<?php
set_time_limit(300); // 5 dakika timeout

require_once 'config.php';
require_once 'includes/SshHelper.php';

$sshHost = EnvLoader::get('ASTERISK_SSH_HOST');
$sshPort = EnvLoader::get('ASTERISK_SSH_PORT', 22);
$sshUser = EnvLoader::get('ASTERISK_SSH_USER');
$sshPass = EnvLoader::get('ASTERISK_SSH_PASS');

echo "=== ASTERISK YÜKLEME ===\n\n";
echo "SSH Bilgileri:\n";
echo "  Host: {$sshHost}:{$sshPort}\n";
echo "  User: {$sshUser}\n\n";

$ssh = new SshHelper($sshHost, $sshPort, $sshUser, $sshPass);

try {
    echo "🔌 SSH bağlantısı test ediliyor...\n";
    
    // Test komutu ile bağlantı kontrol
    try {
        $testOutput = $ssh->exec("echo 'SSH OK'");
        if (strpos($testOutput, 'SSH OK') === false) {
            throw new Exception('SSH test başarısız');
        }
        echo "✅ SSH bağlantısı başarılı!\n\n";
    } catch (Exception $e) {
        throw new Exception('SSH bağlantısı kurulamadı: ' . $e->getMessage());
    }
    
    // 1. AGI Scriptlerini yükle
    echo "=== 1. AGI SCRİPTLERİ YÜKLEME ===\n";
    $localAgiDir = __DIR__ . '/asterisk/agi';
    $remoteAgiDir = '/var/lib/asterisk/agi-bin';
    
    $agiScripts = [
        'update_call_status.php',
        'update_call_dtmf.php',
        'update_call_end.php'
    ];
    
    foreach ($agiScripts as $script) {
        $localPath = $localAgiDir . '/' . $script;
        $remotePath = $remoteAgiDir . '/' . $script;
        
        if (!file_exists($localPath)) {
            echo "  ⚠️ {$script} - Yerel dosya bulunamadı\n";
            continue;
        }
        
        echo "  📤 {$script} yükleniyor...\n";
        try {
            $ssh->sendFile($localPath, $remotePath);
            $ssh->exec("chmod +x {$remotePath}");
            $ssh->exec("chown asterisk:asterisk {$remotePath}");
            echo "  ✅ {$script} yüklendi\n";
        } catch (Exception $e) {
            echo "  ❌ {$script} yükleme başarısız: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n=== 2. DIALPLAN DOSYALARI YÜKLEME ===\n";
    $localDialplanDir = __DIR__ . '/asterisk/dialplan';
    $remoteDialplanDir = '/etc/asterisk';
    
    $dialplanFiles = glob($localDialplanDir . '/extensions_template_*.conf');
    
    foreach ($dialplanFiles as $localPath) {
        $fileName = basename($localPath);
        $remotePath = $remoteDialplanDir . '/' . $fileName;
        
        echo "  📤 {$fileName} yükleniyor...\n";
        try {
            $ssh->sendFile($localPath, $remotePath);
            $ssh->exec("chown asterisk:asterisk {$remotePath}");
            echo "  ✅ {$fileName} yüklendi\n";
        } catch (Exception $e) {
            echo "  ❌ {$fileName} yükleme başarısız: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n=== 3. EXTENSIONS_CUSTOM.CONF GÜNCELLEME ===\n";
    
    // extensions_custom.conf dosyasını kontrol et
    $customConfPath = '/etc/asterisk/extensions_custom.conf';
    $output = $ssh->exec("test -f {$customConfPath} && echo 'EXISTS' || echo 'NOT_EXISTS'");
    
    if (trim($output) === 'NOT_EXISTS') {
        echo "  ⚠️ extensions_custom.conf bulunamadı, oluşturuluyor...\n";
        $ssh->exec("touch {$customConfPath}");
        $ssh->exec("chown asterisk:asterisk {$customConfPath}");
    }
    
    // Mevcut içeriği al
    $currentContent = $ssh->exec("cat {$customConfPath}");
    
    // Template include'larını ekle (yoksa)
    $includes = [
        '#include "extensions_template_1.conf"',
        '#include "extensions_template_2.conf"',
        '#include "extensions_template_3.conf"'
    ];
    
    $needsUpdate = false;
    foreach ($includes as $include) {
        if (strpos($currentContent, $include) === false) {
            $needsUpdate = true;
            break;
        }
    }
    
    if ($needsUpdate) {
        echo "  📝 extensions_custom.conf güncelleniyor...\n";
        
        // Yeni içerik oluştur
        $newContent = trim($currentContent) . "\n\n";
        $newContent .= "; === Template Include'ları (Otomatik Arama Sistemi) ===\n";
        foreach ($includes as $include) {
            if (strpos($currentContent, $include) === false) {
                $newContent .= $include . "\n";
                echo "  + {$include}\n";
            }
        }
        
        // Geçici dosya oluştur
        $tempFile = sys_get_temp_dir() . '/extensions_custom_' . time() . '.conf';
        file_put_contents($tempFile, $newContent);
        
        // Yükle
        try {
            $ssh->sendFile($tempFile, $customConfPath);
            $ssh->exec("chown asterisk:asterisk {$customConfPath}");
            echo "  ✅ extensions_custom.conf güncellendi\n";
            unlink($tempFile);
        } catch (Exception $e) {
            echo "  ❌ Güncelleme başarısız: " . $e->getMessage() . "\n";
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    } else {
        echo "  ℹ️ extensions_custom.conf zaten güncel\n";
    }
    
    echo "\n=== 4. ASTERISK RELOAD ===\n";
    $output = $ssh->exec("asterisk -rx 'dialplan reload'");
    echo "  {$output}\n";
    
    echo "\n" . str_repeat('=', 50) . "\n";
    echo "✅ YÜKLEME TAMAMLANDI!\n";
    echo str_repeat('=', 50) . "\n\n";
    
    echo "Test için:\n";
    echo "1. Yeni bir arama başlatın\n";
    echo "2. Telefonu reddedin\n";
    echo "3. CALLS tablosunu kontrol edin:\n";
    echo "   - SipCode=486 olmalı\n";
    echo "   - Durum dolu olmalı\n";
    echo "   - Locked=0 olmalı\n";
    echo "   - CallTime 60 dakika ileride olmalı\n";
    
} catch (Exception $e) {
    echo "\n❌ HATA: " . $e->getMessage() . "\n";
    echo "\nDetaylar:\n";
    echo "  - SSH bağlantısı kontrol edin\n";
    echo "  - .env dosyasındaki bilgileri kontrol edin\n";
    echo "  - Firewall ayarlarını kontrol edin\n";
}
