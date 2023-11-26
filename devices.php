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
if (!mysqli_real_connect($db, 'localhost', 'xdohna52', 'vemsohu6', 'xdohna52', 0, '/var/run/mysql/mysql.sock')) {
    die('Nelze se připojit k databázi: ' . mysqli_connect_error());
}

// SQL dotaz pro získání dat ze tabulky "devices"
$selectQuery = "SELECT * FROM devices";

// Vykonání dotazu
$result = mysqli_query($db, $selectQuery);

if (!$result) {
    die('Chyba dotazu: ' . mysqli_error($db));
}

$successMessage = ''; // Inicializace proměnné pro úspěšnou zprávu prázdnou hodnotou
$errorMessage = ''; // Inicializace proměnné pro chybovou zprávu prázdnou hodnotou



// Zpracování formuláře pro vytvoření zařízení
if (isset($_POST['createDevice'])) {
    $deviceName = mysqli_real_escape_string($db, $_POST['deviceName']);
    $deviceType = mysqli_real_escape_string($db, $_POST['deviceType']);
    $deviceDescription = mysqli_real_escape_string($db, $_POST['deviceDescription']);
    $userAlias = mysqli_real_escape_string($db, $_POST['userAlias']);
    $hodnota = $_POST['hodnota']; // Hodnota nemusí být escape, protože se nepoužívá v SQL dotazu
    $jednotka = mysqli_real_escape_string($db, $_POST['jednotka']);
    $maintenanceInterval = $_POST['maintenanceInterval']; // Interval nemusí být escape, protože se nepoužívá v SQL dotazu

    // Kontrola, zda jsou datové typy správné pro pole hodnota a maintenance_interval
    if (!is_numeric($hodnota) || !is_numeric($maintenanceInterval)) {
        $errorMessage = "Chyba: Pole 'Hodnota' a 'Interval údržby' musí být číselné hodnoty.";
    } else {
        // Pokud jsou datové typy správné, provedeme vložení do databáze
        $insertQuery = "INSERT INTO devices (device_name, device_type, device_description, user_alias, hodnota, jednotka, maintenance_interval) 
                        VALUES ('$deviceName', '$deviceType', '$deviceDescription', '$userAlias', '$hodnota', '$jednotka', '$maintenanceInterval')";

        if (mysqli_query($db, $insertQuery)) {
            $_SESSION['successMessage'] = "Zařízení bylo úspěšně vytvořeno.";
            header('Location: devices.php'); // Přesměrování na stránku se seznamem zařízení
        } else {
            $errorMessage = "Zařízení se nepodařilo vytvořit.";
        }
    }
}

// Zpracování formuláře pro mazání zařízení
if (isset($_POST['deleteDevice'])) {
    $deleteDeviceId = mysqli_real_escape_string($db, $_POST['deleteDeviceId']);

    // SQL dotaz pro smazání zařízení z databáze
    $deleteDeviceQuery = "DELETE FROM devices WHERE id = '$deleteDeviceId'";

    if (mysqli_query($db, $deleteDeviceQuery)) {
        $_SESSION['successMessageTable'] = "Zařízení s ID $deleteDeviceId bylo úspěšně smazáno.";
        header('Location: ' . $_SERVER['PHP_SELF']); // Přesměrování na stejnou stránku pro aktualizaci tabulky
    } else {
        echo 'Chyba při mazání zařízení: ' . mysqli_error($db);
    }
}


?>

<!DOCTYPE html>
<html>
<head>
    <title>Přidat zařízení</title>
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
<?php
if($currentRole == 'admin' || $currentRole == 'registered'):
?>
<div class="centered-form">
<h2>Vytvořit nové zařízení</h2>
    <?php
    // Zobrazíme úspěšnou zprávu, pokud existuje
    if (!empty($successMessage)) {
        echo '<p style="color: green;">' . $successMessage . '</p>';
    }
    if (!empty($errorMessage)) {
        echo '<p style="color: red;">' . $errorMessage . '</p>';
    }
    ?>


        <form class="user-form" method="POST" action="">
            <div class="form-group">
            <label for="deviceName">Název zařízení:</label>
            <input type="text" id="deviceName" name="deviceName" required>
        </div>

        <div class="form-group">
            <label for="deviceType">Typ zařízení:</label>
            <input type="text" id="deviceType" name="deviceType" required>
        </div>

        <div class="form-group">
            <label for="deviceDescription">Popis zařízení:</label>
            <textarea id="deviceDescription" name="deviceDescription" required></textarea>
        </div>

        <div class="form-group">
            <label for="userAlias">Uživatelský alias:</label>
            <input type="text" id="userAlias" name="userAlias">
        </div>

        <div class="form-group">
            <label for="hodnota">Hodnota:</label>
            <input type="text" id="hodnota" name="hodnota">
        </div>

        <div class="form-group">
            <label for="jednotka">Jednotka:</label>
            <input type="text" id="jednotka" name="jednotka">
        </div>

        <div class="form-group">
            <label for="maintenanceInterval">Interval údržby (dny):</label>
            <input type="number" id="maintenanceInterval" name="maintenanceInterval">
        </div>

        <div class="form-group">
            <button class="btn-submit" type="submit" name="createDevice">Vytvořit zařízení</button>
        </div>
        <p id="successMessage" style="color: green;"></p> <!-- Výpis úspěšného vytvoření zařízení -->

    </form>
</div>

<?php
        if (isset($_SESSION['successMessage'])) {
            echo '<p style="color: green;">' . $_SESSION['successMessage'] . '</p>';
            // Po zobrazení zprávy ji vymažeme z session, aby se nezobrazovala znovu
            unset($_SESSION['successMessage']);
        }
?>


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
            <th>Smazat</th> <!-- Přidáme sloupec pro tlačítko Smazat -->
            <th>Upravit</th> <!-- Přidán sloupec pro tlačítko "Upravit" -->
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
                        <input type="hidden" name="deleteDeviceId" value="<?= $row['id'] ?>">
                        <button class="delete-button" type="submit" name="deleteDevice">Smazat</button>
                    </form>
                </td>
                <td>
                    <form method="POST" action="edit_devices.php" onsubmit="scrollToEditForm()">
                        <input type="hidden" name="editUserId" value="<?= $row['id'] ?>">
                        <button class="edit-button" type="submit" name="loadEditForm">Upravit</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>

    <?php
    // Zobrazíme zprávu o úspěšném vytvoření zařízení pod tabulkou, pokud je k dispozici
    if (isset($_SESSION['successMessageTable'])) {
        echo '<p id="successMessageTable" style="color: green;">' . $_SESSION['successMessageTable'] . '</p>';
        // Po zobrazení zprávy ji vymažeme z session, aby se nezobrazovala znovu
        unset($_SESSION['successMessageTable']);
    }
    ?>
</div>
<?php else:?>
    <div class="centered-form">
    <h2>Seznam zařízení</h2>
    <table class="device-table">
        <tr>
            <th>Název zařízení</th>
            <th>Typ zařízení</th>
            <th>Popis zařízení</th>
        </tr>

        <?php 
            while ($row = mysqli_fetch_assoc($result)) : 
        ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= $row['device_name'] ?></td>
                <td><?= $row['device_type'] ?></td>
            </tr>
        <?php endwhile; ?>
    </table>
</div>
<?php endif;?>
</body>
</html>

<?php
mysqli_close($db);
?>