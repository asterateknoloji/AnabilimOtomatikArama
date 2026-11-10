<?php
/**
 * Login Debug - Session ve Cookie Kontrolü
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Session başlat
session_start();

echo "<h1>Login Debug</h1>";
echo "<hr>";

echo "<h2>1. PHP Session Ayarları</h2>";
echo "<pre>";
echo "Session Status: " . (session_status() == PHP_SESSION_ACTIVE ? "✅ ACTIVE" : "❌ NONE") . "\n";
echo "Session ID: " . session_id() . "\n";
echo "Session Name: " . session_name() . "\n";
echo "Session Save Path: " . session_save_path() . "\n";
echo "Session Cookie Params:\n";
print_r(session_get_cookie_params());
echo "</pre>";

echo "<h2>2. $_SESSION İçeriği</h2>";
echo "<pre>";
if (empty($_SESSION)) {
    echo "❌ Session boş!\n";
} else {
    echo "✅ Session dolu:\n";
    print_r($_SESSION);
}
echo "</pre>";

echo "<h2>3. $_COOKIE İçeriği</h2>";
echo "<pre>";
if (empty($_COOKIE)) {
    echo "⚠️ Cookie yok!\n";
} else {
    echo "✅ Cookies:\n";
    print_r($_COOKIE);
}
echo "</pre>";

echo "<h2>4. Test: Session Yazma</h2>";
$_SESSION['test_key'] = 'test_value_' . time();
echo "<pre>";
echo "Session'a yazıldı: test_key = " . $_SESSION['test_key'] . "\n";
echo "Sayfayı yenileyin ve hala görüyor musunuz?\n";
echo "</pre>";

echo "<h2>5. Test: Database Bağlantısı</h2>";
try {
    require_once 'config.php';
    echo "<pre>✅ Database bağlantısı başarılı!</pre>";
} catch (Exception $e) {
    echo "<pre>❌ Database hatası: " . htmlspecialchars($e->getMessage()) . "</pre>";
}

echo "<h2>6. Test: Login Simülasyonu</h2>";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'includes/Auth.php';
    
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    echo "<pre>";
    echo "Login denemesi:\n";
    echo "  Username: {$username}\n";
    echo "  Password: " . str_repeat('*', strlen($password)) . "\n\n";
    
    $auth = new Auth($pdo);
    $user = $auth->login($username, $password);
    
    if ($user) {
        echo "✅ Login başarılı!\n";
        echo "  User ID: {$user['id']}\n";
        echo "  Username: {$user['username']}\n\n";
        
        echo "Session durumu:\n";
        echo "  user_id: " . ($_SESSION['user_id'] ?? 'YOK') . "\n";
        echo "  username: " . ($_SESSION['username'] ?? 'YOK') . "\n";
        echo "  session_token: " . (isset($_SESSION['session_token']) ? 'VAR' : 'YOK') . "\n\n";
        
        echo "🔄 5 saniye sonra otomatikarama.php'ye yönlendirileceksiniz...\n";
        echo '<meta http-equiv="refresh" content="5;url=otomatikarama.php">';
    } else {
        echo "❌ Login başarısız!\n";
    }
    echo "</pre>";
} else {
    ?>
    <form method="POST">
        <h3>Test Login Formu</h3>
        <p><input type="text" name="username" placeholder="Kullanıcı Adı" value="superadmin" required></p>
        <p><input type="password" name="password" placeholder="Şifre" value="Astera2021" required></p>
        <p><button type="submit">Giriş Yap</button></p>
    </form>
    <?php
}

echo "<h2>7. Session Klasörü Kontrolü</h2>";
$sessionPath = session_save_path();
if (empty($sessionPath)) {
    $sessionPath = sys_get_temp_dir();
}
echo "<pre>";
echo "Session klasörü: {$sessionPath}\n";
if (is_dir($sessionPath)) {
    echo "✅ Klasör mevcut\n";
    if (is_writable($sessionPath)) {
        echo "✅ Yazılabilir\n";
    } else {
        echo "❌ YAZMA İZNİ YOK!\n";
        echo "Çözüm: PHP'nin bu klasöre yazma izni verin\n";
    }
} else {
    echo "❌ KLASÖR YOK!\n";
}
echo "</pre>";

echo "<hr>";
echo "<p><a href='login.php'>← Normal Login Sayfasına Dön</a></p>";
?>
<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
    h1 { color: #333; }
    h2 { color: #667eea; border-bottom: 2px solid #667eea; padding-bottom: 5px; }
    pre { background: white; padding: 15px; border-radius: 5px; border: 1px solid #ddd; }
    form { background: white; padding: 20px; border-radius: 5px; max-width: 400px; }
    input { width: 100%; padding: 10px; margin: 5px 0; border: 1px solid #ddd; border-radius: 4px; }
    button { width: 100%; padding: 12px; background: #667eea; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
    button:hover { background: #5568d3; }
</style>
