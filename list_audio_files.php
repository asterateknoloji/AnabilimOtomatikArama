<?php
require_once 'includes/check_auth.php';
require_once 'config.php';
require_once 'includes/SshHelper.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // SSH ile Asterisk sunucusundan ses dosyalarını listele
    $ssh = new SshHelper(
        ASTERISK_SSH_HOST,
        ASTERISK_SSH_PORT,
        ASTERISK_SSH_USER,
        ASTERISK_SSH_PASS
    );
    
    $command = 'ls -1 /var/lib/asterisk/sounds/otomatik/*.wav 2>/dev/null | xargs -n 1 basename';
    $output = $ssh->exec($command);
    
    $files = array_filter(explode("\n", trim($output)));
    
    echo json_encode([
        'success' => true,
        'files' => $files
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'files' => []
    ]);
}
