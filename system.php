<?php
session_start();

// Dotaz pro odstranění tabulky systems (pokud existuje)
$dropTableQuery = "DROP TABLE IF EXISTS systems";



// Funkce pro odhlášení uživatele
function logoutUser() {
    session_unset(); // Vyprázdnění všech session proměnných
    session_destroy(); // Zničení session
    header('Location: index.html'); // Přesměrování na index.html
    exit(); // Zastavení běhu skriptu
}

function systemExists($db, $systemName) {
    $systemName = mysqli_real_escape_string($db, $systemName);
    $query = "SELECT COUNT(*) as count FROM systems WHERE name = '$systemName'";
    $result = mysqli_query($db, $query);

    if (!$result) {
        die('Chyba dotazu: ' . mysqli_error($db));
    }

    $row = mysqli_fetch_assoc($result);
    return $row['count'] > 0;
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

?>
<html>
<head>
    <title>Přihlášení</title>
    <link rel="stylesheet" type="text/css" href="welcome_style.css"> <!-- Import stylů z externího souboru -->
    <link rel="stylesheet" type="text/css" href="styles.css"> <!-- Import stylů z externího souboru -->
</head>
<body>
<div class="user-bar">

<a href="editusers.php" class="system-button">Uživatelé</a>

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



</body>
</html>

<?php

if (mysqli_query($db, $dropTableQuery)) {
    echo "Tabulka systems byla úspěšně odstraněna.<br>";
} else {
    echo 'Chyba při odstraňování tabulky systems: ' . mysqli_error($db) . "<br>";
}

// Vytvoření tabulky systems (pokud ještě neexistuje)
$createTableQuery = "CREATE TABLE IF NOT EXISTS systems (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    admin_id INT
)";



if (mysqli_query($db, $createTableQuery)) {
    echo "Tabulka systems byla úspěšně vytvořena nebo již existuje.<br>";
} else {
    echo 'Chyba při vytváření tabulky systems: ' . mysqli_error($db) . "<br>";
}

// Vložení informací do tabulky systems
$newSystemName = 'Nový systém'; // Změňte na jméno systému, které se má vložit
$newSystemDescription = 'Popis nového systému'; // Změňte na popis nového systému
$newSystemAdminID = 1; // Změňte na ID admina systému

if (!systemExists($db, $newSystemName)) {
    $insertQuery = "INSERT INTO systems (name, description, admin_id) VALUES ('$newSystemName', '$newSystemDescription', $newSystemAdminID)";

    if (mysqli_query($db, $insertQuery)) {
        echo "Informace byly úspěšně vloženy do tabulky systems.<br>";
    } else {
        echo 'Chyba při vkládání informací do tabulky systems: ' . mysqli_error($db) . "<br>";
    }
} else {
    echo "Systém s názvem '$newSystemName' již existuje.<br>";
}

// Výpis obsahu tabulky systems
$query = "SELECT * FROM systems";

$result = mysqli_query($db, $query);

if (!$result) {
    die('Chyba dotazu: ' . mysqli_error($db));
}

echo "<h2>Obsah tabulky Systems</h2>";
echo "<table border='1'>";
echo "<tr><th>ID</th><th>Název systému</th><th>Popis</th><th>ID admina</th></tr>";

while ($row = mysqli_fetch_assoc($result)) {
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . $row['name'] . "</td>";
    echo "<td>" . $row['description'] . "</td>";
    echo "<td>" . $row['admin_id'] . "</td>";
    echo "</tr>";
}

echo "</table>";

mysqli_close($db);
?>
