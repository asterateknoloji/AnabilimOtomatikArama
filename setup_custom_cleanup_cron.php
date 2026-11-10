<?php
/**
 * ASTERISK CUSTOM KLASÖR TEMİZLEME CRON KURULUMU
 * 
 * Bu script:
 * 1. cleanup_custom_files.sh scriptini Asterisk sunucusuna yükler
 * 2. Gerekli izinleri verir (chmod +x)
 * 3. Cron job ekler (her gece saat 00:00)
 * 4. Log rotation ayarlarını yapar
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/vendor/autoload.php';

use phpseclib\Net\SSH2;
use phpseclib\Net\SFTP;

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html lang='tr'>
<head>
    <meta charset='UTF-8'>
    <title>Custom Klasör Temizleme Cron Kurulumu</title>
    <style>
        body { font-family: 'Courier New', monospace; background: #1e1e1e; color: #d4d4d4; padding: 20px; line-height: 1.6; }
        .container { max-width: 1200px; margin: 0 auto; background: #252526; padding: 30px; border-radius: 10px; }
        h1 { color: #4ec9b0; border-bottom: 2px solid #4ec9b0; padding-bottom: 10px; }
        h2 { color: #569cd6; margin-top: 30px; }
        .success { color: #4ec9b0; }
        .error { color: #f48771; }
        .warning { color: #dcdcaa; }
        .info { color: #9cdcfe; }
        pre { background: #1e1e1e; padding: 15px; border-radius: 5px; overflow-x: auto; border-left: 4px solid #4ec9b0; }
        .step { background: #2d2d30; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #569cd6; }
        .command { background: #1e1e1e; padding: 10px; border-radius: 3px; font-family: monospace; color: #ce9178; }
        hr { border: none; border-top: 1px solid #3e3e42; margin: 30px 0; }
    </style>
</head>
<body>
<div class='container'>";

echo "<h1>🧹 ASTERISK CUSTOM KLASÖR TEMİZLEME CRON KURULUMU</h1>";
echo "<p class='info'>Tarih: " . date('Y-m-d H:i:s') . "</p>";
echo "<hr>";

try {
    // SSH bağlantısı
    echo "<div class='step'>";
    echo "<h2>📡 1. SSH BAĞLANTISI</h2>";
    $ssh = new SSH2(ASTERISK_SSH_HOST, ASTERISK_SSH_PORT);
    
    if (!$ssh->login(ASTERISK_SSH_USER, ASTERISK_SSH_PASS)) {
        throw new Exception('SSH login başarısız!');
    }
    
    echo "<p class='success'>✅ SSH bağlantısı başarılı</p>";
    echo "<p>Host: <span class='info'>" . ASTERISK_SSH_HOST . ":" . ASTERISK_SSH_PORT . "</span></p>";
    echo "<p>User: <span class='info'>" . ASTERISK_SSH_USER . "</span></p>";
    echo "</div>";
    
    // SFTP bağlantısı
    echo "<div class='step'>";
    echo "<h2>📁 2. DOSYA YÜKLEME</h2>";
    $sftp = new SFTP(ASTERISK_SSH_HOST, ASTERISK_SSH_PORT);
    
    if (!$sftp->login(ASTERISK_SSH_USER, ASTERISK_SSH_PASS)) {
        throw new Exception('SFTP login başarısız!');
    }
    
    // Script dosyasını yükle
    $localScript = __DIR__ . '/asterisk/cleanup_custom_files.sh';
    $remoteScript = '/usr/local/bin/cleanup_custom_files.sh';
    
    if (!file_exists($localScript)) {
        throw new Exception("Script dosyası bulunamadı: $localScript");
    }
    
    echo "<p>📤 Yükleniyor: <span class='info'>$remoteScript</span></p>";
    
    if (!$sftp->put($remoteScript, $localScript, SFTP::SOURCE_LOCAL_FILE)) {
        throw new Exception("Script yüklenemedi!");
    }
    
    echo "<p class='success'>✅ Script yüklendi</p>";
    echo "</div>";
    
    // İzinleri ayarla
    echo "<div class='step'>";
    echo "<h2>🔒 3. İZİN AYARLARI</h2>";
    
    $chmod = $ssh->exec("chmod +x $remoteScript 2>&1");
    echo "<pre>chmod +x $remoteScript\n$chmod</pre>";
    
    $chown = $ssh->exec("chown root:root $remoteScript 2>&1");
    echo "<pre>chown root:root $remoteScript\n$chown</pre>";
    
    $verify = $ssh->exec("ls -lh $remoteScript 2>&1");
    echo "<pre>$verify</pre>";
    
    echo "<p class='success'>✅ İzinler ayarlandı</p>";
    echo "</div>";
    
    // Log klasörü oluştur
    echo "<div class='step'>";
    echo "<h2>📋 4. LOG KLASÖRÜ</h2>";
    
    $logDir = "/var/log/asterisk";
    $logFile = "$logDir/cleanup_custom.log";
    
    $mkdir = $ssh->exec("mkdir -p $logDir 2>&1");
    echo "<pre>mkdir -p $logDir\n$mkdir</pre>";
    
    $touchLog = $ssh->exec("touch $logFile && chmod 644 $logFile 2>&1");
    echo "<pre>touch $logFile && chmod 644 $logFile\n$touchLog</pre>";
    
    echo "<p class='success'>✅ Log dosyası hazır: <span class='info'>$logFile</span></p>";
    echo "</div>";
    
    // Cron job ekle
    echo "<div class='step'>";
    echo "<h2>⏰ 5. CRON JOB EKLEME</h2>";
    
    // Mevcut crontab'ı al
    $currentCron = $ssh->exec("crontab -l 2>&1");
    
    echo "<h3>Mevcut Crontab:</h3>";
    echo "<pre>" . htmlspecialchars($currentCron) . "</pre>";
    
    // Yeni cron job
    $cronJob = "0 0 * * * /usr/local/bin/cleanup_custom_files.sh >> /var/log/asterisk/cleanup_custom.log 2>&1";
    $cronComment = "# Asterisk custom klasör temizleme (her gece saat 00:00)";
    
    // Cron job'ın zaten var olup olmadığını kontrol et
    if (strpos($currentCron, 'cleanup_custom_files.sh') !== false) {
        echo "<p class='warning'>⚠️ Cron job zaten mevcut!</p>";
        echo "<p>Mevcut job güncelleniyor...</p>";
        
        // Eski job'ı kaldır
        $removeCron = $ssh->exec("(crontab -l 2>/dev/null | grep -v 'cleanup_custom_files.sh') | crontab - 2>&1");
        echo "<pre>Eski job kaldırıldı:\n$removeCron</pre>";
    }
    
    // Yeni cron job ekle
    $addCron = $ssh->exec("(crontab -l 2>/dev/null; echo '$cronComment'; echo '$cronJob') | crontab - 2>&1");
    echo "<pre>Yeni cron job ekleniyor:\n$addCron</pre>";
    
    // Doğrulama
    $newCron = $ssh->exec("crontab -l 2>&1");
    echo "<h3>Güncellenmiş Crontab:</h3>";
    echo "<pre>" . htmlspecialchars($newCron) . "</pre>";
    
    if (strpos($newCron, 'cleanup_custom_files.sh') !== false) {
        echo "<p class='success'>✅ Cron job başarıyla eklendi!</p>";
    } else {
        throw new Exception("Cron job eklenemedi!");
    }
    
    echo "</div>";
    
    // Log rotation ayarla
    echo "<div class='step'>";
    echo "<h2>🔄 6. LOG ROTATION</h2>";
    
    $logrotateConfig = "/etc/logrotate.d/asterisk-custom-cleanup";
    $logrotateContent = "/var/log/asterisk/cleanup_custom.log {
    weekly
    rotate 12
    compress
    delaycompress
    missingok
    notifempty
    create 0644 root root
}";
    
    // Logrotate config oluştur
    $createLogrotate = $ssh->exec("cat > $logrotateConfig << 'EOF'
$logrotateContent
EOF
chmod 644 $logrotateConfig 2>&1");
    
    echo "<pre>Logrotate config oluşturuldu: $logrotateConfig\n$createLogrotate</pre>";
    
    $verifyLogrotate = $ssh->exec("cat $logrotateConfig 2>&1");
    echo "<pre>$verifyLogrotate</pre>";
    
    echo "<p class='success'>✅ Log rotation ayarlandı</p>";
    echo "</div>";
    
    // Test çalıştırması
    echo "<div class='step'>";
    echo "<h2>🧪 7. TEST ÇALIŞTIRMASI</h2>";
    
    echo "<p class='warning'>⚠️ Test çalıştırması yapılıyor (dry-run)...</p>";
    
    $testRun = $ssh->exec("$remoteScript 2>&1");
    echo "<pre>" . htmlspecialchars($testRun) . "</pre>";
    
    echo "<p class='success'>✅ Test çalıştırması tamamlandı</p>";
    echo "</div>";
    
    // Manuel komutlar
    echo "<hr>";
    echo "<div class='step'>";
    echo "<h2>📝 MANUEL KOMUTLAR</h2>";
    
    echo "<h3>Script'i manuel çalıştırma:</h3>";
    echo "<div class='command'>/usr/local/bin/cleanup_custom_files.sh</div>";
    
    echo "<h3>Log'ları görüntüleme:</h3>";
    echo "<div class='command'>tail -f /var/log/asterisk/cleanup_custom.log</div>";
    
    echo "<h3>Son 50 log satırı:</h3>";
    echo "<div class='command'>tail -50 /var/log/asterisk/cleanup_custom.log</div>";
    
    echo "<h3>Cron job'ları listeleme:</h3>";
    echo "<div class='command'>crontab -l</div>";
    
    echo "<h3>Cron job'ı kaldırma:</h3>";
    echo "<div class='command'>(crontab -l | grep -v 'cleanup_custom_files.sh') | crontab -</div>";
    
    echo "<h3>Custom klasörü kontrol:</h3>";
    echo "<div class='command'>ls -lh /var/lib/asterisk/sounds/custom/ | grep call_</div>";
    echo "<div class='command'>find /var/lib/asterisk/sounds/custom/ -name 'call_*' -mtime +0 -ls</div>";
    
    echo "</div>";
    
    // Özet
    echo "<hr>";
    echo "<h2 class='success'>✅ KURULUM TAMAMLANDI!</h2>";
    echo "<div class='step'>";
    echo "<h3>📊 ÖZET:</h3>";
    echo "<ul>";
    echo "<li>✅ Script yüklendi: <span class='info'>/usr/local/bin/cleanup_custom_files.sh</span></li>";
    echo "<li>✅ Cron job eklendi: <span class='info'>Her gece saat 00:00</span></li>";
    echo "<li>✅ Log dosyası: <span class='info'>/var/log/asterisk/cleanup_custom.log</span></li>";
    echo "<li>✅ Temizleme hedefi: <span class='info'>/var/lib/asterisk/sounds/custom/call_*</span></li>";
    echo "<li>✅ Temizleme kriteri: <span class='info'>24 saatten eski dosyalar</span></li>";
    echo "</ul>";
    
    echo "<h3>⏰ ÇALIŞMA ZAMANI:</h3>";
    echo "<p>Script her gece <strong>saat 00:00</strong>'da otomatik olarak çalışacak.</p>";
    echo "<p>İlk çalıştırma: <strong>" . date('Y-m-d', strtotime('+1 day')) . " 00:00:00</strong></p>";
    
    echo "<h3>📋 TAKİP:</h3>";
    echo "<p>Log dosyasını takip etmek için:</p>";
    echo "<div class='command'>tail -f /var/log/asterisk/cleanup_custom.log</div>";
    
    echo "</div>";
    
    $ssh->disconnect();
    
} catch (Exception $e) {
    echo "<div class='step'>";
    echo "<h2 class='error'>❌ HATA!</h2>";
    echo "<p class='error'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}

echo "</div>
</body>
</html>";
?>
