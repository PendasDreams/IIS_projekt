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


$editSystemId = isset($_POST['editSystemId']) ? $_POST['editSystemId'] : null;
$editSystemName = isset($_POST['editSystemName']) ? $_POST['editSystemName'] : null;
$editSystemDescription = isset($_POST['editSystemDescription']) ? $_POST['editSystemDescription'] : null;

// Poté můžete tyto informace použít v formuláři pro úpravu systému


if (isset($_POST['updateSystem'])) {
    $editSystemId = isset($_POST['editSystemId']) ? $_POST['editSystemId'] : null;
    $editSystemName = isset($_POST['editSystemName']) ? mysqli_real_escape_string($db, $_POST['editSystemName']) : null;
    $editSystemDescription = isset($_POST['editSystemDescription']) ? mysqli_real_escape_string($db, $_POST['editSystemDescription']) : null;
    $editSystemAdminID = isset($_POST['editSystemAdminID']) ? $_POST['editSystemAdminID'] : null; // Nový admin_id

    // Aktualizovat informace o systému v databázi, včetně admin_id
    $updateQuery = "UPDATE systems SET name = '$editSystemName', description = '$editSystemDescription', admin_id = $editSystemAdminID WHERE id = $editSystemId";

    if (mysqli_query($db, $updateQuery)) {
        echo "Informace o systému byly úspěšně aktualizovány.";
    } else {
        echo 'Chyba při aktualizaci informací o systému: ' . mysqli_error($db);
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
<h2>Upravit zařízení</h2>


<form class="user-form" method="POST" action="edit_systems.php">
    <input type="hidden" name="editSystemId" value="<?= $editSystemId ?>">
    <div class="form-group">
        <label for="editSystemName">Název systému:</label>
        <input type="text" id="editSystemName" name="editSystemName" value="<?= $editSystemName ?>" required>
    </div>

    <div class="form-group">
        <label for="editSystemDescription">Popis systému:</label>
        <textarea id="editSystemDescription" name="editSystemDescription" required><?= $editSystemDescription ?></textarea>
    </div>

    <div class="form-group">
        <label for="editSystemAdminID">ID admina systému:</label>
        <input type="number" id="editSystemAdminID" name="editSystemAdminID" value="<?= $editSystemAdminID ?>" required>
    </div>

    <div class="form-group">
        <button class="btn-submit" type="submit" name="updateSystem">Uložit změny</button>
    </div>
</form>

   
</div>



</body>
</html>

<?php
mysqli_close($db);
?>