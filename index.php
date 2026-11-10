<?php
session_start();

// Login kontrolü
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Giriş yapmışsa menü sayfasını göster
header('Location: otomatikarama.php');
exit;
