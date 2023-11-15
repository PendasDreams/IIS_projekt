<?php
session_start();

// Zkontrolujte, zda je uživatel přihlášen
if (!isset($_SESSION['username'])) {
    header('Location: login.html'); // Pokud uživatel není přihlášen, přesměrujte jej na přihlašovací stránku
    exit();
}

// Pokud je uživatel přihlášen, zobrazte uvítací zprávu
echo 'Vítejte, ' . $_SESSION['username'] . '!';

// Můžete zde zobrazit další obsah pro přihlášeného uživatele
?>
