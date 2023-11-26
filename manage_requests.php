<?php
    session_start();

    // Funkce pro odhlášení uživatele
    function logoutUser() {
        session_unset(); // Vyprázdnění všech session proměnných
        session_destroy(); // Zničení session
        header('Location: index.html'); // Přesměrování na index.html
        exit(); // Zastavení běhu skriptu
    }

    // Dotaz pro výpis aktuálně přihlášeného uživatele
    $currentUsername = isset($_SESSION['username']) ? $_SESSION['username'] : null;
    $currentRole = isset($_SESSION['role']) ? $_SESSION['role'] : null;

    // Odhlášení uživatele po kliknutí na tlačítko odhlášení
    if (isset($_POST['logout'])) {
        logoutUser();
    }

    // Připojení k databázi
    $db = mysqli_init();
    if (!mysqli_real_connect($db, 'localhost', 'xnovos14', 'inbon8uj', 'xnovos14', 0, '/var/run/mysql/mysql.sock')) {
        die('Nelze se připojit k databázi: ' . mysqli_connect_error());
    }

    if ($currentRole == 'admin') {
        // Admin: Show all pending requests
        $query = "SELECT sar.id, sar.system_id, sar.requesting_user_id, sar.status, sar.request_date, u.username, s.name AS system_name
                  FROM system_access_requests sar
                  JOIN users u ON sar.requesting_user_id = u.id
                  JOIN systems s ON sar.system_id = s.id
                  WHERE sar.status = 'pending'";
    } else {
        // Regular user: Show requests only for systems where the user is the admin
        $currentUsername = mysqli_real_escape_string($db, $_SESSION['username']);
        $userQuery = "SELECT id FROM users WHERE username = '$currentUsername'";
        $userResult = mysqli_query($db, $userQuery);
        $userData = mysqli_fetch_assoc($userResult);
        $currentUserId = $userData['id'];
    
        $query = "SELECT sar.id, sar.system_id, sar.requesting_user_id, sar.status, sar.request_date, u.username, s.name AS system_name
                  FROM system_access_requests sar
                  JOIN users u ON sar.requesting_user_id = u.id
                  JOIN systems s ON sar.system_id = s.id
                  WHERE sar.status = 'pending' AND s.admin_id = '$currentUserId'";
    }

    $result = mysqli_query($db, $query);
    $requests = mysqli_fetch_all($result, MYSQLI_ASSOC);

    if (isset($_POST['action']) && isset($_POST['requestId'])) {
        $requestId = mysqli_real_escape_string($db, $_POST['requestId']);
        $action = $_POST['action'];
    
        if ($action == 'accept') {
            // Update the status in system_access_requests
            $updateRequestQuery = "UPDATE system_access_requests SET status = 'accepted' WHERE id = '$requestId'";
            mysqli_query($db, $updateRequestQuery);
    
            // Fetch system_id and requesting_user_id to grant access
            $fetchRequestQuery = "SELECT system_id, requesting_user_id FROM system_access_requests WHERE id = '$requestId'";
            $requestResult = mysqli_query($db, $fetchRequestQuery);
            $requestData = mysqli_fetch_assoc($requestResult);
    
            // Insert record into system_user_access
            $insertAccessQuery = "INSERT INTO system_user_access (system_id, user_id, access_granted_date) VALUES ('{$requestData['system_id']}', '{$requestData['requesting_user_id']}', NOW())";
            mysqli_query($db, $insertAccessQuery);
        } elseif ($action == 'deny') {
            // Update the status in system_access_requests to 'denied'
            $updateRequestQuery = "UPDATE system_access_requests SET status = 'denied' WHERE id = '$requestId'";
            mysqli_query($db, $updateRequestQuery);
        }
    }
        
?>

<!DOCTYPE html>
<html>
<head>
    <title>Spravovat žádosti</title>
    <link rel="stylesheet" type="text/css" href="welcome_style.css">
    <link rel="stylesheet" type="text/css" href="styles.css">
</head>
<body>

<div class="user-bar">
    <a href="editusers.php" class="system-button">Uživatelé</a>
    <a href="system.php" class="system-button">Systémy</a>
    <a href="devices.php" class="system-button">Zařízení</a>
    <a href="manage_requests.php" class="system-button">Spravovat žádosti</a> <!-- New Button -->
    <?php if ($currentUsername) : ?>
        <span class="user-info">Přihlášený uživatel:</span> <strong><?= $currentUsername ?></strong><br>
        <span class="user-info">Role:</span> <strong><?= $currentRole ?></strong>
        <form method="POST" action="">
            <button class="logout-button" type="submit" name="logout">Odhlásit se</button>
        </form>
    <?php else : ?>
        <span class="user-info">Není žádný uživatel přihlášen.</span>
    <?php endif; ?>
</div>

<div class="centered-content">
    <h2>Žádosti o přístup do systémů</h2>
    <table>
        <tr>
            <th>ID žádosti</th>
            <th>Název systému</th>
            <th>Uživatelské jméno</th>
            <th>Datum žádosti</th>
            <th>Akce</th>
        </tr>
        <?php if ($requests) : ?>
            <?php foreach ($requests as $request) : ?>
            <tr>
                <td><?= $request['id'] ?></td>
                <td><?= $request['system_name'] ?></td>
                <td><?= $request['username'] ?></td>
                <td><?= $request['request_date'] ?></td>
                <td>
                    <form method="POST" action="">
                        <input type="hidden" name="requestId" value="<?= $request['id'] ?>">
                        <button type="submit" name="action" value="accept" class="accept-button">Přijmout</button>
                        <button type="submit" name="action" value="deny" class="deny-button">Odmítnout</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php else : ?>
            <tr><td colspan="5">Žádné žádosti.</td></tr>
        <?php endif; ?>
    </table>
</div>

</body>
</html>