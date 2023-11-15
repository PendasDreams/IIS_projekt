<?php
session_start();

// Připojení k databázi
$db = mysqli_init();
if (!mysqli_real_connect($db, 'localhost', 'xnovos14', 'inbon8uj', 'xnovos14', 0, '/var/run/mysql/mysql.sock')) {
    die('Nelze se připojit k databázi: ' . mysqli_connect_error());
}

// Získání informací o uživateli na základě editUserId
if (isset($_GET['editUserId'])) {
    $editUserId = mysqli_real_escape_string($db, $_GET['editUserId']);
    $query = "SELECT * FROM users WHERE id = '$editUserId'";
    $result = mysqli_query($db, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
    } else {
        // Uživatel nebyl nalezen
        die('Uživatel nebyl nalezen.');
    }
}

// Zpracování formuláře pro úpravu uživatele
if (isset($_POST['editUser'])) {
    $editedUsername = mysqli_real_escape_string($db, $_POST['editedUsername']);
    $editedPassword = mysqli_real_escape_string($db, $_POST['editedPassword']);
    $editedRole = mysqli_real_escape_string($db, $_POST['editedRole']);

    // Aktualizace informací o uživateli v databázi
    $updateQuery = "UPDATE users SET username = '$editedUsername', password = '$editedPassword', role = '$editedRole' WHERE id = '$editUserId'";
    $updateResult = mysqli_query($db, $updateQuery);

    if ($updateResult) {
        echo "Uživatel byl úspěšně upraven.";
        header('Location: welcome.php'); // Přesměrování zpět na seznam uživatelů po úpravě
        exit();
    } else {
        echo 'Chyba při úpravě uživatele: ' . mysqli_error($db);
    }
}
?>

<html>
<head>
    <title>Úprava uživatele</title>
</head>
<body>


<h2>Úprava uživatele</h2>
<form class="user-form" method="POST" action="">
    <div class="form-group">
        <label for="editedUsername">Uživatelské jméno:</label>
        <input type="text" id="editedUsername" name="editedUsername" required value="<?= $user['username'] ?>">
    </div>

    <div class="form-group">
        <label for="editedPassword">Heslo:</label>
        <input type="text" id="editedPassword" name="editedPassword" required value="<?= $user['password'] ?>">
    </div>

    <div class="form-group">
        <label for="editedRole">Role:</label>
        <input type="text" id="editedRole" name="editedRole" required value="<?= $user['role'] ?>">
    </div>

    <div class="form-group">
        <button class="btn-submit" type="submit" name="editUser">Uložit změny</button>
    </div>
</form>
</body>
</html>

<?php
mysqli_close($db);
?>
