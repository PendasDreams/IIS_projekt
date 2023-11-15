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

// Dotaz pro výpis všech uživatelů
$query = "SELECT * FROM users";

$result = mysqli_query($db, $query);

if (!$result) {
    die('Chyba dotazu: ' . mysqli_error($db));
}

// Zpracování formuláře pro přidání uživatele
// Pole povolených rolí
$allowedRoles = array('admin', 'registered', 'broker');

$errorMessage = ''; // Inicializace chybové zprávy prázdnou hodnotou

if (isset($_POST['addUser'])) {
    $newUsername = mysqli_real_escape_string($db, $_POST['newUsername']);
    $newPassword = mysqli_real_escape_string($db, $_POST['newPassword']);
    $newRole = mysqli_real_escape_string($db, $_POST['newRole']);

    // Kontrola, zda je zadaná role v seznamu povolených rolí
    if (in_array($newRole, $allowedRoles)) {
        // Vložení nového uživatele do databáze
        $insertQuery = "INSERT INTO users (username, password, role) VALUES ('$newUsername', '$newPassword', '$newRole')";
        $insertResult = mysqli_query($db, $insertQuery);

        if ($insertResult) {
            // Uživatel byl úspěšně přidán
            echo "Uživatel '$newUsername' byl úspěšně přidán.";
            header('Location: welcome.php'); // Přesměrování na stránku se seznamem uživatelů

        } else {
            // Chyba při vkládání uživatele
            echo 'Chyba při přidávání uživatele: ' . mysqli_error($db);
        }
    } else {
        // Neplatná role
        $errorMessage = 'Zadaná role není platná. Povolené role jsou: ' . implode(', ', $allowedRoles);
    }
}

if (isset($_POST['deleteUser'])) {
    $deleteUserId = mysqli_real_escape_string($db, $_POST['deleteUserId']);

    // SQL dotaz pro smazání uživatele z databáze
    $deleteUserQuery = "DELETE FROM users WHERE id = '$deleteUserId'";

    if (mysqli_query($db, $deleteUserQuery)) {
        echo "Uživatel s ID $deleteUserId byl úspěšně smazán.";
        header('Location: welcome.php'); // Přesměrování na stránku se seznamem uživatelů

    } else {
        echo 'Chyba při mazání uživatele: ' . mysqli_error($db);
    }
}

?>

<!-- HTML kód -->

<script>
    document.addEventListener("DOMContentLoaded", function () {
        // Funkce pro načtení formuláře pro úpravu uživatele pomocí AJAX
        function loadEditUserForm(userId) {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'edit_user_form.php?editUserId=' + userId, true);
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    // Vložení HTML formuláře do <div> s id "editUserForm"
                    document.getElementById('editUserForm').innerHTML = xhr.responseText;
                }
            };
            xhr.send();
        }

        // Zachytávání kliknutí na tlačítka "Upravit"
        var editButtons = document.querySelectorAll('.edit-button');
        editButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                var userId = this.getAttribute('data-userid');
                loadEditUserForm(userId);
            });
        });
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
    <?php if ($currentUsername) : ?>
        Přihlášený uživatel: <?= $currentUsername ?><br>
        Role: <?= $currentRole ?>
    <?php else : ?>
        Není žádný uživatel přihlášen.
    <?php endif; ?>


    </div>

    <?php if ($currentRole === 'admin') : ?>
    <!-- Zobrazit nadpisy pouze pro uživatele s rolí "admin" -->
    <div class="centered-buttons">
        <h2>Systémy</h2> <!-- Nadpis pro sekci "Systémy" -->
        <!-- Zde můžete přidat obsah pro sekci "Systémy" -->
        <h2>Uživatelé</h2> <!-- Nadpis pro sekci "Uživatelé" -->

        <table>
            <tr>
                <th>ID</th>
                <th>Uživatelské jméno</th>
                <th>Heslo</th>
                <th>Role</th>
                <th>Smazat</th>
                <th>Upravit</th> <!-- Přidán sloupec pro tlačítko "Upravit" -->
            </tr>
            <?php while ($row = mysqli_fetch_assoc($result)) : ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= $row['username'] ?></td>
                    <td><?= $row['password'] ?></td>
                    <td><?= $row['role'] ?></td>
                    <td>
                        <form method="POST" action="">
                            <input type="hidden" name="deleteUserId" value="<?= $row['id'] ?>">
                            <button class="delete-button" type="submit" name="deleteUser">Smazat</button>
                        </form>
                    </td>
                    <td>
                        <form method="POST" action="">
                            <input type="hidden" name="editUserId" value="<?= $row['id'] ?>">
                            <button class="edit-button" type="button" name="editUser" data-userid="<?= $row['id'] ?>">Upravit</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>

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

            <?php if (!empty($errorMessage)) : ?>
                <div class="error-message">
                    <?= $errorMessage ?>
                </div>
            <?php endif; ?>

            <div id="editUserForm"></div> <!-- Místo, kam bude vložen formulář pro úpravu uživatele pomocí AJAX -->



<?php endif; ?>

<?php if ($currentRole !== 'admin') : ?>
    <!-- Zobrazit nadpisy pouze pro uživatele s rolí "admin" -->
    <div class="centered-buttons">
        <h2>Systémy</h2> <!-- Nadpis pro sekci "Systémy" -->
        <!-- Zde můžete přidat obsah pro sekci "Systémy" -->

<?php endif; ?>

</div>



</body>
</html>

<?php
mysqli_close($db);
?>
