<?php
session_start();

// Připojení k databázi
$db = mysqli_init();
if (!mysqli_real_connect($db, 'localhost', 'xnovos14', 'inbon8uj', 'xnovos14', 0, '/var/run/mysql/mysql.sock')) {
    die('Nelze se připojit k databázi: ' . mysqli_connect_error());
}

// Dotaz pro výpis aktuálně přihlášeného uživatele
$currentUsername = isset($_SESSION['username']) ? $_SESSION['username'] : null;
$currentRole = isset($_SESSION['role']) ? $_SESSION['role'] : null;

?>

<html>
<head>
    <title>Přihlášení</title>
    <link rel="stylesheet" type="text/css" href="welcome_style.css"> <!-- Import stylů z externího souboru -->
</head>
<body>
    <div class="user-bar">
        <?php if ($currentUsername) : ?>
            Přihlášený uživatel: <?= $currentUsername ?>
        <?php else : ?>
            Není žádný uživatel přihlášen.
        <?php endif; ?>
    </div>
</body>
</html>

<?php
mysqli_close($db);
?>
