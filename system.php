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

// Dotaz pro získání všech systémů
$query = "SELECT s.id, s.name, s.description, s.admin_id, u.username
    FROM systems as s, users as u WHERE s.admin_id = u.id;";

$result = mysqli_query($db, $query);

if (!$result) {
    die('Chyba dotazu: ' . mysqli_error($db));
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

<h2>Seznam všech systémů</h2>
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
        <th></th>
        <?php endif;?>
    </tr>
    <?php while ($row = mysqli_fetch_assoc($result)) : ?>
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
                <form >
                    <input type="hidden" name="shareSystemId" value="<?= $row['id'] ?>">
                    <button class="edit-button" type="submit" name="loadEditSystem">Sdílet</button>
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
</div>
</body>
</html>

<?php
mysqli_close($db);
?>