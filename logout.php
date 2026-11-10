<?php
/**
 * Logout Script
 * Kullanıcı çıkışı
 */

require_once 'config.php';
require_once 'includes/Auth.php';

$auth = new Auth($pdo);
$auth->logout();

// Login sayfasına yönlendir
header('Location: login.php');
exit;
