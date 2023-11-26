<?php
session_start();

// Dotaz pro odstranění tabulky systems (pokud existuje)
$dropTableQuery = "DROP TABLE IF EXISTS systems";

// Funkce pro odhlášení uživatele
function logoutUser() {
    session_unset(); // Vyprázdnění všech session proměnných
    session_destroy(); // Zničení session
    header('Location: index.html'); // Přesměrování na index.html
    exit(); // Zastavení běhu skriptu
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

// Dotaz pro výpis aktuálně přihlášeného uživatele
$currentUsername = isset($_SESSION['username']) ? $_SESSION['username'] : null;
$currentRole = isset($_SESSION['role']) ? $_SESSION['role'] : null;

// Odhlášení uživatele po kliknutí na tlačítko odhlášení
if (isset($_POST['logout'])) {
    logoutUser();
}

// Připojení k databázi
$db = mysqli_init();
if (!mysqli_real_connect($db, 'localhost', 'xdohna52', 'vemsohu6', 'xdohna52', 0, '/var/run/mysql/mysql.sock')) {
    die('Nelze se připojit k databázi: ' . mysqli_connect_error());
}

// Dotaz pro vytvoření tabulky "systems" (pokud neexistuje)
$createSystemsTableQuery = "CREATE TABLE IF NOT EXISTS systems (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    admin_id INT NOT NULL,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
)";

if (!mysqli_query($db, $createSystemsTableQuery)) {
    die('Chyba při vytváření tabulky systems: ' . mysqli_error($db));
}

// Dotaz pro vytvoření tabulky "system_devices" (pokud neexistuje)
$createSystemDevicesTableQuery = "CREATE TABLE IF NOT EXISTS system_devices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    system_id INT NOT NULL,
    device_id INT NOT NULL,
    FOREIGN KEY (system_id) REFERENCES systems(id) ON DELETE CASCADE,
    FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE
)";

if (!mysqli_query($db, $createSystemDevicesTableQuery)) {
    die('Chyba při vytváření tabulky system_devices: ' . mysqli_error($db));
}

$createSystemAccessRequestsTableQuery = "CREATE TABLE IF NOT EXISTS system_access_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    system_id INT NOT NULL,
    requesting_user_id INT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    request_date DATETIME NOT NULL,
    FOREIGN KEY (system_id) REFERENCES systems(id),
    FOREIGN KEY (requesting_user_id) REFERENCES users(id)
)";

if (!mysqli_query($db, $createSystemAccessRequestsTableQuery)) {
    die('Chyba při vytváření tabulky system_access_requests: ' . mysqli_error($db));
}

$createUserAccessTableQuery = "CREATE TABLE IF NOT EXISTS system_user_access (
    id INT AUTO_INCREMENT PRIMARY KEY,
    system_id INT NOT NULL,
    user_id INT NOT NULL,
    access_granted_date DATETIME NOT NULL,
    FOREIGN KEY (system_id) REFERENCES systems(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
)";

if (!mysqli_query($db, $createUserAccessTableQuery)) {
    die('Chyba při vytváření tabulky system_access_requests: ' . mysqli_error($db));
}




// Zpracování formuláře pro přidání systému
if (isset($_POST['addSystem'])) {
    $newSystemName = mysqli_real_escape_string($db, $_POST['newSystemName']);
    $newSystemDescription = mysqli_real_escape_string($db, $_POST['newSystemDescription']);
    $newSystemAdminID = mysqli_real_escape_string($db, $_POST['newSystemAdminID']);

    if (!systemExists($db, $newSystemName)) {
        $insertQuery = "INSERT INTO systems (name, description, admin_id) VALUES ('$newSystemName', '$newSystemDescription', '$newSystemAdminID')";

        if (mysqli_query($db, $insertQuery)) {
            echo "Systém '$newSystemName' byl úspěšně přidán.";
        } else {
            echo 'Chyba při přidávání systému: ' . mysqli_error($db);
        }
    } else {
        echo "Systém s názvem '$newSystemName' již existuje.";
    }
}

function deleteSystem($db, $systemId) {
    $systemId = mysqli_real_escape_string($db, $systemId);
    $deleteQuery = "DELETE FROM systems WHERE id = '$systemId'";
    return mysqli_query($db, $deleteQuery);
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
    $currentUsername = mysqli_real_escape_string($db, $currentUsername);
    $userQuery = "SELECT id FROM users WHERE username = '$currentUsername'";
    $userResult = mysqli_query($db, $userQuery);
    $userData = mysqli_fetch_assoc($userResult);
    $requestingUserId = $userData['id'];

    $systemId = mysqli_real_escape_string($db, $_POST['requestAccessSystemId']);
    $currentDate = date('Y-m-d H:i:s');

    // Check if request already exists
    $checkRequestQuery = "SELECT COUNT(*) as count FROM system_access_requests WHERE system_id = '$systemId' AND requesting_user_id = '$requestingUserId'";
    $checkRequestResult = mysqli_query($db, $checkRequestQuery);
    $checkRequestData = mysqli_fetch_assoc($checkRequestResult);

    if ($checkRequestData['count'] == 0) {
        // Insert request into the database
        $insertRequestQuery = "INSERT INTO system_access_requests (system_id, requesting_user_id, status, request_date) VALUES ('$systemId', '$requestingUserId', 'pending', '$currentDate')";
        
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

    // Check if user already has access
    $checkAccessQuery = "SELECT COUNT(*) as count FROM system_user_access WHERE system_id = '$systemId' AND user_id = '$shareWithUserId'";
    $checkAccessResult = mysqli_query($db, $checkAccessQuery);
    $checkAccessData = mysqli_fetch_assoc($checkAccessResult);

    if ($checkAccessData['count'] == 0) {
        // Insert shared access into the database
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

if ($currentUsername == 'admin') {
    // Admin sees all systems
    $ownedSystemsQuery = "SELECT * FROM systems";
    $sharedSystemsQuery = ""; // Admin doesn't need to see shared systems separately
} else {
    // Non-admin users see their own systems and systems shared with them
    $userIdQuery = "SELECT id FROM users WHERE username = '$currentUsername'";
    $userIdResult = mysqli_query($db, $userIdQuery);
    $userIdData = mysqli_fetch_assoc($userIdResult);
    $userId = $userIdData['id'];

    $ownedSystemsQuery = "SELECT * FROM systems WHERE admin_id = '$userId'";
    $sharedSystemsQuery = "SELECT s.* FROM system_user_access sua 
                           JOIN systems s ON sua.system_id = s.id 
                           WHERE sua.user_id = '$userId'";

    $otherSystemsQuery = "SELECT * FROM systems WHERE id NOT IN (
        SELECT system_id FROM system_user_access WHERE user_id = '$userId'
        ) AND admin_id != '$userId'";

    $otherSystemsResult = mysqli_query($db, $otherSystemsQuery);
}

// Dotaz pro získání všech systémů
$query = "SELECT s.id, s.name, s.description, s.admin_id, u.username
    FROM systems as s, users as u WHERE s.admin_id = u.id;";

$result = mysqli_query($db, $query);

if (!$result) {
    die('Chyba dotazu: ' . mysqli_error($db));
}

$usersQuery = "SELECT id, username FROM users";
$usersResult = mysqli_query($db, $usersQuery);
$users = mysqli_fetch_all($usersResult, MYSQLI_ASSOC);

// Fetch and display owned systems
$ownedResult = mysqli_query($db, $ownedSystemsQuery);

if ($currentUsername != 'admin') {
    // Fetch and display shared systems
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
    <a href="manage_requests.php" class="system-button">Spravovat žádosti</a>
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

<?php if($currentRole == "admin"):?>
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

    <div class="form-group">
        <label for="newSystemAdminID">ID admina systému:</label>
        <input type="number" id="newSystemAdminID" name="newSystemAdminID" required>
    </div>

    <div class="form-group">
        <button class="btn-submit" type="submit" name="addSystem">Přidat systém</button>
    </div>
</form>
<?php endif;?>

<!-- Owned Systems Table -->
<h2>Vlastněné systémy</h2>
<table>
    <tr>
        <th>Název systému</th>
        <th>Popis</th>
        <th>Admin</th>
        <?php if($currentRole != "guest"):?>
        <th></th>
        <th></th>
        <th></th>
        <th></th>
        <th> systém</th>
        <th>Požádat o sdílení</th>
        <?php endif;?>
    </tr>
    <?php while ($row = mysqli_fetch_assoc($ownedResult)) : ?>
        <tr>
            <td><?= $row['name'] ?></td>
            <td><?= $row['description'] ?></td>
            <td><?= $row['username'] ?></td>
            <?php if($currentRole != "guest"):?>
            <td>
                <form method="POST" action="">
                    <input type="hidden" name="deleteSystemId" value="<?= $row['id'] ?>">
                    <button class="delete-button" type="submit" name="deleteSystem">Smazat</button>
                </form>
            </td>
            <td>
                <!-- Tlačítko pro úpravu systému -->
                <form method="POST" action="edit_systems.php">
                    <input type="hidden" name="editSystemId" value="<?= $row['id'] ?>">
                    <input type="hidden" name="editSystemName" value="<?= $row['name'] ?>">
                    <input type="hidden" name="editSystemDescription" value="<?= $row['description'] ?>">
                    <input type="hidden" name="editSystemAdminID" value="<?= $row['admin_id'] ?>">
                    <button class="edit-button" type="submit" name="loadEditSystem">Upravit</button>
                </form>
            </td>
            <td>
                <form method="POST" action="add_devices_to_system.php"> <!-- Přidat tlačítko pro přidání zařízení do systému -->
                    <input type="hidden" name="addDeviceToSystem" value="<?= $row['id'] ?>">
                    <button class="edit-button" type="submit" name="loadAddDeviceToSystem">Přidat zařízení</button>
                </form>
            </td>
            <td>
                <form method="POST" action="">
                    <input type="hidden" name="shareSystemId" value="<?= $row['id'] ?>">
                    <select name="shareWithUserId">
                        <?php 
                        foreach ($users as $user) {
                            echo "<option value='{$user['id']}'>{$user['username']}</option>";
                        }
                        ?>
                    </select>
                    <button class="share-button" type="submit" name="shareSystem">Sdílet</button>
                </form>
            </td>
            <td>
                <form method="POST" action="">
                    <input type="hidden" name="requestAccessSystemId" value="<?= $row['id'] ?>">
                    <button class="request-access-button" type="submit" name="requestAccess">Poslat žádost</button>
                </form>
            </td>
            <td>
                <form method="POST" action="kpi.php">
                    <input type="hidden" name="KPI" value="<?= $row['admin_id'] ?>">
                    <input type="hidden" name="systemID" value="<?= $row['id'] ?>">
                    <button class="edit-button" type="submit" name="loadKPI">KPI</button>
                </form>
            </td>
            <?php endif;?>
        </tr>
    <?php endwhile; ?>
</table>

<?php if ($currentUsername != 'admin'): ?>
    <!-- Shared Systems Table -->
    <h2>Sdílené systémy</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>Název systému</th>
            <th>Popis</th>
            <th>ID admina</th>
        </tr>
        <?php while ($row = mysqli_fetch_assoc($sharedResult)) : ?>
            <tr>
            <td><?= $row['id'] ?></td>
            <td><?= $row['name'] ?></td>
            <td><?= $row['description'] ?></td>
            <td><?= $row['admin_id'] ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
<?php endif; ?>

<?php if ($currentUsername != 'admin'): ?>
    <!-- Other Systems Table -->
    <h2>Ostatní systémy</h2>
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
<?php endif; ?>

</div>
</body>
</html>

<?php
mysqli_close($db);
?>