<?php
session_start();

// Připojení k databázi
include_once("connect.php");
$db = mysqli_init();
pripojit();


// Funkce pro odhlášení uživatele
function logoutUser() {
    session_unset();
    session_destroy();
    header('Location: index.html'); 
    exit(); 
}

function systemExists($db, $systemName) {
    $systemName = mysqli_real_escape_string($db, $systemName);
    $query = "SELECT COUNT(*) as count FROM systems WHERE name = '$systemName'";
    $result = mysqli_query($db, $query);

    if (!$result) {
        die('Chyba dotazu: ' . mysqli_error($db));
    }

    $row = mysqli_fetch_assoc($result);
    return $row['count'] > 0;
}


$currentUsername = isset($_SESSION['username']) ? $_SESSION['username'] : null;
$currentRole = isset($_SESSION['role']) ? $_SESSION['role'] : null;


$userIdQuery = "SELECT id FROM users WHERE username = '$currentUsername'";
$userIdResult = mysqli_query($db, $userIdQuery);
$userIdData = mysqli_fetch_assoc($userIdResult);
$loggedInUserId = $userIdData['id']; // ID prihlaseneho uzivatele

if (isset($_POST['logout'])) {
    logoutUser();
}

// Připojení k databázi
include_once("connect.php");
$db = mysqli_init();
pripojit();

// Zpracování formuláře pro přidání systému
if (isset($_POST['addSystem'])) {
    $newSystemName = $_POST['newSystemName'];
    $newSystemDescription = $_POST['newSystemDescription'];
    
    $newSystemAdminID = $currentRole == 'admin' ? $_POST['newSystemAdminID'] : $loggedInUserId;

    if (!systemExists($db, $newSystemName)) {
        $insertQuery = $db->prepare("INSERT INTO systems (name, description, admin_id) VALUES (?, ?, ?)");
        $insertQuery->bind_param("ssi", $newSystemName, $newSystemDescription, $newSystemAdminID);

        if ($insertQuery->execute()) {
            echo "Systém '$newSystemName' byl úspěšně přidán.";
        } else {
            echo 'Chyba při přidávání systému: ' . $insertQuery->error;
        }
    } else {
        echo "Systém s názvem '$newSystemName' již existuje.";
    }
}

function deleteSystem($db, $systemId) {
    mysqli_begin_transaction($db);

    try {
        
        $deleteAccessRequestsQuery = "DELETE FROM system_access_requests WHERE system_id = '$systemId'";
        if (!mysqli_query($db, $deleteAccessRequestsQuery)) {
            throw new Exception('Error deleting access requests: ' . mysqli_error($db));
        }

        $deleteUserAccessQuery = "DELETE FROM system_user_access WHERE system_id = '$systemId'";
        if (!mysqli_query($db, $deleteUserAccessQuery)) {
            throw new Exception('Error deleting user access: ' . mysqli_error($db));
        }

        $deleteSystemQuery = "DELETE FROM systems WHERE id = '$systemId'";
        if (!mysqli_query($db, $deleteSystemQuery)) {
            throw new Exception('Error deleting system: ' . mysqli_error($db));
        }

        mysqli_commit($db);
        return true;
    } catch (Exception $e) {
        // Rollback 
        mysqli_rollback($db);
        error_log($e->getMessage());
        return false;
    }
}

// Smazání systému
if (isset($_POST['deleteSystem'])) {
    $systemIdToDelete = mysqli_real_escape_string($db, $_POST['deleteSystemId']);
    if (deleteSystem($db, $systemIdToDelete)) {
        echo "Systém s ID $systemIdToDelete byl smazán.";
    } else {
        echo 'Chyba při mazání systému: ' . mysqli_error($db);
    }
}

if (isset($_POST['requestAccess'])) {
    $systemId = mysqli_real_escape_string($db, $_POST['requestAccessSystemId']);
    $currentDate = date('Y-m-d H:i:s');

    // Check zda jiz dany request neexistuje
    $checkRequestQuery = "SELECT COUNT(*) as count FROM system_access_requests WHERE system_id = '$systemId' AND requesting_user_id = '$loggedInUserId'";
    $checkRequestResult = mysqli_query($db, $checkRequestQuery);
    $checkRequestData = mysqli_fetch_assoc($checkRequestResult);

    if ($checkRequestData['count'] == 0) {
        
        $insertRequestQuery = "INSERT INTO system_access_requests (system_id, requesting_user_id, status, request_date) VALUES ('$systemId', '$loggedInUserId', 'pending', '$currentDate')";
        
        if (mysqli_query($db, $insertRequestQuery)) {
            echo "Request to access system ID $systemId has been sent.";
        } else {
            echo 'Error sending request: ' . mysqli_error($db);
        }
    } else {
        echo "Request already exists.";
    }
}

if (isset($_POST['shareSystem'])) {
    $systemId = mysqli_real_escape_string($db, $_POST['shareSystemId']);
    $shareWithUserId = mysqli_real_escape_string($db, $_POST['shareWithUserId']);
    $currentDate = date('Y-m-d H:i:s');

    // Check zda jiz nema uzivatel pristup
    $checkAccessQuery = "SELECT COUNT(*) as count FROM system_user_access WHERE system_id = '$systemId' AND user_id = '$shareWithUserId'";
    $checkAccessResult = mysqli_query($db, $checkAccessQuery);
    $checkAccessData = mysqli_fetch_assoc($checkAccessResult);

    if ($checkAccessData['count'] == 0) {
        
        $insertAccessQuery = "INSERT INTO system_user_access (system_id, user_id, access_granted_date) VALUES ('$systemId', '$shareWithUserId', '$currentDate')";
        
        if (mysqli_query($db, $insertAccessQuery)) {
            echo "System ID $systemId has been shared with user ID $shareWithUserId.";
        } else {
            echo 'Error sharing system: ' . mysqli_error($db);
        }
    } else {
        echo "User already has access.";
    }
}

$ownedSystemsQuery = "";
$sharedSystemsQuery = "";

if ($currentRole == 'admin') {
    
    $ownedSystemsQuery = "SELECT s.id, s.name, s.description, s.admin_id, u.username 
                          FROM systems s 
                          JOIN users u ON s.admin_id = u.id";
    $sharedSystemsQuery = "";
} else {
    
    $userIdQuery = "SELECT id FROM users WHERE username = '$currentUsername'";
    $userIdResult = mysqli_query($db, $userIdQuery);
    $userIdData = mysqli_fetch_assoc($userIdResult);
    $userId = $userIdData['id'];

    $ownedSystemsQuery = "SELECT s.id, s.name, s.description, s.admin_id, u.username
                      FROM systems s
                      JOIN users u ON s.admin_id = u.id
                      WHERE s.admin_id = '$userId'";
    $sharedSystemsQuery = "SELECT s.id, s.name, s.description, s.admin_id, u.username
    FROM system_user_access sua
    JOIN systems s ON sua.system_id = s.id
    JOIN users u ON s.admin_id = u.id
    WHERE sua.user_id = '$userId'";


    $otherSystemsQuery = "SELECT * FROM systems WHERE id NOT IN (
        SELECT system_id FROM system_user_access WHERE user_id = '$userId'
        ) AND admin_id != '$userId'";


    $otherSystemsResult = mysqli_query($db, $otherSystemsQuery);
}

$query = "SELECT s.*, u.username
    FROM systems as s, users as u WHERE s.admin_id = u.id;";

$result = mysqli_query($db, $query);

if (!$result) {
    die('Chyba dotazu: ' . mysqli_error($db));
}


$usersDropdownQuery = "SELECT u.id, u.username 
                        FROM users u 
                        JOIN roles r ON u.role = r.id 
                        WHERE r.role NOT IN ('broker', 'guest', 'admin')";
$usersDropdownResult = mysqli_query($db, $usersDropdownQuery);
$usersDropdown = mysqli_fetch_all($usersDropdownResult, MYSQLI_ASSOC);



$ownedResult = mysqli_query($db, $ownedSystemsQuery);

if ($currentRole != 'admin') {
    $sharedResult = mysqli_query($db, $sharedSystemsQuery);
}

?>


<html>
<head>
    <title>Přidat systém</title>
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
    <?php if ($currentRole != 'guest') : ?>
    <a href="manage_requests.php" class="system-button">Spravovat žádosti</a>
    <?php endif; ?>
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

<?php if ($currentRole != "guest"): ?>
    <div class="centered-buttons">
        <h2>Přidat systém</h2>
        <form class="user-form" method="POST" action="">
            <div class="form-group">
                <label for="newSystemName">Název systému:</label>
                <input type="text" id="newSystemName" name="newSystemName" required>
            </div>

            <div class="form-group">
                <label for="newSystemDescription">Popis systému:</label>
                <textarea id="newSystemDescription" name="newSystemDescription" required></textarea>
            </div>

            <?php if ($currentRole == "admin"): ?>
                <div class="form-group">
                    <label for="newSystemAdminID">Admin systému:</label>
                    <select id="newSystemAdminID" name="newSystemAdminID" required>
                        <?php foreach ($usersDropdown as $user): ?>
                            <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['username']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <button class="btn-submit" type="submit" name="addSystem">Přidat systém</button>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php if($currentRole != 'guest'): ?>
    <h2>Vlastněné systémy</h2>
    <?php if (mysqli_num_rows($ownedResult) > 0): ?>
    <table style="margin-bottom: 20px;">
        <tr>
            <th>ID</th>
            <th>Název systému</th>
            <th>Popis</th>
            <th>Admin</th>
            <th></th>
            <th></th>
            <th></th>
            <th></th>
            <th> systém</th>
        </tr>
        <?php while ($row = mysqli_fetch_assoc($ownedResult)) : ?>
            <?php
            $currentSystemId = $row['id'];
            $usersDropdownQueryForSystem = "SELECT u.id, u.username 
                                            FROM users u 
                                            JOIN roles r ON u.role = r.id 
                                            WHERE r.role NOT IN ('broker', 'guest', 'admin')
                                            AND u.id != '{$row['admin_id']}'"; 
            $usersDropdownResultForSystem = mysqli_query($db, $usersDropdownQueryForSystem);
            $usersDropdownForSystem = mysqli_fetch_all($usersDropdownResultForSystem, MYSQLI_ASSOC);
            ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= $row['name'] ?></td>
                <td><?= $row['description'] ?></td>
                <td><?= $row['username'] ?></td>
                <td>
                    <form method="POST" action="">
                        <input type="hidden" name="deleteSystemId" value="<?= $row['id'] ?>">
                        <button class="delete-button" type="submit" name="deleteSystem">Smazat</button>
                    </form>
                </td>
                <td>
                    
                    <form method="POST" action="edit_systems.php">
                        <input type="hidden" name="editSystemId" value="<?= $row['id'] ?>">
                        <input type="hidden" name="editSystemName" value="<?= $row['name'] ?>">
                        <input type="hidden" name="editSystemDescription" value="<?= $row['description'] ?>">
                        <input type="hidden" name="editSystemAdminID" value="<?= $row['admin_id'] ?>">
                        <button class="edit-button" type="submit" name="loadEditSystem">Upravit</button>
                    </form>
                </td>
                <td>
                    <form method="POST" action="add_devices_to_system.php"> 
                        <input type="hidden" name="addDeviceToSystem" value="<?= $row['id'] ?>">
                        <button class="edit-button" type="submit" name="loadAddDeviceToSystem">Správa zařízení</button>
                    </form>
                </td>
                <td>
                    <form method="POST" action="">
                        <input type="hidden" name="shareSystemId" value="<?= $row['id'] ?>">
                        <select name="shareWithUserId">
                        <?php foreach ($usersDropdownForSystem as $user): ?>
                            <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['username']) ?></option>
                        <?php endforeach; ?>
                        </select>
                        <button class="share-button" type="submit" name="shareSystem">Sdílet systém</button>
                    </form>
                </td>
                <td>
                    <form method="POST" action="kpi.php">
                        <input type="hidden" name="KPI" value="<?= $row['admin_id'] ?>">
                        <input type="hidden" name="systemID" value="<?= $row['id'] ?>">
                        <button class="edit-button" type="submit" name="loadKPI">KPI</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
    <?php else: ?>
        <p>Žádné vlastněné systémy na zobrazení.</p>
    <?php endif; ?>
    

    <?php if ($currentUsername != 'admin'): ?>
       
        <h2>Sdílené systémy</h2>
        <?php if (mysqli_num_rows($sharedResult) > 0): ?>
        <table style="margin-bottom: 80px;">
            <tr>
                <th>ID</th>
                <th>Název systému</th>
                <th>Popis</th>
                <th>ID admina</th>
                <th></th>
            </tr>
            <?php while ($row = mysqli_fetch_assoc($sharedResult)) : ?>
                <tr>
                <td><?= $row['id'] ?></td>
                <td><?= $row['name'] ?></td>
                <td><?= $row['description'] ?></td>
                <td><?= $row['admin_id'] ?></td>
                <td>
                    <form method="POST" action="add_devices_to_system.php"> 
                        <input type="hidden" name="addDeviceToSystem" value="<?= $row['id'] ?>">
                        <button class="edit-button" type="submit" name="loadAddDeviceToSystem">Sledovat zařízení</button>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
        <?php else: ?>
            <p>Žádné sdílené systémy na zobrazení.</p>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($currentUsername != 'admin'): ?>
        
        <h2>Ostatní systémy</h2>
        <?php if (mysqli_num_rows($otherSystemsResult) > 0): ?>
        <table>
            <tr>
                <th>ID</th>
                <th>Název systému</th>
                <th>Popis</th>
                <th>ID admina</th>
                <th>Požádat o sdílení</th>
            </tr>
            <?php while ($row = mysqli_fetch_assoc($otherSystemsResult)) : ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= $row['name'] ?></td>
                    <td><?= $row['description'] ?></td>
                    <td><?= $row['admin_id'] ?></td>
                    <td>
                        <form method="POST" action="">
                            <input type="hidden" name="requestAccessSystemId" value="<?= $row['id'] ?>">
                            <button class="request-access-button" type="submit" name="requestAccess">Poslat žádost</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
        <?php else: ?>
            <p>Žádné ostatní systémy k zobrazení.</p>
        <?php endif; ?>
    <?php endif; ?>
<?php else: ?>

    <h2>Seznam systémů</h2>
    <?php if (mysqli_num_rows($result) > 0): ?>
        <table>
            <tr>
                <th>Název systému</th>
                <th>Popis</th>
                <th>Admin</th>
            </tr>
            <?php while ($row = mysqli_fetch_assoc($result)) : ?>
                <tr>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td><?= htmlspecialchars($row['description']) ?></td>
                    <td><?= htmlspecialchars($row['username']) ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p>Žádné systémy k zobrazení.</p>
    <?php endif; ?>
<?php endif; ?>
</div>

</body>
</html>

<?php
mysqli_close($db);
?>