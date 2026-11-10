<?php
/**
 * Kullanıcı Giriş Testi ve Şifre Sıfırlama
 */

require_once 'config.php';
require_once 'includes/Encryption.php';

$encryption = new Encryption();

echo "<!DOCTYPE html>";
echo "<html lang='tr'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<title>Kullanıcı Test & Şifre Sıfırlama</title>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
    .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    h1 { color: #333; border-bottom: 3px solid #667eea; padding-bottom: 10px; }
    h2 { color: #667eea; margin-top: 30px; }
    .user-card { background: #f9f9f9; padding: 15px; margin: 10px 0; border-left: 4px solid #667eea; border-radius: 5px; }
    .success { color: #27ae60; font-weight: bold; }
    .error { color: #e74c3c; font-weight: bold; }
    .info { color: #3498db; }
    table { width: 100%; border-collapse: collapse; margin: 10px 0; }
    th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
    th { background: #667eea; color: white; }
    .btn { display: inline-block; padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin: 5px; }
    .btn:hover { background: #5568d3; }
    .code { background: #f4f4f4; padding: 2px 5px; border-radius: 3px; font-family: monospace; }
</style>";
echo "</head>";
echo "<body>";
echo "<div class='container'>";

echo "<h1>🔐 Kullanıcı Giriş Testi</h1>";

// Şifre sıfırlama işlemi
if (isset($_POST['reset_password'])) {
    $userId = $_POST['user_id'];
    $newPassword = $_POST['new_password'];
    
    echo "<h2>Şifre Sıfırlama İşlemi</h2>";
    
    $hashedPassword = $encryption->hashPassword($newPassword);
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    $result = $stmt->execute([$hashedPassword, $userId]);
    
    if ($result) {
        echo "<p class='success'>✅ Şifre başarıyla sıfırlandı!</p>";
        echo "<p>Kullanıcı ID: <strong>$userId</strong></p>";
        echo "<p>Yeni Şifre: <strong class='code'>$newPassword</strong></p>";
    } else {
        echo "<p class='error'>❌ Şifre sıfırlanamadı!</p>";
    }
    echo "<hr>";
}

// Tüm kullanıcıları listele
echo "<h2>📋 Kayıtlı Kullanıcılar</h2>";

$stmt = $pdo->query("SELECT * FROM users ORDER BY id");
$users = $stmt->fetchAll();

echo "<p>Toplam <strong>" . count($users) . "</strong> kullanıcı bulundu.</p>";

foreach ($users as $user) {
    echo "<div class='user-card'>";
    echo "<h3>👤 {$user['username']}</h3>";
    echo "<table>";
    echo "<tr><th>Alan</th><th>Değer</th></tr>";
    echo "<tr><td>ID</td><td>{$user['id']}</td></tr>";
    echo "<tr><td>Kullanıcı Adı</td><td><strong>{$user['username']}</strong></td></tr>";
    echo "<tr><td>Email</td><td>{$user['email']}</td></tr>";
    echo "<tr><td>Aktif</td><td>" . ($user['is_active'] ? '✅ EVET' : '❌ HAYIR') . "</td></tr>";
    echo "<tr><td>Oluşturma</td><td>{$user['created_at']}</td></tr>";
    echo "<tr><td>Son Giriş</td><td>" . ($user['last_login'] ?? 'Hiç giriş yapılmamış') . "</td></tr>";
    
    // Şifre hash bilgisi
    $hashInfo = "Bilinmiyor";
    $hashLength = strlen($user['password']);
    
    if (strpos($user['password'], '$2y$') === 0) {
        $hashInfo = "bcrypt (password_hash)";
    } elseif ($hashLength == 32) {
        $hashInfo = "MD5 (Eski format)";
    } elseif ($hashLength == 40) {
        $hashInfo = "SHA1";
    }
    
    echo "<tr><td>Şifre Hash Tipi</td><td class='info'>{$hashInfo}</td></tr>";
    echo "<tr><td>Hash Uzunluğu</td><td>{$hashLength} karakter</td></tr>";
    echo "</table>";
    
    // Test şifreleri
    $testPasswords = [
        'Astera2021',
        'astera2021', 
        'ASTERA2021',
        'admin',
        'superadmin',
        '123456',
        'password'
    ];
    
    echo "<h4>🔍 Şifre Testi (Yaygın şifreler):</h4>";
    echo "<ul>";
    foreach ($testPasswords as $testPassword) {
        $isValid = $encryption->verifyPassword($testPassword, $user['password']);
        $icon = $isValid ? "✅" : "❌";
        $class = $isValid ? "success" : "error";
        echo "<li class='$class'>$icon <code>$testPassword</code></li>";
    }
    echo "</ul>";
    
    // Şifre sıfırlama formu
    echo "<form method='POST' style='margin-top: 15px;'>";
    echo "<h4>🔄 Şifre Sıfırla:</h4>";
    echo "<input type='hidden' name='user_id' value='{$user['id']}'>";
    echo "<input type='text' name='new_password' placeholder='Yeni şifre' required style='padding: 8px; width: 200px; border: 2px solid #ddd; border-radius: 5px;'>";
    echo "<button type='submit' name='reset_password' class='btn'>Şifreyi Sıfırla</button>";
    echo "</form>";
    
    echo "</div>";
}

// Yeni kullanıcı oluşturma önerisi
if (count($users) == 0) {
    echo "<div style='background: #ffe6e6; padding: 20px; border-radius: 5px; margin-top: 20px;'>";
    echo "<h3>⚠️ Hiç Kullanıcı Yok!</h3>";
    echo "<p>Sisteme giriş yapabilmek için bir kullanıcı oluşturmanız gerekiyor.</p>";
    echo "<p><a href='create_superadmin.php' class='btn'>Superadmin Oluştur</a></p>";
    echo "</div>";
}

echo "<div style='margin-top: 30px; padding: 20px; background: #e8f4fd; border-radius: 5px;'>";
echo "<h3>💡 Kullanım Talimatları</h3>";
echo "<ol>";
echo "<li>Yukarıdaki listede kullanıcıları ve şifre testlerini görüyorsunuz</li>";
echo "<li>Eğer hiçbir test şifresi eşleşmediyse, <strong>'Şifre Sıfırla'</strong> butonunu kullanın</li>";
echo "<li>Yeni şifre belirleyin ve kaydedin</li>";
echo "<li>Login sayfasında yeni şifre ile giriş yapın</li>";
echo "</ol>";
echo "</div>";

echo "<div style='margin-top: 20px; text-align: center;'>";
echo "<a href='login.php' class='btn'>🔐 Login Sayfasına Dön</a>";
echo "</div>";

echo "</div>";
echo "</body>";
echo "</html>";
