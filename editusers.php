<?php
session_start();


// Připojení k databázi
include_once("connect.php");
$db = mysqli_init();
pripojit();

$currentUsername = isset($_SESSION['username']) ? $_SESSION['username'] : null;
$currentRole = isset($_SESSION['role']) ? $_SESSION['role'] : null;

// Pole povolených rolí
$allowedRoles = array('admin', 'registered', 'broker', 'guest');
$rolesQuery = "SELECT id, role FROM roles";
$rolesResult = mysqli_query($db, $rolesQuery);
$allRoles = [];
while ($role = mysqli_fetch_assoc($rolesResult)) {
    $allRoles[$role['id']] = $role['role'];
}

$errorMessage = ''; 

if (isset($_POST['addUser'])) {
    $newUsername = mysqli_real_escape_string($db, $_POST['newUsername']);
    $newPassword = mysqli_real_escape_string($db, $_POST['newPassword']);
    $newRole = mysqli_real_escape_string($db, $_POST['newRole']);

    // Kontrola, zda je zadaná role v seznamu povolených rolí
    if (array_key_exists($newRole, $allRoles)) {
        // Kontrola, zda již existuje uživatel se stejným jménem
        $checkDuplicateQuery = "SELECT * FROM users WHERE username = '$newUsername'";
        $duplicateResult = mysqli_query($db, $checkDuplicateQuery);

        if (!$duplicateResult) {
            die('Chyba dotazu: ' . mysqli_error($db));
        }

        if (mysqli_num_rows($duplicateResult) == 0) {

            $insertQuery = "INSERT INTO users (username, password, role) VALUES ('$newUsername', '$newPassword', $newRole)";
            $insertResult = mysqli_query($db, $insertQuery);

            if ($insertResult) {
                echo "Uživatel '$newUsername' byl úspěšně přidán.";
                header('Location: editusers.php'); 
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

    $deleteUserQuery = "DELETE FROM users WHERE id = '$deleteUserId'";

    if (mysqli_query($db, $deleteUserQuery)) {
        echo "Uživatel s ID $deleteUserId byl úspěšně smazán.";
        header('Location: editusers.php'); 

    } else {
        echo 'Chyba při mazání uživatele: ' . mysqli_error($db);
    }
}


if (isset($_POST['loadEditForm'])) {
    
    $editUserId = mysqli_real_escape_string($db, $_POST['editUserId']);

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

    if (array_key_exists($editedRole, $allRoles)) {
        // Kontrola, zda již existuje uživatel se stejným jménem
        $checkDuplicateQuery = "SELECT * FROM users WHERE username = '$editedUsername' AND id != '$editUserId'";
        $duplicateResult = mysqli_query($db, $checkDuplicateQuery);

        if (!$duplicateResult) {
            die('Chyba dotazu: ' . mysqli_error($db));
        }

        if (mysqli_num_rows($duplicateResult) == 0) {
            
            $editUserQuery = "UPDATE users SET username = '$editedUsername', password = '$editedPassword', role = $editedRole WHERE id = '$editUserId'";
            if (mysqli_query($db, $editUserQuery)) {
                echo "Uživatel s ID $editUserId byl úspěšně upraven.";
                header('Location: editusers.php'); 
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
    
    session_unset();
    
    session_destroy();
    
    header('Location: index.html');
    exit();
}

$roleQuery = "SELECT id, role FROM roles";
$roleResult = mysqli_query($db, $roleQuery);
$roles = mysqli_fetch_all($roleResult, MYSQLI_ASSOC);

?>

<script>
document.addEventListener("DOMContentLoaded", function() {
    
    var urlParams = new URLSearchParams(window.location.search);
    var submitted = urlParams.get("submitted");
    
    if (submitted) {
        
        setTimeout(function() {
           
            var editForm = document.querySelector(".user-form");
            
            
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
    <?php
    if ($currentRole != 'guest'){
        echo '<a href="manage_requests.php" class="system-button">Spravovat žádosti</a>';
    }
    ?>
    <?php if ($currentUsername) : ?>
        <span class="user-info">Přihlášený uživatel:</span> <strong><?= $currentUsername ?></strong><br>
        <span class="user-info">Role:</span> <strong><?= $currentRole ?></strong>

        
        <form method="POST" action="index.html"> 
            <button type="submit" name="logout" class="logout-button">Odhlásit se</button>
        </form>
        
    <?php else : ?>
        <span class="user-info">Není žádný uživatel přihlášen.</span>
    <?php endif; ?>
</div>

<?php if ($currentRole === 'admin') : ?>
   
    <div class="centered-buttons">
        <?php
        
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
                    <th>Upravit</th>
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
                    <select id="editedRole" name="editedRole" required>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?= $role['id'] ?>" <?= (isset($user) && $user['role'] == $role['id']) ? 'selected' : '' ?>><?= htmlspecialchars(ucfirst($role['role'])) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                
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
                    <select id="newRole" name="newRole" required>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?= $role['id'] ?>"><?= htmlspecialchars(ucfirst($role['role'])) ?></option>
                        <?php endforeach; ?>
                    </select>
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
