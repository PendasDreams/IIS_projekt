<?php
    session_start();

    // Funkce pro odhlášení uživatele
    function logoutUser() {
        session_unset(); 
        session_destroy(); 
        header('Location: index.html'); 
        exit(); 
    }

    
    $currentUsername = isset($_SESSION['username']) ? $_SESSION['username'] : null;
    $currentRole = isset($_SESSION['role']) ? $_SESSION['role'] : null;

    
    if (isset($_POST['logout'])) {
        logoutUser();
    }

    // Připojení k databázi
    include_once("connect.php");
    $db = mysqli_init();
    pripojit();

    if ($currentRole == 'admin') {
        $query = "SELECT sar.id, sar.system_id, sar.requesting_user_id, sar.status, sar.request_date, u.username, s.name AS system_name
                  FROM system_access_requests sar
                  JOIN users u ON sar.requesting_user_id = u.id
                  JOIN systems s ON sar.system_id = s.id
                  WHERE sar.status = 'pending'";
    } else {
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
            
            $updateRequestQuery = "UPDATE system_access_requests SET status = 'accepted' WHERE id = '$requestId'";
            mysqli_query($db, $updateRequestQuery);
    
            
            $fetchRequestQuery = "SELECT system_id, requesting_user_id FROM system_access_requests WHERE id = '$requestId'";
            $requestResult = mysqli_query($db, $fetchRequestQuery);
            $requestData = mysqli_fetch_assoc($requestResult);
    
            
            $insertAccessQuery = "INSERT INTO system_user_access (system_id, user_id, access_granted_date) VALUES ('{$requestData['system_id']}', '{$requestData['requesting_user_id']}', NOW())";
            mysqli_query($db, $insertAccessQuery);
        } elseif ($action == 'deny') {
            
            $updateRequestQuery = "UPDATE system_access_requests SET status = 'denied' WHERE id = '$requestId'";
            mysqli_query($db, $updateRequestQuery);
        }
        header('Location: ' . $_SERVER['PHP_SELF']);
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
    <a href="welcome.php" class="system-button">Menu</a>
    <?php
    if ($currentRole != 'guest'){
        echo '<a href="editusers.php" class="system-button">Uživatelé</a>';
    }
    ?>
    <a href="system.php" class="system-button">Systémy</a>
    <a href="devices.php" class="system-button">Zařízení</a>
    <?php
    if ($currentRole != 'guest'){
        echo '<a href="manage_requests.php" class="system-button">Spravovat žádosti</a>';
    }
    ?>
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