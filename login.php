<?php
// Připojení k databázi
$db = mysqli_init();
if (!mysqli_real_connect($db, 'localhost', 'xnovos14', 'inbon8uj', 'xnovos14', 0, '/var/run/mysql/mysql.sock')) {
    die('Nelze se připojit k databázi: ' . mysqli_connect_error());
}

// Dotaz pro vytvoření tabulky users (pokud neexistuje)
$createTableQuery = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL
)";

if (mysqli_query($db, $createTableQuery)) {
    echo "Tabulka 'users' byla úspěšně vytvořena nebo již existuje.<br>";
} else {
    die('Chyba při vytváření tabulky: ' . mysqli_error($db));
}

// Získání hodnot z formuláře (pokud byl formulář odeslán)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Ochrana před SQL injection
    $username = mysqli_real_escape_string($db, $username);
    $password = mysqli_real_escape_string($db, $password);

    // Dotaz pro vkládání nového uživatele
    $insertQuery = "INSERT INTO users (username, password) VALUES ('$username', SHA1('$password'))";

    if (mysqli_query($db, $insertQuery)) {
        echo "Uživatel byl úspěšně vložen do databáze.<br>";
    } else {
        die('Chyba při vkládání uživatele: ' . mysqli_error($db));
    }
}

// Dotaz pro ověření uživatele
$query = "SELECT * FROM users WHERE username='$username' AND password=SHA1('$password')";

$result = mysqli_query($db, $query);

if (!$result) {
    die('Chyba dotazu: ' . mysqli_error($db));
}

// Ověření, zda uživatel existuje
if (mysqli_num_rows($result) == 1) {
    // Uživatel byl nalezen, můžete provést přihlášení
    session_start();
    $_SESSION['username'] = $username;
    header('Location: welcome.php'); // Přesměrování na uvítací stránku
} else {
    echo 'Nesprávné uživatelské jméno nebo heslo.';
}

mysqli_close($db);
?>
