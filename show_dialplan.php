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
    echo "=== DIALPLAN: from-internal context - h extension ===\n\n";
    
    $result = $ssh->exec("asterisk -rx 'dialplan show h@from-internal' 2>&1");
    echo $result;
    echo "\n\n";
    
    echo "=== DIALPLAN: from-internal-xfer context - h extension ===\n\n";
    $result2 = $ssh->exec("asterisk -rx 'dialplan show h@from-internal-xfer' 2>&1");
    echo $result2;
    
} catch (Exception $e) {
    echo "HATA: " . $e->getMessage() . "\n";
}
