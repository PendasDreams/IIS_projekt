
<?php
// Připojení k databázi
$db = mysqli_init();
if (!mysqli_real_connect($db, 'localhost', 'xnovos14', 'inbon8uj', 'xnovos14', 0, '/var/run/mysql/mysql.sock')) {
    die('Nelze se připojit k databázi: ' . mysqli_connect_error());
}


// // SQL příkaz pro odstranění tabulky users (pokud existuje)
// $dropTableQuery = "DROP TABLE IF EXISTS users";

// if (mysqli_query($db, $dropTableQuery)) {
//     echo "Tabulka 'users' byla úspěšně odstraněna, pokud existovala.<br>";
// } else {
//     die('Chyba při odstraňování tabulky: ' . mysqli_error($db));
// }



// SQL příkaz pro vytvoření tabulky users (pokud neexistuje)
$createTableQuery = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL
)";

if (mysqli_query($db, $createTableQuery)) {
    echo "Tabulka 'users' byla úspěšně vytvořena nebo již existuje.<br>";
} else {
    die('Chyba při vytváření tabulky: ' . mysqli_error($db));
}

$usersToInsert = [
    ['admin', 'admin', 'admin'],
    ['registered', 'registered', 'registered'],
    ['broker', 'broker', 'broker'],
];

foreach ($usersToInsert as $userData) {
    $username = $userData[0];
    $password = $userData[1];
    $role = $userData[2];

    // Zkontrolujte, zda uživatel s tímto jménem již existuje v databázi
    $checkQuery = "SELECT username FROM users WHERE username = '$username'";
    $checkResult = mysqli_query($db, $checkQuery);

    if (!$checkResult) {
        die('Chyba dotazu: ' . mysqli_error($db));
    }

    if (mysqli_num_rows($checkResult) === 0) {
        // Uživatel s tímto jménem neexistuje, můžeme jej vložit do databáze
        $insertQuery = "INSERT IGNORE INTO users (username, password, role) VALUES ('$username', '$password', '$role')";
        
        if (mysqli_query($db, $insertQuery)) {
            echo "Uživatel '$username' byl úspěšně vložen do databáze s rolí '$role'.<br>";
        } else {
            die('Chyba při vkládání uživatele: ' . mysqli_error($db));
        }
    } else {
        echo "Uživatel s jménem '$username' již existuje v databázi, takže nebyl znovu vložen.<br>";
    }
}


// Získání hodnot z formuláře (pokud byl formulář odeslán)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    session_start();

    // Ochrana před SQL injection
    $username = mysqli_real_escape_string($db, $username);
    $password = mysqli_real_escape_string($db, $password);

    if (isset($_POST['userLoginBtn'])) {
        // Pokud bylo stisknuto tlačítko "Přihlásit se jako registrovaný uživatel"
        // Dotaz pro ověření uživatele
        $query = "SELECT * FROM users WHERE BINARY username='$username' AND BINARY password='$password'";
    
        $result = mysqli_query($db, $query);
    
        if (!$result) {
            die('Chyba dotazu: ' . mysqli_error($db));
        }
    
        // Ověření, zda uživatel existuje
        if (mysqli_num_rows($result) == 1) {
            // Uživatel byl nalezen, můžete provést přihlášení
            $user = mysqli_fetch_assoc($result);
    
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $user['role']; // Přidejte roli do session
    
            // Logování do konzole
            echo "Uživatel '$username' se přihlásil jako registrovaný uživatel s rolí '{$user['role']}'.";
            header('Location: welcome.php'); // Přesměrování na uvítací stránku pro registrovaného uživatele
            exit(); // Ukončení provádění skriptu
        } else {
            $query = "SELECT * FROM users";

                $result = mysqli_query($db, $query);

                if (!$result) {
                    die('Chyba dotazu: ' . mysqli_error($db));
                }

                // Výpis tabulky uživatelů
                echo '<h2>Seznam uživatelů</h2>';
                echo '<table>';
                echo '<tr><th>ID</th><th>Uživatelské jméno</th><th>Heslo</th><th>Role</th></tr>';

                while ($row = mysqli_fetch_assoc($result)) {
                    echo '<tr>';
                    echo '<td>' . $row['id'] . '</td>';
                    echo '<td>' . $row['username'] . '</td>';
                    echo '<td>' . $row['password'] . '</td>';
                    echo '<td>' . $row['role'] . '</td>';
                    echo '</tr>';
                }

                echo '</table>';
        }
    } elseif (isset($_POST['guestLoginBtn'])) {
        // Pokud bylo stisknuto tlačítko "Přihlásit se jako Host"
        // Vytvoření uživatele s rolí "guest" do databáze
        $role = 'guest';
        $username = 'guest';
    
        // Vložení uživatele s rolí "guest" do databáze
        $insertQuery = "INSERT INTO users (username, password, role) VALUES ('$username', '$password', '$role')";
    
        if (mysqli_query($db, $insertQuery)) {
            echo "Uživatel '$username' s rolí 'guest' byl úspěšně vložen do databáze.<br>";
    
            $_SESSION['username'] = $username;
            $_SESSION['role'] = 'guest';

            // Logování do konzole
            echo "Uživatel '$username' se přihlásil jako host.";
            header('Location: welcome.php'); // Přesměrování na uvítací stránku pro registrovaného uživatele
        } else {
            die('Chyba při vkládání uživatele: ' . mysqli_error($db));
        }
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
echo '<tr><th>ID</th><th>Uživatelské jméno</th><th>Heslo</th><th>Role</th></tr>';

while ($row = mysqli_fetch_assoc($result)) {
    echo '<tr>';
    echo '<td>' . $row['id'] . '</td>';
    echo '<td>' . $row['username'] . '</td>';
    echo '<td>' . $row['password'] . '</td>';
    echo '<td>' . $row['role'] . '</td>';
    echo '</tr>';
}

echo '</table>';

mysqli_close($db);
?>