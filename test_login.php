<?php
/**
 * Login Test Script
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/Auth.php';

echo "=== LOGIN TEST ===\n\n";

// Kullanıcı bilgilerini kontrol et
$username = 'superadmin';
$password = 'Astera2021';

echo "Test Bilgileri:\n";
echo "  Kullanıcı: {$username}\n";
echo "  Şifre: {$password}\n\n";

// 1. Kullanıcıyı database'den çek
try {
    $stmt = $pdo->prepare("SELECT * FROM " . table('users') . " WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo "❌ Kullanıcı bulunamadı!\n";
        exit(1);
    }
    
    echo "✅ Kullanıcı bulundu:\n";
    echo "  ID: {$user['id']}\n";
    echo "  Username: {$user['username']}\n";
    echo "  Email: " . ($user['email'] ?: '(boş)') . "\n";
    echo "  Active: " . ($user['is_active'] ? 'Evet' : 'Hayır') . "\n";
    echo "  Password Hash: " . substr($user['password'], 0, 30) . "...\n\n";
    
} catch (Exception $e) {
    echo "❌ Database hatası: " . $e->getMessage() . "\n";
    exit(1);
}

// 2. Şifre hash tipini kontrol et
echo "Şifre Hash Bilgisi:\n";
if (strpos($user['password'], '$2y$') === 0) {
    echo "  ✅ Hash Tipi: bcrypt (PHP password_hash)\n";
    echo "  ✅ Uzunluk: " . strlen($user['password']) . " karakter\n\n";
} else {
    echo "  ❌ Bilinmeyen hash tipi!\n";
    echo "  Hash: {$user['password']}\n\n";
}

// 3. Manuel şifre doğrulama
echo "Manuel Şifre Doğrulama:\n";
$isValid = password_verify($password, $user['password']);
echo "  " . ($isValid ? "✅" : "❌") . " password_verify('{$password}', hash) = " . ($isValid ? 'TRUE' : 'FALSE') . "\n\n";

// 4. Encryption sınıfı ile test
echo "Encryption Sınıfı ile Test:\n";
try {
    require_once __DIR__ . '/includes/Encryption.php';
    $encryption = new Encryption();
    $isValidEncryption = $encryption->verifyPassword($password, $user['password']);
    echo "  " . ($isValidEncryption ? "✅" : "❌") . " Encryption::verifyPassword() = " . ($isValidEncryption ? 'TRUE' : 'FALSE') . "\n\n";
} catch (Exception $e) {
    echo "  ❌ Encryption hatası: " . $e->getMessage() . "\n\n";
}

// 5. Auth sınıfı ile giriş testi
echo "Auth Sınıfı ile Login Testi:\n";
try {
    // Session'ı temizle
    if (session_status() == PHP_SESSION_ACTIVE) {
        session_destroy();
    }
    
    $auth = new Auth($pdo);
    $loginResult = $auth->login($username, $password);
    
    if ($loginResult) {
        echo "  ✅ Login başarılı!\n";
        echo "  User ID: {$loginResult['id']}\n";
        echo "  Username: {$loginResult['username']}\n";
        
        // Session kontrolü
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        echo "\n  Session Bilgileri:\n";
        echo "    user_id: " . ($_SESSION['user_id'] ?? 'YOK') . "\n";
        echo "    username: " . ($_SESSION['username'] ?? 'YOK') . "\n";
        echo "    session_token: " . (isset($_SESSION['session_token']) ? 'VAR' : 'YOK') . "\n";
        echo "    login_time: " . ($_SESSION['login_time'] ?? 'YOK') . "\n";
    } else {
        echo "  ❌ Login başarısız!\n";
    }
    
} catch (Exception $e) {
    echo "  ❌ Auth hatası: " . $e->getMessage() . "\n";
    echo "  Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n=== TEST TAMAMLANDI ===\n";
