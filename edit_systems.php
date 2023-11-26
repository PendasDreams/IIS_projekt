<?php
session_start();

// Funkce pro odhlášení uživatele
function logoutUser() {
    session_unset(); // Vyprázdnění všech session proměnných
    session_destroy(); // Zničení session
    header('Location: index.html'); // Přesměrování na index.html
    exit(); // Zastavení běhu skriptu
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

$editSystemId = null; // Initialize $editSystemId to null

if (isset($_POST['editSystemId'])) {
    // Assign POST value if available
    $editSystemId = $_POST['editSystemId'];
    // Store in session if the user is not 'admin'
    if ($_SESSION['username'] != 'admin') {
        $_SESSION['editSystemId'] = $editSystemId;
    }
} elseif (isset($_SESSION['editSystemId'])) {
    // Use session value if POST value is not available
    $editSystemId = $_SESSION['editSystemId'];
}

$editSystemName = isset($_POST['editSystemName']) ? $_POST['editSystemName'] : null;
$editSystemDescription = isset($_POST['editSystemDescription']) ? $_POST['editSystemDescription'] : null;


// Fetching users with access to the edited system
if ($editSystemId) {
    $accessQuery = "SELECT u.id, u.username FROM system_user_access sua
                    JOIN users u ON sua.user_id = u.id
                    WHERE sua.system_id = '$editSystemId' AND u.id != 0";
    $accessResult = mysqli_query($db, $accessQuery);
    $accessUsers = mysqli_fetch_all($accessResult, MYSQLI_ASSOC);
}

// Handle the removal of a user
// if (isset($_POST['removeUser'])) {
//     $userIdToRemove = mysqli_real_escape_string($db, $_POST['userId']);
//     $systemIdToRemoveFrom = mysqli_real_escape_string($db, $_POST['systemId']);

//     if ($systemIdToRemoveFrom == $editSystemId) {
//         $removeQuery = "DELETE FROM system_user_access WHERE user_id = '$userIdToRemove' AND system_id = '$systemIdToRemoveFrom'";
//         mysqli_query($db, $removeQuery);

//         // Refresh the list of users with access
//         $accessQuery = "SELECT u.id, u.username FROM system_user_access sua
//                         JOIN users u ON sua.user_id = u.id
//                         WHERE sua.system_id = '$editSystemId' AND u.id != 0";
//         $accessResult = mysqli_query($db, $accessQuery);
//         $accessUsers = mysqli_fetch_all($accessResult, MYSQLI_ASSOC);

//         header('Location: edit_systems.php');
//         exit(); 
//     }
// }

// Handle the removal of a user
// if (isset($_POST['removeUser'])) {
//     $userIdToRemove = mysqli_real_escape_string($db, $_POST['userId']);
//     $systemId = mysqli_real_escape_string($db, $_POST['systemId']);
//     $removeQuery = "DELETE FROM system_user_access WHERE user_id = '$userIdToRemove' AND system_id = '$systemId'";
//     mysqli_query($db, $removeQuery);

//     // Redirect to refresh the page
//     header('Location: edit_systems.php');
//     exit();
// }

// Poté můžete tyto informace použít v formuláři pro úpravu systému


if (isset($_POST['updateSystem'])) {
    $editSystemId = mysqli_real_escape_string($db, $_POST['editSystemId']);
    $editSystemName = mysqli_real_escape_string($db, $_POST['editSystemName']);
    $editSystemDescription = mysqli_real_escape_string($db, $_POST['editSystemDescription']);
    $editSystemAdminID = mysqli_real_escape_string($db, $_POST['editSystemAdminID']);

    // Update system details
    $updateQuery = "UPDATE systems SET name = '$editSystemName', description = '$editSystemDescription', admin_id = '$editSystemAdminID' WHERE id = '$editSystemId'";
    mysqli_query($db, $updateQuery);

    // Remove selected users
    if (isset($_POST['usersToRemove'])) {
        foreach ($_POST['usersToRemove'] as $userIdToRemove) {
            $userIdToRemove = mysqli_real_escape_string($db, $userIdToRemove);
            $removeQuery = "DELETE FROM system_user_access WHERE user_id = '$userIdToRemove' AND system_id = '$editSystemId'";
            mysqli_query($db, $removeQuery);
        }
    }

    // Redirect to refresh the page
    header('Location: edit_systems.php');
    exit();
}




?>

<!DOCTYPE html>
<html>
<head>
    <title>Přidat zařízení</title>
    <link rel="stylesheet" type="text/css" href="welcome_style.css">
    <link rel="stylesheet" type="text/css" href="styles.css">
</head>
<body>

<div class="user-bar">
    <a href="editusers.php" class="system-button">Uživatelé</a>
    <a href="system.php" class="system-button">Systémy</a>
    <a href="devices.php" class="system-button">Zařízení</a>
    <a href="manage_requests.php" class="system-button">Spravovat žádosti</a>
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



<div class="centered-form">
<h2>Upravit zařízení</h2>


<form class="user-form" method="POST" action="edit_systems.php">
    <input type="hidden" name="editSystemId" value="<?= $editSystemId ?>">
    <div class="form-group">
        <label for="editSystemName">Název systému:</label>
        <input type="text" id="editSystemName" name="editSystemName" value="<?= $editSystemName ?>" required>
    </div>

    <div class="form-group">
        <label for="editSystemDescription">Popis systému:</label>
        <textarea id="editSystemDescription" name="editSystemDescription" required><?= $editSystemDescription ?></textarea>
    </div>

    <div class="form-group">
        <label for="editSystemAdminID">ID admina systému:</label>
        <input type="number" id="editSystemAdminID" name="editSystemAdminID" value="<?= $editSystemAdminID ?>" required>
    </div>
    
    <?php if (isset($accessUsers) && $accessUsers): ?>
    <div class="system-users">
        <h3 style="text-align:center;">Uživatelé s přístupem k systému: <?= htmlspecialchars($editSystemName) ?></h3>  
        <table>
            <tr>
                <th>Uživatel</th>
                <th>Odstránit</th>
            </tr>
            <?php foreach ($accessUsers as $user): ?>
                <tr>
                    <td style="text-align: center;"><?= htmlspecialchars($user['username']) ?></td>
                    <td style="text-align: center;">
                        <input type="checkbox" name="usersToRemove[]" value="<?= $user['id'] ?>">
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
    <?php endif; ?>

    <div class="form-group">
        <button class="btn-submit" type="submit" name="updateSystem">Uložit změny</button>
    </div>
</form>


   
</div>



</body>
</html>

<?php
mysqli_close($db);
?>