<?php
session_start();

// Funkce pro odhlášení uživatele
function logoutUser() {
    session_unset(); 
    session_destroy();
    header('Location: index.html'); 
    exit(); 
}

$currentUsername = isset($_SESSION['username']) ? $_SESSION['username'] : null;
$currentRole = isset($_SESSION['role']) ? $_SESSION['role'] : null;


if (isset($_POST['logout'])) {
    logoutUser();
}

// Připojení k databázi
include_once("connect.php");
$db = mysqli_init();
pripojit();

$editSystemId = null; 

// Pro moznost refreshu stránky
if (isset($_POST['editSystemId'])) {
    $editSystemId = $_POST['editSystemId'];
    if ($_SESSION['username'] != 'admin') {
        $_SESSION['editSystemId'] = $editSystemId;
    }
} elseif (isset($_SESSION['editSystemId'])) {
    $editSystemId = $_SESSION['editSystemId'];
}

$editSystemName = isset($_POST['editSystemName']) ? $_POST['editSystemName'] : null;
$editSystemDescription = isset($_POST['editSystemDescription']) ? $_POST['editSystemDescription'] : null;

if ($editSystemId) {
    $systemQuery = "SELECT * FROM systems WHERE id = '$editSystemId'";
    $systemResult = mysqli_query($db, $systemQuery);
    $systemData = mysqli_fetch_assoc($systemResult);
    $editSystemName = $systemData['name'];
    $editSystemDescription = $systemData['description'];
    $editSystemAdminID = $systemData['admin_id'];

    
    $accessQuery = "SELECT u.id, u.username 
                    FROM system_user_access sua
                    JOIN users u ON sua.user_id = u.id
                    WHERE sua.system_id = '$editSystemId'";
    $accessResult = mysqli_query($db, $accessQuery);
    $accessUsers = mysqli_fetch_all($accessResult, MYSQLI_ASSOC);
}

$usersDropdownQuery = "SELECT u.id, u.username FROM users u JOIN roles r ON u.role = r.id WHERE r.role NOT IN ('broker', 'guest', 'admin')";
$usersDropdownResult = mysqli_query($db, $usersDropdownQuery);
$usersDropdown = mysqli_fetch_all($usersDropdownResult, MYSQLI_ASSOC);



if (isset($_POST['updateSystem'])) {
    $editSystemId = mysqli_real_escape_string($db, $_POST['editSystemId']);
    $editSystemName = mysqli_real_escape_string($db, $_POST['editSystemName']);
    $editSystemDescription = mysqli_real_escape_string($db, $_POST['editSystemDescription']);
    $editSystemAdminID = mysqli_real_escape_string($db, $_POST['editSystemAdminID']);

    
    $updateQuery = "UPDATE systems SET name = '$editSystemName', description = '$editSystemDescription', admin_id = '$editSystemAdminID' WHERE id = '$editSystemId'";
    mysqli_query($db, $updateQuery);

   
    if (isset($_POST['usersToRemove'])) {
        foreach ($_POST['usersToRemove'] as $userIdToRemove) {
            $userIdToRemove = mysqli_real_escape_string($db, $userIdToRemove);
            $removeQuery = "DELETE FROM system_user_access WHERE user_id = '$userIdToRemove' AND system_id = '$editSystemId'";
            mysqli_query($db, $removeQuery);
        }
    }

    
    header('Location: system.php');
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
        <select id="editSystemAdminID" name="editSystemAdminID" required>
            <?php foreach ($usersDropdown as $user): ?>
                <option value="<?= $user['id'] ?>" <?= $user['id'] == $editSystemAdminID ? 'selected' : '' ?>><?= htmlspecialchars($user['username']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <?php if (!empty($accessUsers)): ?>
        <div class="system-users">
            <h3 style="text-align:center;">Uživatelé s přístupem k systému: <?= htmlspecialchars($editSystemName) ?></h3>  
            <table>
                <tr>
                    <th style="text-align: center;">Uživatel</th>
                    <th style="text-align: center;">Odstránit</th>
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

    <div class="form-group" style="margin-top: 20px;">
        <button class="btn-submit" type="submit" name="updateSystem">Uložit změny</button>
    </div>
</form>
   
</div>

</body>
</html>

<?php
mysqli_close($db);
?>