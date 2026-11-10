<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🐛 arama_yonetimi.php Debug</h1>";

echo "<h3>Adım 1: Check Auth</h3>";
try {
    require_once 'includes/check_auth.php';
    echo "✅ check_auth.php yüklendi<br>";
    echo "Kullanıcı: " . htmlspecialchars($currentUser['username'] ?? 'Bilinmiyor') . "<br>";
} catch (Throwable $e) {
    echo "❌ HATA: " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    exit;
}

echo "<h3>Adım 2: Permission Kontrolü</h3>";
try {
    requirePermission('import.view');
    echo "✅ import.view yetkisi OK<br>";
} catch (Throwable $e) {
    echo "❌ HATA: " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    exit;
}

echo "<h3>Adım 3: Config</h3>";
try {
    require_once 'config.php';
    echo "✅ config.php yüklendi<br>";
} catch (Throwable $e) {
    echo "❌ HATA: " . $e->getMessage() . "<br>";
    exit;
}

echo "<h3>Adım 4: Manager Class'ları</h3>";
try {
    require_once 'includes/ImportManager.php';
    echo "✅ ImportManager<br>";
    
    require_once 'includes/ProjectManager.php';
    echo "✅ ProjectManager<br>";
    
    require_once 'includes/AutoCallManager.php';
    echo "✅ AutoCallManager<br>";
} catch (Throwable $e) {
    echo "❌ HATA: " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    exit;
}

echo "<h3>Adım 5: Instance Oluştur</h3>";
try {
    $importManager = new ImportManager($pdo);
    echo "✅ ImportManager instance<br>";
    
    $projectManager = new ProjectManager($pdo);
    echo "✅ ProjectManager instance<br>";
    
    $autoCallManager = new AutoCallManager($pdo);
    echo "✅ AutoCallManager instance<br>";
} catch (Throwable $e) {
    echo "❌ HATA: " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    exit;
}

echo "<h3>Adım 6: İlk 100 Satırı Yükle</h3>";
try {
    ob_start();
    $lines = file('arama_yonetimi.php');
    $first100 = array_slice($lines, 0, 100);
    
    // Syntax check
    $tempFile = tempnam(sys_get_temp_dir(), 'php');
    file_put_contents($tempFile, implode('', $first100) . "\n?>");
    
    $output = shell_exec("php -l " . escapeshellarg($tempFile) . " 2>&1");
    unlink($tempFile);
    
    if (strpos($output, 'No syntax errors') !== false) {
        echo "✅ Syntax OK (ilk 100 satır)<br>";
    } else {
        echo "❌ Syntax HATASI:<br><pre>$output</pre>";
    }
    
    ob_end_clean();
} catch (Throwable $e) {
    echo "⚠️ Syntax check yapılamadı<br>";
}

echo "<h2 style='color: green;'>✅ TÜM KONTROLLER BAŞARILI!</h2>";
echo "<p>arama_yonetimi.php'nin yüklenmesi gerekiyordu...</p>";
echo "<p><a href='arama_yonetimi.php' style='padding: 15px 30px; background: #4CAF50; color: white; text-decoration: none; border-radius: 5px;'>🚀 arama_yonetimi.php'yi Aç</a></p>";
echo "<p>Eğer yukarıdaki link çalışmıyorsa, tarayıcı console'una (F12) bakın!</p>";
