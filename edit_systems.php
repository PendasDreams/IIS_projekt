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

// Získání ID systému, který se má upravit
if (isset($_POST['editSystemId'])) {
    $editSystemId = mysqli_real_escape_string($db, $_POST['editSystemId']);
    
    // Dotaz pro získání údajů o systému
    $query = "SELECT * FROM systems WHERE id = '$editSystemId'";
    $result = mysqli_query($db, $query);

    if (!$result) {
        die('Chyba dotazu: ' . mysqli_error($db));
    }

    $systemData = mysqli_fetch_assoc($result);
}

// Logika pro uložení změn v systému po odeslání formuláře
if (isset($_POST['saveChanges'])) {
    $editedSystemId = mysqli_real_escape_string($db, $_POST['editedSystemId']);
    $editedSystemName = mysqli_real_escape_string($db, $_POST['editedSystemName']);
    $editedSystemDescription = mysqli_real_escape_string($db, $_POST['editedSystemDescription']);
    $editedSystemAdminID = mysqli_real_escape_string($db, $_POST['editedSystemAdminID']);

    // Dotaz pro aktualizaci údajů systému
    $updateQuery = "UPDATE systems SET name = '$editedSystemName', description = '$editedSystemDescription', admin_id = '$editedSystemAdminID' WHERE id = '$editedSystemId'";

    if (mysqli_query($db, $updateQuery)) {
        echo "Systém byl úspěšně upraven.";
    } else {
        echo 'Chyba při úpravě systému: ' . mysqli_error($db);
    }
}

if (isset($_POST['addToSystem'])) {
    $deviceId = mysqli_real_escape_string($db, $_POST['deviceId']);
    $systemId = mysqli_real_escape_string($db, $_POST['systemId']);

    // Kontrola, zda zařízení již není přidáno do systému
    $checkQuery = "SELECT COUNT(*) as count FROM system_devices WHERE system_id = '$systemId' AND device_id = '$deviceId'";
    $checkResult = mysqli_query($db, $checkQuery);

    if (!$checkResult) {
        die('Chyba dotazu: ' . mysqli_error($db));
    }

    $checkRow = mysqli_fetch_assoc($checkResult);
    if ($checkRow['count'] > 0) {
        echo "Toto zařízení je již přidáno do systému.";
    } else {
        // Vložení zařízení do systému
        $insertQuery = "INSERT INTO system_devices (system_id, device_id) VALUES ('$systemId', '$deviceId')";

        if (mysqli_query($db, $insertQuery)) {
            echo "Zařízení bylo úspěšně přidáno do systému.";
        } else {
            echo 'Chyba při přidávání zařízení do systému: ' . mysqli_error($db);
        }
    }
}


// SQL dotaz pro získání dat ze tabulky "devices"
$selectQuery = "SELECT * FROM devices";

// Vykonání dotazu
$result = mysqli_query($db, $selectQuery);

if (!$result) {
    die('Chyba dotazu: ' . mysqli_error($db));
}
?>


<!DOCTYPE html>
<html>
<head>
    <title>Upravit systém</title>
    <link rel="stylesheet" type="text/css" href="welcome_style.css">
    <link rel="stylesheet" type="text/css" href="styles.css">
</head>
<body>

<div class="user-bar">
    <a href="editusers.php" class="system-button">Uživatelé</a>
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


<div class="centered-form">
    <h2>Seznam zařízení</h2>
    <table class="device-table">
    <tr>
        <th>ID</th>
        <th>Název zařízení</th>
        <th>Typ zařízení</th>
        <th>Popis zařízení</th>
        <th>Uživatelský alias</th>
        <th>Hodnota</th>
        <th>Jednotka</th>
        <th>Interval údržby (dny)</th>
        <th>Akce</th> <!-- Nový sloupec pro tlačítko "Přidat do systému" -->
    </tr>

    <?php while ($row = mysqli_fetch_assoc($result)) : ?>
        <tr>
            <td><?= $row['id'] ?></td>
            <td><?= $row['device_name'] ?></td>
            <td><?= $row['device_type'] ?></td>
            <td><?= $row['device_description'] ?></td>
            <td><?= $row['user_alias'] ?></td>
            <td><?= $row['hodnota'] ?></td>
            <td><?= $row['jednotka'] ?></td>
            <td><?= $row['maintenance_interval'] ?></td>
            <td>
                <form method="POST" action="edit_systems.php"> <!-- Nastavte akci na stránku pro přidání zařízení do systému -->
                    <input type="hidden" name="deviceId" value="<?= $row['id'] ?>">
                    <button class="edit-button" type="submit" name="addToSystem">Přidat do systému</button>
                </form>
            </td>
        </tr>
    <?php endwhile; ?>
</table>
</div>


<div class="centered-form">
    <h2>Upravit systém</h2>
    <form class="user-form" method="POST" action="">
        <input type="hidden" name="editedSystemId" value="<?= $systemData['id'] ?>">
        <div class="form-group">
            <label for="editedSystemName">Název systému:</label>
            <input type="text" id="editedSystemName" name="editedSystemName" required value="<?= $systemData['name'] ?>">
        </div>

        <div class="form-group">
            <label for="editedSystemDescription">Popis systému:</label>
            <textarea id="editedSystemDescription" name="editedSystemDescription" required><?= $systemData['description'] ?></textarea>
        </div>

        <div class="form-group">
            <label for="editedSystemAdminID">ID admina systému:</label>
            <input type="number" id="editedSystemAdminID" name="editedSystemAdminID" required value="<?= $systemData['admin_id'] ?>">
        </div>

        <div class="form-group">
            <button class="btn-submit" type="submit" name="saveChanges">Uložit změny</button>
        </div>
    </form>
</div>

</body>
</html>

<?php
mysqli_close($db);
?>
