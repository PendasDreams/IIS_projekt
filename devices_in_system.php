<?php
session_start();

// Připojení k databázi
$db = mysqli_init();
if (!mysqli_real_connect($db, 'localhost', 'xnovos14', 'inbon8uj', 'xnovos14', 0, '/var/run/mysql/mysql.sock')) {
    die('Nelze se připojit k databázi: ' . mysqli_connect_error());
}

if (isset($_POST['viewDevices'])) {
    $systemId = mysqli_real_escape_string($db, $_POST['systemId']);
    
    // Dotaz pro získání zařízení v daném systému
    $query = "SELECT * FROM devices WHERE system_id = '$systemId'";
    $result = mysqli_query($db, $query);

    if (!$result) {
        die('Chyba dotazu: ' . mysqli_error($db));
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Zobrazení zařízení v systému</title>
    <link rel="stylesheet" type="text/css" href="welcome_style.css">
    <link rel="stylesheet" type="text/css" href="styles.css">
</head>
<body>

<!-- Zde zobrazte seznam zařízení v daném systému pomocí dat z databáze (v proměnné $result) -->

<h2>Zařízení v systému</h2>
<table>
    <tr>
        <th>ID</th>
        <th>Název zařízení</th>
        <th>Typ zařízení</th>
        <th>Popis</th>
        <!-- Další sloupce podle potřeby -->
    </tr>
    <?php while ($device = mysqli_fetch_assoc($result)) : ?>
        <tr>
            <td><?= $device['id'] ?></td>
            <td><?= $device['device_name'] ?></td>
            <td><?= $device['device_type'] ?></td>
            <td><?= $device['device_description'] ?></td>
            <!-- Další sloupce podle potřeby -->
        </tr>
    <?php endwhile; ?>
</table>

</body>
</html>

<?php
mysqli_close($db);
?>
