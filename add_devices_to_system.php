<?php
session_start();

// Funkce pro odhlášení uživatele
function logoutUser() {
    session_unset(); 
    session_destroy(); 
    header('Location: index.html'); 
    exit(); 
}

// Funkce pro přidání zařízení do systému
function addDeviceToSystem($systemId, $deviceId, $db) {
    $query = "INSERT INTO system_devices (system_id, device_id) VALUES ($systemId, $deviceId)";
    if (mysqli_query($db, $query)) {
        return true;
    } else {
        return false;
    }
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

$systemId = isset($_POST['addDeviceToSystem']) ? $_POST['addDeviceToSystem'] : null;



if ($systemId) {
    
    $query = "SELECT * FROM systems WHERE id = $systemId";
    $result = mysqli_query($db, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $systemData = mysqli_fetch_assoc($result);
        $systemName = $systemData['name'];
        $systemDescription = $systemData['description'];
        $systemAdminID = $systemData['admin_id'];
    } else {
        echo "Systém nebyl nalezen.";
    }
} else {
    
    echo "Chybějící ID systému.";
} 

// Zpracování formuláře pro přidání zařízení do systému
if (isset($_POST['add_to_system'])) {
    $deviceId = isset($_POST['device_id']) ? $_POST['device_id'] : null;
    
    if ($deviceId) {
        if (addDeviceToSystem($systemId, $deviceId, $db)) {
            
            echo "Zařízení bylo úspěšně přidáno do systému.";
        } else {
            
            echo "Chyba při přidávání zařízení do systému.";
        }
    } else {
        
        echo "Chybějící ID zařízení.";
    }
}

// Zpracování formuláře pro odebrání zařízení ze systému
if (isset($_POST['remove_from_system'])) {
    $deviceId = isset($_POST['device_id']) ? $_POST['device_id'] : null;
    $systemId = isset($_POST['system_id']) ? $_POST['system_id'] : null;

    if ($deviceId && $systemId) {
        $queryRemove = "DELETE FROM system_devices WHERE system_id = $systemId AND device_id = $deviceId";
        if (mysqli_query($db, $queryRemove)) {
            
            echo "Zařízení bylo úspěšně odebráno ze systému.";
            
            
            $query = "SELECT * FROM systems WHERE id = $systemId";
            $result = mysqli_query($db, $query);
            
            if ($result && mysqli_num_rows($result) > 0) {
                $systemData = mysqli_fetch_assoc($result);
                $systemName = $systemData['name'];
                $systemDescription = $systemData['description'];
                $systemAdminID = $systemData['admin_id'];
            }
        } else {
            
            echo "Chyba při odebírání zařízení ze systému: " . mysqli_error($db);
        }
    } else {
        
        echo "Chybějící ID zařízení nebo system_id.";
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

<div class="centered-form">
    <h2>Informace o systému</h2>

    <table>
        <tr>
            <th>ID systému</th>
            <th>Název systému</th>
            <th>Popis systému</th>
            <th>ID admina systému</th>
        </tr>
        <?php
       
        if (isset($systemId, $systemName, $systemDescription, $systemAdminID)) {
            echo "<tr>";
            echo "<td>{$systemId}</td>";
            echo "<td>{$systemName}</td>";
            echo "<td>{$systemDescription}</td>";
            echo "<td>{$systemAdminID}</td>";
            echo "</tr>";
        } else {
            
            echo "Informace o systému nejsou k dispozici.";
        }
        ?>
    </table>
</div>


<h2>Zařízení v systému</h2>
    <table>
        <tr>
            <th>ID zařízení</th>
            <th>Název zařízení</th>
            <th>Typ zařízení</th>
            <th>Popis zařízení</th>
            <th>Uživatelský alias</th>
            <th>Hodnota</th>
            <th>Jednotka</th>
            <th>Interval údržby (dny)</th>
            <th>Odebrat ze systému</th>
        </tr>
        <?php
        // Dotaz pro získání všech zařízení v systému
        $queryDevices = "SELECT * FROM devices WHERE id IN (SELECT device_id FROM system_devices WHERE system_id = $systemId)";
        $resultDevices = mysqli_query($db, $queryDevices);

        if ($resultDevices) {
            while ($deviceData = mysqli_fetch_assoc($resultDevices)) {
                echo "<tr>";
                echo "<td>{$deviceData['id']}</td>";
                echo "<td>{$deviceData['device_name']}</td>";
                echo "<td>{$deviceData['device_type']}</td>";
                echo "<td>{$deviceData['device_description']}</td>";
                echo "<td>{$deviceData['user_alias']}</td>";
                echo "<td>{$deviceData['hodnota']}</td>";
                echo "<td>{$deviceData['jednotka']}</td>";
                echo "<td>{$deviceData['maintenance_interval']}</td>";
                echo "<td>";
                
                echo "<form method='POST' action=''>";
                echo "<input type='hidden' name='device_id' value='{$deviceData['id']}'>";
                
                echo "<input type='hidden' name='system_id' value='{$systemId}'>";
                echo "<button class='delete-button' type='submit' name='remove_from_system'>Odebrat ze systému</button>";
                echo "</form>";
                echo "</td>";
                echo "</tr>";
            }
        }
        ?>
    </table>
</div>


<div class="centered-form">
    <h2>Výpis všech zařízení</h2>
    <table>
        <tr>
            <th>ID zařízení</th>
            <th>Název zařízení</th>
            <th>Typ zařízení</th>
            <th>Popis zařízení</th>
            <th>Uživatelský alias</th>
            <th>Hodnota</th>
            <th>Jednotka</th>
            <th>Interval údržby (dny)</th>
            <th>Akce</th>
        </tr>
        <?php
        // Dotaz pro získání všech zařízení
        $queryAllDevices = "SELECT * FROM devices";
        $resultAllDevices = mysqli_query($db, $queryAllDevices);

        if ($resultAllDevices) {
            while ($deviceData = mysqli_fetch_assoc($resultAllDevices)) {
                echo "<tr>";
                echo "<td>{$deviceData['id']}</td>";
                echo "<td>{$deviceData['device_name']}</td>";
                echo "<td>{$deviceData['device_type']}</td>";
                echo "<td>{$deviceData['device_description']}</td>";
                echo "<td>{$deviceData['user_alias']}</td>";
                echo "<td>{$deviceData['hodnota']}</td>";
                echo "<td>{$deviceData['jednotka']}</td>";
                echo "<td>{$deviceData['maintenance_interval']}</td>";
                echo "<td>";
                
                echo "<form method='POST' action=''>";
                echo "<input type='hidden' name='device_id' value='{$deviceData['id']}'>";
                echo "<input type='hidden' name='addDeviceToSystem' value='{$systemId}'>";
                echo "<button class='edit-button' type='submit' name='add_to_system'>Přidat do systému</button>";
                echo "</form>";
                echo "</td>";
                echo "</tr>";
            }
        }
        ?>
    </table>
</div>

</body>
</html>

<?php
mysqli_close($db);
?>
