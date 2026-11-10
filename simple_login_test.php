<?php
/**
 * BASİT LOGIN TESTİ
 */

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';
require_once 'includes/Auth.php';

echo "<h1>Basit Login Testi</h1>";
echo "<hr>";

// POST ile geldiyse login yap
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    echo "<h2>Login İşlemi Başlıyor...</h2>";
    echo "<pre>";
    echo "Kullanıcı: {$username}\n";
    echo "Şifre: " . str_repeat('*', strlen($password)) . "\n\n";
    
    try {
        $auth = new Auth($pdo);
        $user = $auth->login($username, $password);
        
        if ($user) {
            echo "✅ LOGIN BAŞARILI!\n\n";
            echo "User Bilgileri:\n";
            echo "  ID: {$user['id']}\n";
            echo "  Username: {$user['username']}\n";
            echo "  Email: " . ($user['email'] ?: '(boş)') . "\n\n";
            
            echo "Session Bilgileri:\n";
            echo "  user_id: " . ($_SESSION['user_id'] ?? 'YOK') . "\n";
            echo "  username: " . ($_SESSION['username'] ?? 'YOK') . "\n";
            echo "  session_token: " . (isset($_SESSION['session_token']) ? 'VAR (' . substr($_SESSION['session_token'], 0, 10) . '...)' : 'YOK') . "\n";
            echo "  login_time: " . ($_SESSION['login_time'] ?? 'YOK') . "\n\n";
            
            echo "isLoggedIn() kontrolü: " . ($auth->isLoggedIn() ? '✅ TRUE' : '❌ FALSE') . "\n\n";
            
            echo "🎉 Login başarılı! Şimdi yönlendirileceksiniz...\n";
            echo "</pre>";
            
            // JavaScript ile yönlendir (header yerine)
            echo '<script>setTimeout(function(){ window.location.href = "otomatikarama.php"; }, 3000);</script>';
            echo '<p><a href="otomatikarama.php">Beklemek istemiyorsanız buraya tıklayın →</a></p>';
            
        } else {
            echo "❌ LOGIN BAŞARISIZ!\n\n";
            echo "Kullanıcı adı veya şifre hatalı.\n";
            echo "</pre>";
            echo '<p><a href="simple_login_test.php">← Tekrar dene</a></p>';
        }
        
    } catch (Exception $e) {
        echo "❌ HATA!\n\n";
        echo "Mesaj: " . $e->getMessage() . "\n";
        echo "Dosya: " . $e->getFile() . ":" . $e->getLine() . "\n";
        echo "</pre>";
    }
    
} else {
    // Form göster
    ?>
    <form method="POST" style="max-width: 400px; margin: 50px auto; padding: 30px; background: #f9f9f9; border-radius: 10px;">
        <h2 style="text-align: center; color: #667eea;">Test Login</h2>
        
        <div style="margin: 20px 0;">
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Kullanıcı Adı:</label>
            <input type="text" name="username" value="superadmin" required 
                   style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px;">
        </div>
        
        <div style="margin: 20px 0;">
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Şifre:</label>
            <input type="password" name="password" value="Astera2021" required 
                   style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px;">
        </div>
        
        <button type="submit" style="width: 100%; padding: 12px; background: #667eea; color: white; border: none; border-radius: 5px; font-size: 16px; font-weight: bold; cursor: pointer;">
            Giriş Yap
        </button>
        
        <p style="text-align: center; margin-top: 20px; font-size: 12px; color: #666;">
            <a href="login.php">Normal Login Sayfası</a> | 
            <a href="login_debug.php">Debug Sayfası</a>
        </p>
    </form>
    <?php
}
?>
<style>
    body { font-family: Arial, sans-serif; background: #f5f5f5; }
    pre { background: white; padding: 20px; border-radius: 5px; border: 1px solid #ddd; }
    a { color: #667eea; text-decoration: none; }
    a:hover { text-decoration: underline; }
</style>
