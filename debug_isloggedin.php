<?php
/**
 * isLoggedIn() Metodu Debug
 */

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

echo "<h1>isLoggedIn() Debug</h1>";
echo "<hr>";

// Session'a test verileri ekle (login simülasyonu)
$_SESSION['user_id'] = 5;
$_SESSION['username'] = 'superadmin';
$_SESSION['session_token'] = 'test_token_' . time();
$_SESSION['login_time'] = time();

echo "<h2>1. Session Verileri</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Auth sınıfını oluştur
require_once 'includes/Auth.php';
$auth = new Auth($pdo);

echo "<h2>2. isLoggedIn() Test</h2>";
echo "<pre>";

// Manuel kontroller
echo "Manuel Kontroller:\n";
echo "  isset(\$_SESSION['user_id']): " . (isset($_SESSION['user_id']) ? 'TRUE' : 'FALSE') . "\n";
echo "  isset(\$_SESSION['session_token']): " . (isset($_SESSION['session_token']) ? 'TRUE' : 'FALSE') . "\n";
echo "  \$_SESSION['user_id'] değeri: " . $_SESSION['user_id'] . "\n";
echo "  \$_SESSION['session_token'] değeri: " . $_SESSION['session_token'] . "\n\n";

// Timeout kontrolü
$timeout = 3600; // 1 saat
$elapsed = time() - $_SESSION['login_time'];
echo "Timeout Kontrolü:\n";
echo "  Session timeout: {$timeout} saniye (1 saat)\n";
echo "  Geçen süre: {$elapsed} saniye\n";
echo "  Timeout oldu mu: " . ($elapsed > $timeout ? 'EVET (FALSE dönecek)' : 'HAYIR (OK)') . "\n\n";

// USER_SESSIONS tablosu kontrolü
echo "USER_SESSIONS Tablo Kontrolü:\n";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'USER_SESSIONS'");
    $exists = $stmt->rowCount() > 0;
    echo "  Tablo var mı: " . ($exists ? 'EVET' : 'HAYIR') . "\n";
    
    if (!$exists) {
        echo "  → Tablo yok, checkSession() TRUE dönmeli\n";
    }
} catch (Exception $e) {
    echo "  Hata: " . $e->getMessage() . "\n";
}

echo "\n";

// isLoggedIn() çağır
echo "isLoggedIn() Sonucu:\n";
try {
    $result = $auth->isLoggedIn();
    echo "  Sonuç: " . ($result ? '✅ TRUE' : '❌ FALSE') . "\n";
} catch (Exception $e) {
    echo "  ❌ Hata: " . $e->getMessage() . "\n";
    echo "  Stack Trace:\n";
    echo "  " . str_replace("\n", "\n  ", $e->getTraceAsString()) . "\n";
}

echo "</pre>";

echo "<h2>3. Auth.php Kodu Kontrolü</h2>";
echo "<pre>";
$authFile = __DIR__ . '/includes/Auth.php';
$content = file_get_contents($authFile);

// isLoggedIn metodunu bul
preg_match('/public function isLoggedIn\(\)(.*?)public function/s', $content, $matches);
if ($matches) {
    $method = substr($matches[1], 0, 500);
    echo "isLoggedIn() metodunun ilk 500 karakteri:\n";
    echo htmlspecialchars($method);
    echo "\n...\n";
}
echo "</pre>";

echo "<hr>";
echo "<p><a href='simple_login_test.php'>← Login Test Sayfasına Dön</a></p>";

echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
    pre { background: white; padding: 15px; border-radius: 5px; border: 1px solid #ddd; overflow-x: auto; }
    h2 { color: #667eea; }
</style>";
