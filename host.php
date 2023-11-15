<?php
session_start();

// Připojení k databázi
$db = mysqli_init();
if (!mysqli_real_connect($db, 'localhost', 'xnovos14', 'inbon8uj', 'xnovos14', 0, '/var/run/mysql/mysql.sock')) {
    die('Nelze se připojit k databázi: ' . mysqli_connect_error());
}

// Dotaz pro výpis všech uživatelů
$query = "SELECT * FROM users";

$result = mysqli_query($db, $query);

if (!$result) {
    die('Chyba dotazu: ' . mysqli_error($db));
}

// Výpis tabulky uživatelů

echo '<h2>Hostrdy</h2>';
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
