<?php
/**
 * Tüm Bağlantıları Test Et
 */

echo "===========================================\n";
echo "BAĞLANTI TESTLERİ\n";
echo "===========================================\n\n";

// 1. PHP Sürümü
echo "1️⃣ PHP SÜRÜMÜ:\n";
echo "   Sürüm: " . phpversion() . "\n";
echo "   " . (version_compare(phpversion(), '5.4.0', '>=') ? "✅" : "❌") . " PHP 5.4+ gereksinimi\n\n";

// 2. PHP Uzantıları
echo "2️⃣ PHP UZANTILARI:\n";
$extensions = ['pdo_mysql', 'openssl', 'curl', 'mbstring', 'json'];
foreach ($extensions as $ext) {
    $loaded = extension_loaded($ext);
    echo "   " . ($loaded ? "✅" : "❌") . " {$ext}\n";
}
echo "\n";

// 3. .env Dosyası
echo "3️⃣ .ENV DOSYASI:\n";
if (!file_exists(__DIR__ . '/.env')) {
    echo "   ❌ .env dosyası bulunamadı!\n\n";
    exit(1);
}
echo "   ✅ .env dosyası bulundu\n";

require_once __DIR__ . '/includes/EnvLoader.php';
try {
    $envLoader = new EnvLoader(__DIR__ . '/.env');
    $envLoader->load();
    echo "   ✅ .env yüklendi\n\n";
} catch (Exception $e) {
    echo "   ❌ .env yüklenemedi: " . $e->getMessage() . "\n\n";
    exit(1);
}

// 4. MySQL Bağlantısı
echo "4️⃣ MYSQL BAĞLANTISI:\n";
$dbHost = EnvLoader::get('DB_HOST');
$dbName = EnvLoader::get('DB_NAME');
$dbUser = EnvLoader::get('DB_USER');
$dbPass = EnvLoader::get('DB_PASS');

echo "   Host: {$dbHost}\n";
echo "   Database: {$dbName}\n";
echo "   User: {$dbUser}\n";

try {
    $pdo = new PDO(
        "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "   ✅ MySQL bağlantısı başarılı\n";
    
    // Tablo kontrolü
    $tables = ['CALLS', 'IMPORT_HISTORY', 'MESSAGE_TEMPLATES', 'PROJECTS', 'users'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '{$table}'");
        $exists = $stmt->fetch();
        echo "   " . ($exists ? "✅" : "❌") . " {$dbName}.{$table}\n";
    }
    echo "\n";
} catch (PDOException $e) {
    echo "   ❌ MySQL bağlantı hatası: " . $e->getMessage() . "\n\n";
}

// 5. SSH Bağlantısı
echo "5️⃣ SSH BAĞLANTISI (Asterisk):\n";
$sshHost = EnvLoader::get('ASTERISK_SSH_HOST');
$sshPort = EnvLoader::get('ASTERISK_SSH_PORT');
$sshUser = EnvLoader::get('ASTERISK_SSH_USER');

echo "   Host: {$sshHost}:{$sshPort}\n";
echo "   User: {$sshUser}\n";

// Port açık mı test et
$connection = @fsockopen($sshHost, $sshPort, $errno, $errstr, 5);
if ($connection) {
    echo "   ✅ SSH portu açık\n";
    fclose($connection);
} else {
    echo "   ❌ SSH portu kapalı: {$errstr}\n";
}
echo "\n";

// 6. Klasör İzinleri
echo "6️⃣ KLASÖR İZİNLERİ:\n";
$folders = ['logs', 'tmp', 'audio', 'uploads'];
foreach ($folders as $folder) {
    $path = __DIR__ . '/' . $folder;
    if (!is_dir($path)) {
        mkdir($path, 0777, true);
        echo "   📁 {$folder}/ oluşturuldu\n";
    }
    $writable = is_writable($path);
    echo "   " . ($writable ? "✅" : "❌") . " {$folder}/ " . ($writable ? "yazılabilir" : "yazılamıyor!") . "\n";
}
echo "\n";

// 7. Windows TTS
echo "7️⃣ WINDOWS TTS:\n";
$psCommand = 'Add-Type -AssemblyName System.Speech; $synth = New-Object System.Speech.Synthesis.SpeechSynthesizer; $synth.GetInstalledVoices().Count';
$output = shell_exec("powershell -Command \"{$psCommand}\"");
$voiceCount = intval(trim($output));
echo "   " . ($voiceCount > 0 ? "✅" : "❌") . " {$voiceCount} TTS sesi bulundu\n\n";

// 8. Task Scheduler
echo "8️⃣ TASK SCHEDULER:\n";
$tasks = ['OtomatikAramaUnlock', 'OtomatikAramaTekrar', 'OtomatikAramaZamanlanmis'];
foreach ($tasks as $taskName) {
    $cmd = "schtasks /query /tn \"{$taskName}\" 2>nul";
    exec($cmd, $output, $returnCode);
    echo "   " . ($returnCode == 0 ? "✅" : "❌") . " {$taskName}\n";
}
echo "\n";

// SONUÇ
echo "===========================================\n";
echo "TEST TAMAMLANDI!\n";
echo "===========================================\n";
