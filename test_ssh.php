<?php
/**
 * SSH Bağlantı Testi
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/SshHelper.php';

echo "=== SSH BAĞLANTI TESTİ ===\n\n";

echo "Host: " . ASTERISK_SSH_HOST . "\n";
echo "Port: " . ASTERISK_SSH_PORT . "\n";
echo "User: " . ASTERISK_SSH_USER . "\n\n";

echo "1️⃣ BAĞLANTI TESTİ...\n";

try {
    // Basit komut test et
    $result = SshHelper::exec('echo "SSH Bağlantı Testi"');
    echo "   ✅ SSH bağlantısı başarılı!\n";
    echo "   Yanıt: " . trim($result) . "\n\n";
    
    // Asterisk spool klasörü kontrol
    echo "2️⃣ ASTERISK KLASÖR KONTROLÜ...\n";
    $spoolPath = ASTERISK_SPOOL_PATH;
    $result = SshHelper::exec("ls -la {$spoolPath} 2>&1");
    
    if (strpos($result, 'No such file') !== false) {
        echo "   ❌ {$spoolPath} bulunamadı!\n";
    } else {
        echo "   ✅ {$spoolPath} erişilebilir\n";
    }
    
    // Ses klasörü kontrol
    $soundsPath = ASTERISK_SOUNDS_PATH;
    $result = SshHelper::exec("ls -la {$soundsPath} 2>&1");
    
    if (strpos($result, 'No such file') !== false) {
        echo "   ❌ {$soundsPath} bulunamadı!\n";
        echo "   Oluşturulsun mu? (y/n): ";
    } else {
        echo "   ✅ {$soundsPath} erişilebilir\n";
    }
    
    echo "\n3️⃣ AGI KLASÖRÜ KONTROLÜ...\n";
    $agiPath = '/var/lib/asterisk/agi-bin/';
    $result = SshHelper::exec("ls -la {$agiPath}update_call_*.php 2>&1");
    
    if (strpos($result, 'update_call_status.php') !== false) {
        echo "   ✅ AGI scriptleri bulundu\n";
        echo "   - update_call_status.php\n";
        echo "   - update_call_dtmf.php\n";
        echo "   - update_call_end.php\n";
    } else {
        echo "   ⚠️ AGI scriptleri bulunamadı\n";
        echo "   upload_all_to_asterisk.php ile yükleyin\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ SSH bağlantı hatası!\n";
    echo "   Hata: " . $e->getMessage() . "\n\n";
    
    echo "🔧 SORUN GİDERME:\n";
    echo "   1. SSH portu açık mı?\n";
    echo "      Test-NetConnection -ComputerName " . ASTERISK_SSH_HOST . " -Port " . ASTERISK_SSH_PORT . "\n\n";
    echo "   2. SSH kullanıcı/şifre doğru mu?\n";
    echo "      .env dosyasını kontrol edin\n\n";
    echo "   3. Firewall kuralı var mı?\n";
}

echo "\n=== TEST TAMAMLANDI ===\n";
