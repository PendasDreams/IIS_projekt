<?php
session_start();

// Funkce pro odhlášení uživatele
function logoutUser() {
    session_unset(); 
    session_destroy(); 
    header('Location: index.html'); 
    exit(); 
}


include_once("connect.php");
$db = mysqli_init();
pripojit();


$currentUsername = isset($_SESSION['username']) ? $_SESSION['username'] : null;
$currentRole = isset($_SESSION['role']) ? $_SESSION['role'] : null;


if (isset($_POST['logout'])) {
    logoutUser();
}
?>

<html>
<head>
    <title>Přihlášení</title>
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

<?php if ($currentRole === 'admin' || $currentRole === 'registered') : ?>
    <div class="centered-buttons">
        <h2><a href="editusers.php">Uživatelé</a></h2>
        <?php
            if ($currentRole === 'admin'){
                echo '<div>Informace o uživatelých a jejich správa systému</div>';
            }else{
                echo '<div>Vám dostupné informace o uživatelých Jméno a Role </div>';
            }
        ?>
        
        <h2><a href="system.php">Systémy</a></h2> 
        <div>Vám dostupné informace o systémech Jméno, Popis, Admin a správa systému</div>
        <h2><a href="devices.php">Zařízení</a></h2> 
        <div>Vám dostupné informace o zařízeních Jméno, Popis, Alias, Typ, Jednotka, Interval údržby a správa zařízení</div>
    </div>
<?php endif; ?>


<?php if ($currentRole !== 'admin' && $currentRole !== 'registered') : ?>
    <div>Dobrý den, jste přihlášený jako host můžete si prohlížet zakladní informace o systémech a zařízeních</div>
    <div class="centered-buttons">
        <h2><a href="system.php">Systémy</a></h2>
        <div>Vám dostupné informace o systémech Jméno, Popis, Admin</div>
        <h2><a href="devices.php">Zařízení</a></h2>
        <div>Vám dostupné informace o zařízeních Jméno, Typ, Popis</div>
    </div>
<?php endif; ?>

</div>



</body>
</html>

<?php
mysqli_close($db);
?>
