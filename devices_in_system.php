<?php
session_start();



if (isset($_SESSION['systemData'])) {
    $systemData = $_SESSION['systemData'];
    // Pre-fill the form fields with system data
    $editedSystemId = $systemData['id'];
    $editedSystemName = $systemData['name'];
    $editedSystemDescription = $systemData['description'];
    $editedSystemAdminID = $systemData['admin_id'];
} else {
    // Handle the case where system data is not available
    $editedSystemId = "";
    $editedSystemName = "";
    $editedSystemDescription = "";
    $editedSystemAdminID = "";
}

$systemId = isset($_POST['systemId']) ? intval($_POST['systemId']) : 0;


// At the beginning of your PHP code
$editedSystemId = isset($_POST['editedSystemId']) ? $_POST['editedSystemId'] : "";
$editedSystemName = isset($_POST['editedSystemName']) ? $_POST['editedSystemName'] : "";
$editedSystemDescription = isset($_POST['editedSystemDescription']) ? $_POST['editedSystemDescription'] : "";
$editedSystemAdminID = isset($_POST['editedSystemAdminID']) ? $_POST['editedSystemAdminID'] : "";





function getDevicesInSystemFromDatabase($db, $systemId) {
    $deviceQuery = "SELECT devices.* FROM devices 
                    INNER JOIN system_devices ON devices.id = system_devices.device_id
                    WHERE system_devices.system_id = '$systemId'";
    $deviceResult = mysqli_query($db, $deviceQuery);

    if (!$deviceResult) {
        die('Chyba dotazu na zařízení: ' . mysqli_error($db));
    }

    return $deviceResult;
}

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

    // Fetch the system data
    $query = "SELECT * FROM systems WHERE id = '$editSystemId'";
    $result = mysqli_query($db, $query);

    if (!$result) {
        die('Chyba dotazu: ' . mysqli_error($db));
    }

    $systemData = mysqli_fetch_assoc($result);

    // Fetch the devices associated with the system
    $deviceQuery = "SELECT devices.* FROM devices 
                    INNER JOIN system_devices ON devices.id = system_devices.device_id
                    WHERE system_devices.system_id = '$editSystemId'";
    $deviceResult = mysqli_query($db, $deviceQuery);

    if (!$deviceResult) {
        die('Chyba dotazu na zařízení: ' . mysqli_error($db));
    }
} else {
    // Handle the case where $_POST['editSystemId'] is not set
    $systemData = null; // Set a default value for $systemData
    $deviceResult = null; // Set a default value for $deviceResult
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
    $systemId = isset($_POST['systemId']) ? intval($_POST['systemId']) : ($systemData['id'] ?? 0);

    if ($systemId > 0) {
        // Před vložením do system_devices zkontrolujte, zda systém s tímto ID existuje
        $checkSystemQuery = "SELECT id FROM systems WHERE id = '$systemId'";
        $checkSystemResult = mysqli_query($db, $checkSystemQuery);

        if (!$checkSystemResult) {
            die('Chyba při kontrole systému: ' . mysqli_error($db));
        }

        if (mysqli_num_rows($checkSystemResult) > 0) {
            // Systém existuje, můžete pokračovat s vkládáním do system_devices
            $insertQuery = "INSERT INTO system_devices (system_id, device_id) VALUES ('$systemId', '$deviceId')";

            if (mysqli_query($db, $insertQuery)) {
                echo "Zařízení bylo úspěšně přidáno do systému.";
            } else {
                echo 'Chyba při přidávání zařízení do systému: ' . mysqli_error($db);
            }
        } else {
            echo "Systém s ID $systemId neexistuje.";
        }
    } else {
        echo "Neplatná hodnota pro systemId.";
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
            <form method="POST" action="">
                <input type="hidden" name="deviceId" value="<?= $row['id'] ?>">
                <input type="hidden" name="systemId" value="<?= $systemData['id'] ?? (isset($_POST['systemId']) ? $_POST['systemId'] : '') ?>">
                <button class="edit-button" type="submit" name="addToSystem">Přidat do systému</button>
            </form>
        </td>
        </tr>
    <?php endwhile; ?>
</table>
</div>


<div class="centered-form">
    <h2>Upravit systém</h2>
    <?php if ($systemData !== null) : ?>
        <p><strong>System ID:</strong> <?= $systemData['id'] ?></p>
        <p><strong>Název systému:</strong> <?= $systemData['name'] ?></p>
        <p><strong>Popis systému:</strong> <?= $systemData['description'] ?></p>
        <p><strong>ID admina systému:</strong> <?= $systemData['admin_id'] ?></p>
    <?php endif; ?>
    <form class="user-form" method="POST" action="">
        <input type="hidden" name="editedSystemId" value="<?= $editedSystemId ?>">
        <input type="hidden" name="editedSystemName" value="<?= $editedSystemName ?>">
        <input type="hidden" name="editedSystemDescription" value="<?= $editedSystemDescription ?>">
        <input type="hidden" name="editedSystemAdminID" value="<?= $editedSystemAdminID ?>">
    
        <div class="form-group">
            <label for="editedSystemName">Název systému:</label>
            <input type="text" id="editedSystemName" name="editedSystemName" required value="<?= $editedSystemName ?>">
        </div>

        <div class="form-group">
            <label for="editedSystemDescription">Popis systému:</label>
            <textarea id="editedSystemDescription" name="editedSystemDescription" required><?= $editedSystemDescription ?></textarea>
        </div>

        <div class="form-group">
            <label for="editedSystemAdminID">ID admina systému:</label>
            <input type="number" id="editedSystemAdminID" name="editedSystemAdminID" required value="<?= $editedSystemAdminID ?>">
        </div>

        <div class="form-group">
            <button class="btn-submit" type="submit" name="saveChanges">Uložit změny</button>
        </div>
    </form>
</div>

<div class="centered-form">
    <?php if ($systemData !== null) : ?>
        <h2>Seznam zařízení v systému "<?= $systemData['name'] ?>"</h2>
    <?php else : ?>
        <h2>Seznam zařízení v systému</h2>
    <?php endif; ?>
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
        </tr>

        <?php if ($deviceResult !== null) : ?>
            <?php while ($deviceRow = mysqli_fetch_assoc($deviceResult)) : ?>
                <tr>
                    <td><?= $deviceRow['id'] ?></td>
                    <td><?= $deviceRow['device_name'] ?></td>
                    <td><?= $deviceRow['device_type'] ?></td>
                    <td><?= $deviceRow['device_description'] ?></td>
                    <td><?= $deviceRow['user_alias'] ?></td>
                    <td><?= $deviceRow['hodnota'] ?></td>
                    <td><?= $deviceRow['jednotka'] ?></td>
                    <td><?= $deviceRow['maintenance_interval'] ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else : ?>
            <tr>
                <td colspan="8">Žádná zařízení nejsou v tomto systému.</td>
            </tr>
        <?php endif; ?>
    </table>
</div>

</body>
</html>

<?php
mysqli_close($db);
?>
