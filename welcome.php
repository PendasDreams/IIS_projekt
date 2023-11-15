<?php
session_start();

// Funkce pro odhlášení uživatele
function logoutUser() {
    session_unset(); // Vyprázdnění všech session proměnných
    session_destroy(); // Zničení session
    header('Location: index.html'); // Přesměrování na index.html
    exit(); // Zastavení běhu skriptu
}

// Připojení k databázi
$db = mysqli_init();
if (!mysqli_real_connect($db, 'localhost', 'xnovos14', 'inbon8uj', 'xnovos14', 0, '/var/run/mysql/mysql.sock')) {
    die('Nelze se připojit k databázi: ' . mysqli_connect_error());
}

// Dotaz pro výpis aktuálně přihlášeného uživatele
$currentUsername = isset($_SESSION['username']) ? $_SESSION['username'] : null;
$currentRole = isset($_SESSION['role']) ? $_SESSION['role'] : null;

// Odhlášení uživatele po kliknutí na tlačítko odhlášení
if (isset($_POST['logout'])) {
    logoutUser();
}
?>

<html>
<head>
    <title>Přihlášení</title>
    <link rel="stylesheet" type="text/css" href="welcome_style.css"> <!-- Import stylů z externího souboru -->
    <link rel="stylesheet" type="text/css" href="styles.css"> <!-- Import stylů z externího souboru -->
</head>
<body>
<div class="user-bar">
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

<?php if ($currentRole === 'admin') : ?>
    <!-- Zobrazit nadpisy pouze pro uživatele s rolí "admin" -->
    <div class="centered-buttons">
        <h2><a href="editusers.php">Uživatelé</a></h2> <!-- Nadpis "Systémy" jako odkaz na system.php -->
        <!-- Zde můžete přidat obsah pro sekci "Systémy" -->
        
        <h2><a href="system.php">Systémy</a></h2> <!-- Nadpis pro sekci "Uživatelé" -->
    </div>
<?php endif; ?>

<!-- ... (váš stávající kód) ... -->

<?php if ($currentRole !== 'admin') : ?>
    <!-- Zobrazit nadpisy pouze pro uživatele s rolí "admin" -->
    <div class="centered-buttons">
        <h2>Systémy</h2> <!-- Nadpis pro sekci "Systémy" -->
        <!-- Zde můžete přidat obsah pro sekci "Systémy" -->
    </div>
<?php endif; ?>

</div>



</body>
</html>

<?php
mysqli_close($db);
?>
