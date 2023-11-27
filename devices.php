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

// Odhlášení uživatele po kliknutí na tlačítko odhlášení
if (isset($_POST['logout'])) {
    logoutUser();
}


include_once("connect.php");
$db = mysqli_init();
pripojit();

$selectQuery = "SELECT * FROM devices";
$result = mysqli_query($db, $selectQuery);

if (!$result) {
    die('Chyba dotazu: ' . mysqli_error($db));
}

$successMessage = ''; 
$errorMessage = ''; 



// Zpracování formuláře pro vytvoření zařízení
if (isset($_POST['createDevice'])) {
    $deviceName = $_POST['deviceName'];
    $deviceType = $_POST['deviceType'];
    $deviceDescription = $_POST['deviceDescription'];
    $userAlias = $_POST['userAlias'];
    $hodnota = $_POST['hodnota']; 
    $jednotka = $_POST['jednotka'];
    $maintenanceInterval = $_POST['maintenanceInterval']; 

    if (!is_numeric($hodnota) || !is_numeric($maintenanceInterval)) {
        $errorMessage = "Chyba: Pole 'Hodnota' a 'Interval údržby' musí být číselné hodnoty.";
    } else {
        
        $insertStmt = $db->prepare("INSERT INTO devices (device_name, device_type, device_description, user_alias, hodnota, jednotka, maintenance_interval) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $insertStmt->bind_param("ssssids", $deviceName, $deviceType, $deviceDescription, $userAlias, $hodnota, $jednotka, $maintenanceInterval);

        if ($insertStmt->execute()) {
            $_SESSION['successMessage'] = "Zařízení bylo úspěšně vytvořeno.";
            header('Location: devices.php');
            exit();
        } else {
            $errorMessage = "Zařízení se nepodařilo vytvořit: " . $insertStmt->error;
        }
        $insertStmt->close();
    }
}

// Zpracování formuláře pro mazání zařízení
if (isset($_POST['deleteDevice'])) {
    $deleteDeviceId = mysqli_real_escape_string($db, $_POST['deleteDeviceId']);

   
    $deleteDeviceQuery = "DELETE FROM devices WHERE id = '$deleteDeviceId'";

    if (mysqli_query($db, $deleteDeviceQuery)) {
        $_SESSION['successMessageTable'] = "Zařízení s ID $deleteDeviceId bylo úspěšně smazáno.";
        header('Location: ' . $_SERVER['PHP_SELF']); 
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
<?php
if($currentRole == 'admin' || $currentRole == 'registered'):
?>
<div class="centered-form">
<h2>Vytvořit nové zařízení</h2>
    <?php
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
        <p id="successMessage" style="color: green;"></p> 

    </form>
</div>

<?php
        if (isset($_SESSION['successMessage'])) {
            echo '<p style="color: green;">' . $_SESSION['successMessage'] . '</p>';
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
            <th>Smazat</th> 
            <th>Upravit</th> 
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

    if (isset($_SESSION['successMessageTable'])) {
        echo '<p id="successMessageTable" style="color: green;">' . $_SESSION['successMessageTable'] . '</p>';
       
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
                <td><?= $row['device_name'] ?></td>
                <td><?= $row['device_type'] ?></td>
                <td><?= $row['device_description'] ?></td>
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