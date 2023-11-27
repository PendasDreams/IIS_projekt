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

// Odhlášení uživatele po kliknutí na tlačítko odhlášení
if (isset($_POST['logout'])) {
    logoutUser();
}

// Připojení k databázi
include_once("connect.php");
$db = mysqli_init();
pripojit();

// SQL dotaz pro získání dat ze tabulky "devices"
$selectQuery = "SELECT k.id as id, d.device_name as DName, k.val as KVal, c.typ as comp, d.hodnota as DVal FROM KPI as k, devices as d, compare as c WHERE k.device_id = d.id AND k.typ = c.id AND k.system_id = '$systemID'";

// Vykonání dotazu
$result = mysqli_query($db, $selectQuery);

if (!$result) {
    die('Chyba dotazu: ' . mysqli_error($db));
}

$selectQuery2 = "SELECT d.id as id, d.device_name as device FROM devices as d, system_devices as sd WHERE sd.device_id = d.id AND sd.system_id = '$systemID'";

$deviceResult = mysqli_query($db, $selectQuery2);

$successMessage = ''; // Inicializace proměnné pro úspěšnou zprávu prázdnou hodnotou
$errorMessage = ''; // Inicializace proměnné pro chybovou zprávu prázdnou hodnotou

if (isset($_POST['createKPI'])) {
    $newDevID = $_POST['device'];
    $newVal = $_POST['value'];
    $newComp = $_POST['compType'];
    // Kontrola, zda jsou datové typy správné pro pole hodnota a maintenance_interval
    if (!is_numeric($newVal)){
        $errorMessage = "Chyba: Pole 'Hodnota' a 'Interval údržby' musí být číselné hodnoty.";
    } else {
        // Pokud jsou datové typy správné, provedeme vložení do databáze
        $insertQuery = "INSERT INTO KPI (val, device_id, system_id, typ)
                        VALUES ('$newVal', '$newDevID', '$systemID','$newComp')";

        if (mysqli_query($db, $insertQuery)) {
            $_SESSION['successMessage'] = "KPI bylo úspěšně vytvořeno.";
            header('Location: kpi.php'); // Přesměrování na stránku se seznamem zařízení
        } else {
            $errorMessage = "Zařízení se nepodařilo vytvořit.";
        }
    }
}

// Zpracování formuláře pro mazání KPI

if (isset($_POST['deleteKPI'])) {
    $deleteKPIId = mysqli_real_escape_string($db, $_POST['deleteKPIId']);

    // SQL dotaz pro smazání zařízení z databáze
    $deleteKPIQuery = "DELETE FROM KPI WHERE id = '$deleteKPIId'";

    if (mysqli_query($db, $deleteKPIQuery)) {
        $_SESSION['successMessageTable'] = "KPI s ID $deleteKPIId bylo úspěšně smazáno.";
        header('Location: ' . $_SERVER['PHP_SELF']); // Přesměrování na stejnou stránku pro aktualizaci tabulky
    } else {
        echo 'Chyba při mazání zařízení: ' . mysqli_error($db);
    }
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Přehled KPI</title>
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
?>
<?php if ($adminID == $currentID): ?>
    <div class="centered-form">
    <h2>Vytvořit Kličový indentifikátor výkonu (KPI)</h2>
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
            <label for="device">Zařízení</label>
            <select name="device" id="device">
            <?php while ($row = mysqli_fetch_assoc($deviceResult)): 
                echo '<option value="'. $row['id'] . '">'.$row['device'].'</option>';
            endwhile; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="value">Hodnota:</label>
            <input type="number" step="0.1" id="value" name="value">
        </div>
        <div class="form-group">
            <label for="compType">Porovnání:</label>
            <select name="compType" id="compType">
                <option value="1">Equal</option>
                <option value="2">Not equal</option>
                <option value="3">Less than</option>
                <option value="4">Less than equal</option>
                <option value="5">Greater</option>
                <option value="6">Greater than equal</option>
            </select>
        </div>
        <div class="form-group">
            <button class="btn-submit" type="submit" name="createKPI">Vytvořit KPI</button>
        </div>
        <p id="successMessage" style="color: green;"></p> <!-- Výpis úspěšného vytvoření zařízení -->

        </form>
    </div>
<?php endif ?>

<?php
        if (isset($_SESSION['successMessage'])) {
            echo '<p style="color: green;">' . $_SESSION['successMessage'] . '</p>';
            // Po zobrazení zprávy ji vymažeme z session, aby se nezobrazovala znovu
            unset($_SESSION['successMessage']);
        }
    ?>


<div class="centered-form">
    <h2>Seznam KPI</h2>
    <table class="device-table">
        <tr>
            <th>Název zařízení</th>
            <th>Hodnota zařízení</th>
            <th>Porovnání</th>
            <th>Hodnota KPI</th>
            <th>Stav</th>
            <?php if ($adminID == $currentID):?>
            <th></th>
            <th></th>
            <?php endif; ?>
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

                <?php if ($adminID == $currentID):?>
                <td>
                    <form method="POST" action="">
                        <input type="hidden" name="deleteKPIId" value="<?= $row['id'] ?>">
                        <button class="delete-button" type="submit" name="deleteKPI">Smazat</button>
                    </form>
                </td>
                <td>
                    <form method="POST" action="edit_kpi.php" onsubmit="scrollToEditForm()">
                        <input type="hidden" name="editKPI" value="<?= $row['id'] ?>">
                        <button class="edit-button" type="submit" name="loadEditForm">Upravit</button>
                    </form>
                </td>
                <?php endif; ?>
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

</body>
</html>

<?php
endif;
mysqli_close($db);
?>