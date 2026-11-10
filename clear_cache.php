<?php
/**
 * PHP Opcache ve Session Temizleyici
 */

echo "<h1>Cache Temizleme</h1>";
echo "<hr>";

// 1. Opcache temizle
echo "<h2>1. Opcache</h2>";
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "<p style='color: green;'>✅ Opcache temizlendi!</p>";
} else {
    echo "<p style='color: orange;'>⚠️ Opcache aktif değil</p>";
}

// 2. Session temizle
echo "<h2>2. Session</h2>";
session_start();
session_destroy();
echo "<p style='color: green;'>✅ Session temizlendi!</p>";

// 3. Auth.php kontrolü
echo "<h2>3. Auth.php Dosya Kontrolü</h2>";
$authFile = __DIR__ . '/includes/Auth.php';
$content = file_get_contents($authFile);

// isLoggedIn metodunu kontrol et
if (strpos($content, 'SHOW TABLES LIKE \'USER_SESSIONS\'') !== false) {
    echo "<p style='color: green;'>✅ Auth.php güncellemesi var (USER_SESSIONS kontrolü)</p>";
} else {
    echo "<p style='color: red;'>❌ Auth.php güncellemesi yok! Dosya yenilenmemiş!</p>";
}

// Dosya zamanı
$modTime = filemtime($authFile);
echo "<p>Dosya değiştirilme zamanı: " . date('Y-m-d H:i:s', $modTime) . "</p>";
echo "<p>Şu anki zaman: " . date('Y-m-d H:i:s') . "</p>";

// 4. Test
echo "<h2>4. Test</h2>";
echo "<p><a href='simple_login_test.php' style='padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px;'>Login Test Sayfasına Git →</a></p>";

echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
    h1 { color: #333; }
    h2 { color: #667eea; border-bottom: 2px solid #667eea; padding-bottom: 5px; }
</style>";
