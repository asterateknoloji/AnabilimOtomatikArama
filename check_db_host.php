<?php
require_once 'config.php';

echo "<h2>Database Bağlantı Bilgileri</h2>";
echo "<pre>";
echo "DB_HOST: " . EnvLoader::get('DB_HOST') . "\n";
echo "DB_NAME: " . EnvLoader::get('DB_NAME') . "\n";
echo "DB_USER: " . EnvLoader::get('DB_USER') . "\n";
echo "\nBağlantı Testi:\n";

try {
    // Bağlı olduğumuz host'u öğren
    $stmt = $pdo->query("SELECT @@hostname as hostname, USER() as current_user, DATABASE() as current_db");
    $info = $stmt->fetch();
    
    echo "✅ Bağlantı başarılı!\n";
    echo "  Hostname: {$info['hostname']}\n";
    echo "  Current User: {$info['current_user']}\n";
    echo "  Current DB: {$info['current_db']}\n";
    
} catch (Exception $e) {
    echo "❌ Hata: " . $e->getMessage() . "\n";
}
echo "</pre>";

echo "<p><a href='login_debug.php'>← Login Debug'a Dön</a></p>";
?>
