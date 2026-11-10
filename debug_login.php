<?php
/**
 * Login Debug Script
 */

// Session başlat
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/Encryption.php';

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Login Debug</title>";
echo "<style>body{font-family:monospace;padding:20px;background:#f5f5f5;}";
echo ".success{color:green;}.error{color:red;}.info{color:blue;}</style></head><body>";

echo "<h1>🔍 LOGIN DEBUG</h1>";

try {
    // PDO bağlantısı
    $host = '192.168.181.16';
    $dbname = 'ANABILIM';
    $user = 'astera';
    $pass = 'astera2012';
    
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8",
        $user,
        $pass,
        array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
        )
    );
    
    echo "<p class='success'>✅ Veritabanı bağlantısı başarılı!</p>";
    
    // Kullanıcıyı çek
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute(['superadmin']);
    $dbUser = $stmt->fetch();
    
    if (!$dbUser) {
        echo "<p class='error'>❌ Kullanıcı bulunamadı!</p>";
        exit;
    }
    
    echo "<p class='success'>✅ Kullanıcı bulundu!</p>";
    echo "<pre>";
    echo "User ID: {$dbUser['id']}\n";
    echo "Username: {$dbUser['username']}\n";
    echo "Password Hash: {$dbUser['password']}\n";
    echo "Hash Length: " . strlen($dbUser['password']) . "\n";
    echo "</pre>";
    
    // Test şifresi
    $testPassword = 'Astera2020';
    echo "<h2>🔐 Şifre Testi</h2>";
    echo "<p class='info'>Test Şifre: <strong>$testPassword</strong></p>";
    
    // Encryption instance
    $encryption = new Encryption();
    
    // 1. password_verify ile test (PHP 5.5+)
    echo "<h3>Test 1: password_verify()</h3>";
    if (function_exists('password_verify')) {
        $result1 = password_verify($testPassword, $dbUser['password']);
        echo "<p class='" . ($result1 ? 'success' : 'error') . "'>";
        echo $result1 ? "✅ BAŞARILI" : "❌ BAŞARISIZ";
        echo "</p>";
    } else {
        echo "<p class='error'>❌ password_verify() mevcut değil!</p>";
    }
    
    // 2. Encryption class ile test
    echo "<h3>Test 2: Encryption->verifyPassword()</h3>";
    $result2 = $encryption->verifyPassword($testPassword, $dbUser['password']);
    echo "<p class='" . ($result2 ? 'success' : 'error') . "'>";
    echo $result2 ? "✅ BAŞARILI" : "❌ BAŞARISIZ";
    echo "</p>";
    
    // 3. Yeni hash oluştur ve karşılaştır
    echo "<h3>Test 3: Yeni Hash Oluştur</h3>";
    $newHash = $encryption->hashPassword($testPassword);
    echo "<pre>";
    echo "Yeni Hash: $newHash\n";
    echo "Yeni Hash Length: " . strlen($newHash) . "\n";
    echo "</pre>";
    
    $result3 = $encryption->verifyPassword($testPassword, $newHash);
    echo "<p class='" . ($result3 ? 'success' : 'error') . "'>";
    echo "Yeni Hash Testi: " . ($result3 ? "✅ BAŞARILI" : "❌ BAŞARISIZ");
    echo "</p>";
    
    // 4. Auth class ile test
    echo "<h2>🔓 Auth Class Test</h2>";
    require_once __DIR__ . '/includes/Auth.php';
    
    $auth = new Auth($pdo);
    
    echo "<p class='info'>Giriş denemesi yapılıyor...</p>";
    
    $loginResult = $auth->login('superadmin', $testPassword);
    
    if ($loginResult) {
        echo "<p class='success'>✅ AUTH LOGIN BAŞARILI!</p>";
        echo "<pre>";
        print_r($loginResult);
        echo "</pre>";
    } else {
        echo "<p class='error'>❌ AUTH LOGIN BAŞARISIZ!</p>";
        
        // Detaylı hata analizi
        echo "<h3>Hata Analizi:</h3>";
        
        // Kullanıcıyı tekrar kontrol et
        $stmt->execute(['superadmin']);
        $checkUser = $stmt->fetch();
        
        if (!$checkUser) {
            echo "<p class='error'>❌ Kullanıcı Auth sorgusunda bulunamadı!</p>";
        } else {
            echo "<p class='success'>✅ Kullanıcı sorgusu OK</p>";
            
            // Şifre kontrolü
            $passCheck = $encryption->verifyPassword($testPassword, $checkUser['password']);
            echo "<p class='" . ($passCheck ? 'success' : 'error') . "'>";
            echo "Şifre Kontrolü: " . ($passCheck ? "✅ OK" : "❌ FAIL");
            echo "</p>";
        }
    }
    
    // 5. Session kontrolü
    echo "<h2>📋 Session Bilgileri</h2>";
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Hata: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "</body></html>";
