<?php
//funkce pro připojení k databázi
function pripojit(){
global $db;
$server = "localhost";
$uzivatel = "xdohna52";
$heslo = "vemsohu6";
$database = "xdohna52";
$link=mysqli_real_connect($db, $server, $uzivatel, $heslo, $database, 0, '/var/run/mysql/mysql.sock');
 
if (!$link) {
    die('Nelze se připojit k databázi: ' . mysqli_connect_error());
}
}
?>