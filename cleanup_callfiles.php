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
    echo "=== CALL FILES CLEANUP ===\n\n";
    
    // /var/spool/asterisk/outgoing/ içindeki call file'ları listele
    echo "1. Mevcut call file'lar:\n";
    $list = $ssh->exec("ls -lh /var/spool/asterisk/outgoing/call_*.call 2>&1");
    echo $list . "\n\n";
    
    if (strpos($list, 'No such file') === false && strpos($list, 'call_') !== false) {
        echo "2. Call file'lar SİLİNİYOR...\n";
        $result = $ssh->exec("rm -f /var/spool/asterisk/outgoing/call_*.call 2>&1");
        echo "Sonuç: " . ($result ? $result : "Silindi!") . "\n\n";
        
        echo "3. Kontrol:\n";
        $check = $ssh->exec("ls -lh /var/spool/asterisk/outgoing/call_*.call 2>&1");
        echo $check . "\n";
    } else {
        echo "2. Silinecek call file yok.\n";
    }
    
} catch (Exception $e) {
    echo "HATA: " . $e->getMessage() . "\n";
}
