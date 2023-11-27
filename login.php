<?php
// Připojení k databázi
include_once("connect.php");
$db = mysqli_init();
pripojit();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    session_start();

    $username = mysqli_real_escape_string($db, $username);
    $password = mysqli_real_escape_string($db, $password);

    if (isset($_POST['userLoginBtn'])) {

        $query = "SELECT u.id, u.username, u.password, r.role FROM users as u, roles as r WHERE BINARY username='$username' AND BINARY password='$password' AND r.id=u.role";
    
        $result = mysqli_query($db, $query);
    
        if (!$result) {
            die('Chyba dotazu: ' . mysqli_error($db));
        }
    
        if (mysqli_num_rows($result) == 1) {
            
            $user = mysqli_fetch_assoc($result);
    
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $user['role']; 
            $_SESSION['userID'] = $user['id']; 
    
            echo "Uživatel '$username' se přihlásil jako registrovaný uživatel s rolí '{$user['role']}'.";
            header('Location: welcome.php');
            exit(); 
        } else {
            
            $error_message = 'Nesprávné uživatelské jméno nebo heslo.';
    
            echo '<script>';
            echo 'alert("' . $error_message . '");'; 
            echo 'window.location.href = "index.html";'; 
            echo '</script>';
        }
    } elseif (isset($_POST['guestLoginBtn'])) {
       
        $role = 'guest';
        $username = 'guest';

        $_SESSION['username'] = $username;
        $_SESSION['role'] = 'guest';
        $_SESSION['userID'] = 4; 

        
        echo "Uživatel '$username' se přihlásil jako host.";
        header('Location: welcome.php');
    }
}


mysqli_close($db);
?>