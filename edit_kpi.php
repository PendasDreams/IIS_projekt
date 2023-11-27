<?php
session_start();

function logoutUser() {
    session_unset(); // Vyprázdnění všech session proměnných
    session_destroy(); // Zničení session
    header('Location: index.html'); // Přesměrování na index.html
    exit(); // Zastavení běhu skriptu
}

$currentUsername = isset($_SESSION['username']) ? $_SESSION['username'] : null;
$currentRole = isset($_SESSION['role']) ? $_SESSION['role'] : null;
$currentID = isset($_SESSION['userID']) ? $_SESSION['userID'] : null;

if (isset($_POST['KPI'])){
    $_SESSION['adminID'] = $_POST['KPI'];
}
$adminID = isset($_SESSION['adminID']) ? $_SESSION['adminID'] : null;

if (isset($_POST['systemID'])){
    $_SESSION['systemID'] = $_POST['systemID'];
}
$systemID = isset($_SESSION['systemID']) ? $_SESSION['systemID'] : null;

if (isset($_POST['editKPI'])){
    $_SESSION['editKPI'] = $_POST['editKPI'];
}
$editKPI = isset($_SESSION['editKPI']) ? $_SESSION['editKPI'] : null;

// Odhlášení uživatele po kliknutí na tlačítko odhlášení
if (isset($_POST['logout'])) {
    logoutUser();
}

// Připojení k databázi
include_once("connect.php");
$db = mysqli_init();
pripojit();


// SQL dotaz pro získání dat ohledně KPI
$selectQuery = "SELECT k.id as id, d.device_name as DName, k.val as KVal, c.typ as comp, d.hodnota as DVal FROM KPI as k, devices as d, compare as c 
    WHERE k.device_id = d.id AND k.typ = c.id AND k.system_id = '$systemID' AND k.id = '$editKPI'";

// Vykonání dotazu
$result = mysqli_query($db, $selectQuery);
if (!$result) {
    die('Chyba dotazu: ' . mysqli_error($db));
}
// SQL dotaz pro detailnějších dat KPI
$selectQuery2 = "SELECT d.id as id, d.device_name as device FROM devices as d, system_devices as sd 
    WHERE sd.device_id = d.id AND sd.system_id = '$systemID'";
$deviceResult = mysqli_query($db, $selectQuery2);
if (!$deviceResult) {
    die('Chyba dotazu: ' . mysqli_error($db));
}
$selectQuery3 = "SELECT k.id as id, d.id as devID, d.device_name as DName, k.val as KVal, c.id AS compID,c.typ as comp FROM KPI as k, devices as d, compare as c 
    WHERE k.device_id = d.id AND k.typ = c.id AND k.system_id = '$systemID' AND k.id = '$editKPI'";
$updateResult = mysqli_query($db, $selectQuery3);
if (!$updateResult) {
    die('Chyba dotazu: ' . mysqli_error($db));
}

$successMessage = ''; // Inicializace proměnné pro úspěšnou zprávu prázdnou hodnotou
$errorMessage = ''; // Inicializace proměnné pro chybovou zprávu prázdnou hodnotou

if (isset($_POST['save'])) {
    $test = mysqli_fetch_assoc($updateResult);
    if ($test) {
    $editedName = isset($_POST['device']) ? $_POST['device'] : $test['devID'];
    
    $editedValue = isset($_POST['newVal']) ? $_POST['newVal'] : null;
    if($editedValue == "" || $editedValue == NULL) {
        $editedValue = $test['KVal'];
    }else{
        $editedValue = floatval($editedValue);
    }

    $editedCompType = isset($_POST['newType']) ? $_POST['newType'] : null;
    if($editedCompType == "" || $editedCompType == NULL) {
        $editedCompType = $test['compID'];
    }
    }else{
        $errorMessage = 'Chyba hledaní řádku tabulky';
    }

    $editKPIQuery = "UPDATE KPI SET val = '$editedValue', device_id = '$editedName', typ = '$editedCompType'
        WHERE id = '$editKPI'";
    if (mysqli_query($db, $editKPIQuery)) {
        echo "KPI s ID $editKPI byl úspěšně upraven.";
        header('Location: kpi.php'); // Přesměrování na stránku se seznamem KPI
    } else {
                $errorMessage = 'Chyba při úpravě uživatele: ' . mysqli_error($db);
            }
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Upravit KPI</title>
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

<?php
if ($currentRole == "guest"):
    echo '<div>Nastala chyba</div>';
    echo '<div>Tato stránka by neměla být přístupná pro hosty</div>';
    echo '</body>';
    echo '</html>';
else:
        if (isset($_SESSION['successMessage'])) {
            echo '<p style="color: green;">' . $_SESSION['successMessage'] . '</p>';
            // Po zobrazení zprávy ji vymažeme z session, aby se nezobrazovala znovu
            unset($_SESSION['successMessage']);
        }
?>

<div class="centered-form">
    <h2>Současné nastavení KPI</h2>
    <table class="device-table">
        <tr>
            <th>Název zařízení</th>
            <th>Hodnota zařízení</th>
            <th>Porovnání</th>
            <th>Hodnota KPI</th>
            <th>Stav</th>
        </tr>

        <?php while ($row = mysqli_fetch_assoc($result)) : ?>
            <tr>
                <td><?= $row['DName'] ?></td>
                <td><?= $row['DVal'] ?></td>
                <td><?= $row['comp'] ?></td>
                <td><?= $row['KVal'] ?></td>
                <td>
                    <?php
                        $allowedOperators = ["<", ">", "<=", ">=", "==", "!="];
                        if (!in_array($row['comp'], $allowedOperators)) {
                            die("Invalid comparison operator");
                        }
                        $comparisonString = "\$compared = (\$row['DVal']" . $row['comp'] . "\$row['KVal']);";
                        eval($comparisonString);
                        if ($compared) {
                            echo "OK";
                        } else {
                            echo "ERROR";
                        }
                    ?>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
</div>
<div class = "centered-buttons">
<h2>Upravit KPI</h2>
<form class="user-form" method="POST" action="">
    <div class="form-group">
    <label for="newName">Změnit zařízení</label>
            <select name="newName" id="newName">
                <option value="">--vyberte zařízení--</option>
            <?php while ($row = mysqli_fetch_assoc($deviceResult)): 
                echo '<option value="'. $row['id'] . '">'.$row['device'].'</option>';
            endwhile; ?>
            </select>
    </div>
    <div class="form-group">
        <label for="newVal">Změnit Hodotu KPI:</label>
        <input type="number" step="0.1" id="newVal" name="newVal">
    </div>
    <div class="form-group">
        <label for="newType"></label>
        <select name="newType" id="newType">
            <option value="">--vyberte zařízení--</option>
            <option value="1">Equal</option>
            <option value="2">Not equal</option>
            <option value="3">Less than</option>
            <option value="4">Less than equal</option>
            <option value="5">Greater</option>
            <option value="6">Greater than equal</option>
        </select>
    </div>
    <div class="form-group">
        <button class="btn-submit" type="submit" name="save">Uložit změny</button>
    </div>
</form>
</div>

</body>
</html>

<?php
endif;
mysqli_close($db);
?>