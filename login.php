<?php
// Připojení k databázi
$db = mysqli_init();
if (!mysqli_real_connect($db, 'localhost', 'xnovos14', 'inbon8uj', 'xnovos14', 0, '/var/run/mysql/mysql.sock')) {
    die('Nelze se připojit k databázi: ' . mysqli_connect_error());
}

// Dotaz pro vytvoření tabulky users (stejný dotaz jako ve vašem kódu, pokud tabulka neexistuje)
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



// Zkontrolujte, zda uživatel "user" již existuje
$query = "SELECT * FROM users WHERE username='user'";
$result = mysqli_query($db, $query);

if (!$result) {
    die('Chyba dotazu: ' . mysqli_error($db));
}

// Pokud uživatel "user" neexistuje, vložte jej do databáze
if (mysqli_num_rows($result) == 0) {
    $username = 'user';
    $password = 'heslo';

    // Ochrana před SQL injection
    $username = mysqli_real_escape_string($db, $username);
    $password = mysqli_real_escape_string($db, $password);

    // Dotaz pro vložení uživatele
    $insertQuery = "INSERT INTO users (username, password) VALUES ('$username', '$password')";

    if (mysqli_query($db, $insertQuery)) {
        echo "Uživatel 'user' s heslem 'heslo' byl úspěšně vložen do databáze.<br>";
    } else {
        die('Chyba při vkládání uživatele: ' . mysqli_error($db));
    }
} else {
    echo "Uživatel 'user' již existuje v databázi.<br>";
}

// Získání hodnot z formuláře (pokud byl formulář odeslán)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Ochrana před SQL injection
    $username = mysqli_real_escape_string($db, $username);
    $password = mysqli_real_escape_string($db, $password);

    // Dotaz pro ověření uživatele
    $query = "SELECT * FROM users WHERE username='$username' AND password='$password'";

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
}

// Výpis všech uživatelů (pokud bylo heslo zadáno správně a přihlášení proběhlo)
if (isset($_SESSION['username'])) {
    echo 'Vítejte, ' . $_SESSION['username'] . '!';

    // Můžete zde zobrazit další obsah pro přihlášeného uživatele
}

// Dotaz pro výpis všech uživatelů
$query = "SELECT * FROM users";

$result = mysqli_query($db, $query);

if (!$result) {
    die('Chyba dotazu: ' . mysqli_error($db));
}

// Výpis tabulky uživatelů
echo '<h2>Seznam uživatelů</h2>';
echo '<table>';
echo '<tr><th>ID</th><th>Uživatelské jméno</th><th>Heslo</th></tr>';

while ($row = mysqli_fetch_assoc($result)) {
    echo '<tr>';
    echo '<td>' . $row['id'] . '</td>';
    echo '<td>' . $row['username'] . '</td>';
    echo '<td>' . $row['password'] . '</td>';
    echo '</tr>';
}

echo '</table>';

mysqli_close($db);
?>
