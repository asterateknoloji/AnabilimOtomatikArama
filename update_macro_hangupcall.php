<?php
require_once 'config.php';
require_once 'includes/SshHelper.php';

$ssh = new SshHelper(
    ASTERISK_SSH_HOST,
    ASTERISK_SSH_PORT,
    ASTERISK_SSH_USER,
    ASTERISK_SSH_PASS
);

try {
    echo "=== macro-hangupcall MEVCUT İÇERİĞİ ===\n\n";
    $current = $ssh->exec("grep -A 20 '^\[macro-hangupcall\]' /etc/asterisk/extensions.conf 2>&1");
    echo $current;
    echo "\n\n";
    
    if (strpos($current, 'macro-hangupcall') === false) {
        echo "macro-hangupcall extensions.conf içinde bulunamadı, extensions_additional.conf kontrol ediliyor...\n\n";
        $current = $ssh->exec("grep -A 20 '^\[macro-hangupcall\]' /etc/asterisk/extensions_additional.conf 2>&1");
        echo $current;
    }
    
} catch (Exception $e) {
    echo "HATA: " . $e->getMessage() . "\n";
}
