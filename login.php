<?php
// Připojení k databázi
$db = mysqli_init();
if (!mysqli_real_connect($db, 'localhost', 'xdohna52', 'vemsohu6', 'xdohna52', 0, '/var/run/mysql/mysql.sock')) {
    die('Nelze se připojit k databázi: ' . mysqli_connect_error());
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
        $query = "SELECT u.id, u.username, u.password, r.role FROM users as u, roles as r WHERE BINARY username='$username' AND BINARY password='$password' AND r.id=u.role";
    
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
            $_SESSION['userID'] = $user['id']; 
    
            // Logování do konzole
            echo "Uživatel '$username' se přihlásil jako registrovaný uživatel s rolí '{$user['role']}'.";
            header('Location: welcome.php'); // Přesměrování na uvítací stránku pro registrovaného uživatele
            exit(); // Ukončení provádění skriptu
        } else {
            // Vytvoření chybové zprávy
            $error_message = 'Nesprávné uživatelské jméno nebo heslo.';
    
            // JavaScript pro zobrazení chybového okna s tlačítkem pro návrat na index.html
            echo '<script>';
            echo 'alert("' . $error_message . '");'; // Zobrazíme chybovou zprávu
            echo 'window.location.href = "index.html";'; // Přesměrování na index.html
            echo '</script>';
        }
    } elseif (isset($_POST['guestLoginBtn'])) {
        // Pokud bylo stisknuto tlačítko "Přihlásit se jako Host"
        // Vytvoření uživatele s rolí "guest" do databáze
        $role = 'guest';
        $username = 'guest';

        $_SESSION['username'] = $username;
        $_SESSION['role'] = 'guest';
        $_SESSION['userID'] = 4; 

        // Logování do konzole
        echo "Uživatel '$username' se přihlásil jako host.";
        header('Location: welcome.php'); // Přesměrování na uvítací stránku pro registrovaného uživatele
    }
}


mysqli_close($db);
?>