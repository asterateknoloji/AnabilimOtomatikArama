<?php
require_once 'config.php';

echo "<h1>USER_SESSIONS Tablo Kontrolü</h1>";
echo "<hr>";

echo "<h2>1. Tablo Yapısı</h2>";
echo "<pre>";
try {
    $stmt = $pdo->query("DESCRIBE USER_SESSIONS");
    $columns = $stmt->fetchAll();
    
    echo "Kolonlar:\n";
    foreach ($columns as $col) {
        echo "  - {$col['Field']} ({$col['Type']})";
        if ($col['Key'] == 'PRI') echo " [PRIMARY KEY]";
        if ($col['Null'] == 'NO') echo " [NOT NULL]";
        echo "\n";
    }
} catch (Exception $e) {
    echo "❌ Hata: " . $e->getMessage() . "\n";
}
echo "</pre>";

echo "<h2>2. Kayıt Sayısı</h2>";
echo "<pre>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM USER_SESSIONS");
    $count = $stmt->fetchColumn();
    echo "Toplam kayıt: {$count}\n";
    
    if ($count > 0) {
        echo "\nSon 5 kayıt:\n";
        $stmt = $pdo->query("SELECT * FROM USER_SESSIONS ORDER BY created_at DESC LIMIT 5");
        while ($row = $stmt->fetch()) {
            echo "\n";
            echo "  ID: {$row['id']}\n";
            echo "  User ID: {$row['user_id']}\n";
            echo "  Session Token: " . substr($row['session_token'], 0, 20) . "...\n";
            echo "  IP: {$row['ip_address']}\n";
            echo "  Active: {$row['is_active']}\n";
            echo "  Created: {$row['created_at']}\n";
            echo "  Expires: {$row['expires_at']}\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Hata: " . $e->getMessage() . "\n";
}
echo "</pre>";

echo "<h2>3. Test: Session Ekleme</h2>";
echo "<pre>";
try {
    $testToken = 'test_' . time();
    $hashedToken = hash('sha256', $testToken);
    $expiresAt = date('Y-m-d H:i:s', time() + 3600);
    
    $stmt = $pdo->prepare("
        INSERT INTO USER_SESSIONS 
        (user_id, session_token, ip_address, user_agent, last_activity, created_at, expires_at, is_active) 
        VALUES (?, ?, ?, ?, NOW(), NOW(), ?, 1)
    ");
    
    $result = $stmt->execute([
        5,
        $hashedToken,
        '127.0.0.1',
        'Test User Agent',
        $expiresAt
    ]);
    
    if ($result) {
        echo "✅ Test session başarıyla eklendi!\n";
        echo "  Token: {$testToken}\n";
        echo "  Hashed: " . substr($hashedToken, 0, 20) . "...\n";
        
        // Şimdi kontrol et
        $stmt = $pdo->prepare("SELECT * FROM USER_SESSIONS WHERE session_token = ?");
        $stmt->execute([$hashedToken]);
        $session = $stmt->fetch();
        
        if ($session) {
            echo "\n✅ Session tabloda bulundu:\n";
            echo "  ID: {$session['id']}\n";
            echo "  User ID: {$session['user_id']}\n";
            echo "  Active: {$session['is_active']}\n";
        } else {
            echo "\n❌ Session tabloda bulunamadı!\n";
        }
    } else {
        echo "❌ Session eklenemedi!\n";
    }
} catch (Exception $e) {
    echo "❌ Hata: " . $e->getMessage() . "\n";
    echo "Satır: " . $e->getLine() . "\n";
}
echo "</pre>";

echo "<h2>4. Çözüm Önerisi</h2>";
echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; border: 1px solid #ffc107;'>";
echo "<p><strong>SEÇENEK 1: USER_SESSIONS Tablosunu Sil (Önerilen)</strong></p>";
echo "<p>Eski sistemde bu tablo yok, PHP session yeterli.</p>";
echo "<form method='POST'>";
echo "<button type='submit' name='drop_table' style='padding: 10px 20px; background: #dc3545; color: white; border: none; border-radius: 5px; cursor: pointer;'>USER_SESSIONS Tablosunu Sil</button>";
echo "</form>";
echo "</div>";

// Tablo silme işlemi
if (isset($_POST['drop_table'])) {
    try {
        $pdo->exec("DROP TABLE USER_SESSIONS");
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin-top: 10px;'>";
        echo "✅ USER_SESSIONS tablosu silindi!<br>";
        echo "Şimdi login çalışacak. <a href='simple_login_test.php'>Test edin →</a>";
        echo "</div>";
    } catch (Exception $e) {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin-top: 10px;'>";
        echo "❌ Hata: " . $e->getMessage();
        echo "</div>";
    }
}

echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
    pre { background: white; padding: 15px; border-radius: 5px; border: 1px solid #ddd; }
    h2 { color: #667eea; }
</style>";
