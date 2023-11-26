<?php
session_start();


// Připojení k databázi
$db = mysqli_init();
if (!mysqli_real_connect($db, 'localhost', 'xdohna52', 'vemsohu6', 'xdohna52', 0, '/var/run/mysql/mysql.sock')) {
    die('Nelze se připojit k databázi: ' . mysqli_connect_error());
}

// Dotaz pro výpis aktuálně přihlášeného uživatele
$currentUsername = isset($_SESSION['username']) ? $_SESSION['username'] : null;
$currentRole = isset($_SESSION['role']) ? $_SESSION['role'] : null;

// Zpracování formuláře pro přidání uživatele
// Pole povolených rolí
$allowedRoles = array('admin', 'registered', 'broker', 'guest');

$errorMessage = ''; // Inicializace chybové zprávy prázdnou hodnotou

if (isset($_POST['addUser'])) {
    $newUsername = mysqli_real_escape_string($db, $_POST['newUsername']);
    $newPassword = mysqli_real_escape_string($db, $_POST['newPassword']);
    $newRole = mysqli_real_escape_string($db, $_POST['newRole']);

    // Kontrola, zda je zadaná role v seznamu povolených rolí
    if (in_array($newRole, $allowedRoles)) {
        // Kontrola, zda již existuje uživatel se stejným jménem
        $checkDuplicateQuery = "SELECT * FROM users WHERE username = '$newUsername'";
        $duplicateResult = mysqli_query($db, $checkDuplicateQuery);

        if (!$duplicateResult) {
            die('Chyba dotazu: ' . mysqli_error($db));
        }

        if (mysqli_num_rows($duplicateResult) == 0) {
            // Uživatel s tímto jménem neexistuje, můžeme ho přidat
            $insertQuery = "INSERT INTO users (username, password, role) VALUES ('$newUsername', '$newPassword', '$newRole')";
            $insertResult = mysqli_query($db, $insertQuery);

            if ($insertResult) {
                echo "Uživatel '$newUsername' byl úspěšně přidán.";
                header('Location: editusers.php'); // Přesměrování na stránku se seznamem uživatelů
            } else {
                $errorMessage = 'Chyba při přidávání uživatele: ' . mysqli_error($db);
            }
        } else {
            $errorMessage = "Uživatel se jménem '$newUsername' již existuje.";
        }
    } else {
        $errorMessage = 'Zadaná role není platná. Povolené role jsou: ' . implode(', ', $allowedRoles);
    }
}

if (isset($_POST['deleteUser'])) {
    $deleteUserId = mysqli_real_escape_string($db, $_POST['deleteUserId']);

    // SQL dotaz pro smazání uživatele z databáze
    $deleteUserQuery = "DELETE FROM users WHERE id = '$deleteUserId'";

    if (mysqli_query($db, $deleteUserQuery)) {
        echo "Uživatel s ID $deleteUserId byl úspěšně smazán.";
        header('Location: editusers.php'); // Přesměrování na stránku se seznamem uživatelů

    } else {
        echo 'Chyba při mazání uživatele: ' . mysqli_error($db);
    }
}


if (isset($_POST['loadEditForm'])) {
    // Pokud bylo stisknuto tlačítko "Upravit"
    $editUserId = mysqli_real_escape_string($db, $_POST['editUserId']);

    // Získání údajů o uživateli pro úpravu
    $query = "SELECT * FROM users WHERE id = '$editUserId'";
    $userResult = mysqli_query($db, $query);

    if ($userResult) {
        $user = mysqli_fetch_assoc($userResult);
    }
}

if (isset($_POST['editUser'])) {
    $editUserId = mysqli_real_escape_string($db, $_POST['editUserId']);
    $editedUsername = mysqli_real_escape_string($db, $_POST['editedUsername']);
    $editedPassword = mysqli_real_escape_string($db, $_POST['editedPassword']);
    $editedRole = mysqli_real_escape_string($db, $_POST['editedRole']);

    // Kontrola, zda je zadaná role v seznamu povolených rolí
    if (in_array($editedRole, $allowedRoles)) {
        // Kontrola, zda již existuje uživatel se stejným jménem
        $checkDuplicateQuery = "SELECT * FROM users WHERE username = '$editedUsername' AND id != '$editUserId'";
        $duplicateResult = mysqli_query($db, $checkDuplicateQuery);

        if (!$duplicateResult) {
            die('Chyba dotazu: ' . mysqli_error($db));
        }

        if (mysqli_num_rows($duplicateResult) == 0) {
            // Uživatel s tímto jménem neexistuje nebo je to tentýž uživatel, můžeme provést úpravu
            $editUserQuery = "UPDATE users SET username = '$editedUsername', password = '$editedPassword', role = '$editedRole' WHERE id = '$editUserId'";
            if (mysqli_query($db, $editUserQuery)) {
                echo "Uživatel s ID $editUserId byl úspěšně upraven.";
                header('Location: editusers.php'); // Přesměrování na stránku se seznamem uživatelů
            } else {
                $errorMessage = 'Chyba při úpravě uživatele: ' . mysqli_error($db);
            }
        } else {
            $errorMessage = "Uživatel se jménem '$editedUsername' již existuje.";
        }
    } else {
        $errorMessage = 'Zadaná role není platná. Povolené role jsou: ' . implode(', ', $allowedRoles);
    }
}

if (isset($_POST['logout'])) {
    // Zrušení všech session proměnných
    session_unset();
    // Zničení session
    session_destroy();
    // Přesměrování na index.html
    header('Location: index.html');
    exit();
}




?>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Zkontrolujeme, zda byl odeslán formulář
    var urlParams = new URLSearchParams(window.location.search);
    var submitted = urlParams.get("submitted");
    
    if (submitted) {
        // Počkejme nějaký čas, než se stránka znovunačte (např. 100 ms)
        setTimeout(function() {
            // Najdeme prvek formuláře úpravy uživatele
            var editForm = document.querySelector(".user-form");
            
            // Scrollujeme dolů na tento prvek
            if (editForm) {
                editForm.scrollIntoView({ behavior: "smooth", block: "start" });
            }
        }, 100);
    }
});
</script>


<html>
<head>
    <title>Přihlášení</title>
    <link rel="stylesheet" type="text/css" href="welcome_style.css"> <!-- Import stylů z externího souboru -->
    <link rel="stylesheet" type="text/css" href="styles.css"> <!-- Import stylů z externího souboru -->
</head>
<body>
<div class="user-bar">
    <!-- Tlačítko "Systémy" na levé straně -->
    <a href="welcome.php" class="system-button">Menu</a>
    <?php
    if ($currentRole != 'guest'){
        echo '<a href="editusers.php" class="system-button">Uživatelé</a>';
    }
    ?>
    <a href="system.php" class="system-button">Systémy</a>
    <a href="devices.php" class="system-button">Zařízení</a>
    <a href="manage_requests.php" class="system-button">Spravovat žádosti</a>
    <?php if ($currentUsername) : ?>
        <span class="user-info">Přihlášený uživatel:</span> <strong><?= $currentUsername ?></strong><br>
        <span class="user-info">Role:</span> <strong><?= $currentRole ?></strong>

        <!-- Tlačítko pro odhlášení -->
        <form method="POST" action="index.html"> <!-- Vytvořte stránku logout.php pro odhlášení uživatele -->
            <button type="submit" name="logout" class="logout-button">Odhlásit se</button>
        </form>
        
    <?php else : ?>
        <span class="user-info">Není žádný uživatel přihlášen.</span>
    <?php endif; ?>
</div>

<?php if ($currentRole === 'admin') : ?>
    <!-- Zobrazit nadpisy pouze pro uživatele s rolí "admin" -->
    <div class="centered-buttons">
        <?php
        // Dotaz pro výpis všech uživatelů
        $query = "SELECT u.id, u.username, u.password, r.role FROM users as u, roles as r WHERE u.role = r.id";
        $result = mysqli_query($db, $query);

        if (!$result) {
            die('Chyba dotazu: ' . mysqli_error($db));
        }
        ?>
            <table>
                <tr>
                    <th>Uživatelské jméno</th>
                    <th>Role</th>
                    <th>Smazat</th>
                    <th>Upravit</th> <!-- Přidán sloupec pro tlačítko "Upravit" -->
                </tr>
                <?php while ($row = mysqli_fetch_assoc($result)) : ?>
                    <tr>
                        <td><?= $row['username'] ?></td>
                        <td><?= $row['role'] ?></td>
                        <td>
                            <form method="POST" action="">
                                <input type="hidden" name="deleteUserId" value="<?= $row['id'] ?>">
                                <button class="delete-button" type="submit" name="deleteUser">Smazat</button>
                            </form>
                        </td>
                        <td>
                        <form method="POST" action="" onsubmit="scrollToEditForm()">
                            <input type="hidden" name="editUserId" value="<?= $row['id'] ?>">
                            <button class="edit-button" type="submit" name="loadEditForm">Upravit</button>
                        </form>
                        </td>

                    </tr>
                <?php endwhile; ?>
            </table>



            <?php if (!empty($errorMessage)) : ?>
                <div class="error-message">
                    <p><?= $errorMessage ?></p>
                </div>
            <?php endif; ?>

            <!-- Formulář pro úpravu uživatele -->
            <h2>Úprava uživatele</h2>
            <form class="user-form" method="POST" action="">
                <div class="form-group">
                    <label for="editedUsername">Uživatelské jméno:</label>
                    <input type="text" id="editedUsername" name="editedUsername" required value="<?= isset($user) ? $user['username'] : '' ?>">
                </div>

                <div class="form-group">
                    <label for="editedPassword">Heslo:</label>
                    <input type="password" id="editedPassword" name="editedPassword" required value="<?= isset($user) ? $user['password'] : '' ?>">
                </div>

                <div class="form-group">
                    <label for="editedRole">Role:</label>
                    <input type="text" id="editedRole" name="editedRole" required value="<?= isset($user) ? $user['role'] : '' ?>">
                </div>

                <!-- Skryté pole pro editovaného uživatele ID -->
                <input type="hidden" name="editUserId" value="<?= isset($user) ? $user['id'] : '' ?>">

                <div class="form-group">
                    <button class="btn-submit" type="submit" name="editUser">Uložit změny</button>
                </div>
            </form>


            <h2>Přidat uživatele</h2>
            <form class="user-form" method="POST" action="">
                <div class="form-group">
                    <label for="newUsername">Uživatelské jméno:</label>
                    <input type="text" id="newUsername" name="newUsername" required>
                </div>

                <div class="form-group">
                    <label for="newPassword">Heslo:</label>
                    <input type="password" id="newPassword" name="newPassword" required>
                </div>

                <div class="form-group">
                    <label for="newRole">Role:</label>
                    <input type="text" id="newRole" name="newRole" required>
                </div>

                <div class="form-group">
                    <button class="btn-submit" type="submit" name="addUser">Přidat uživatele</button>
                </div>
            </form>
    </div>

<?php 
    endif;
    if ($currentRole != 'admin') :
        if($currentRole == 'registered'):
            $query = "SELECT u.id, u.username, r.role FROM users as u, roles as r WHERE u.role = r.id";
            $result = mysqli_query($db, $query);
?>
    <div class="centered-buttons">
    <table>
    <tr>
        <th>Uživatelské jméno</th>
        <th>Role</th>
    </tr>
        <?php while ($row = mysqli_fetch_assoc($result)) : ?>
            <tr>
            <td><?= $row['username'] ?></td>
            <td><?= $row['role'] ?></td>
            </tr>
        <?php endwhile; ?>
    </table>
    </div>

<?php else :
        header('Location: welcome.php');
    endif;
endif;
?>

</div>



</body>
</html>

<?php
mysqli_close($db);
?>
