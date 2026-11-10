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
    echo "=== from-internal-noxfer-custom EKLEME ===\n\n";
    
    // Önce kontrol
    $check = $ssh->exec("grep '^\[from-internal-noxfer-custom\]' /etc/asterisk/extensions_custom.conf 2>&1");
    
    if (strpos($check, 'from-internal-noxfer-custom') !== false) {
        echo "from-internal-noxfer-custom zaten mevcut!\n";
        return;
    }
    
    // Yeni content
    $content = '
; ============================================
; Otomatik Arama - from-internal-noxfer-custom
; ============================================
[from-internal-noxfer-custom]
exten => h,1,GotoIf($["${CALL_ID}" != ""]?agi,1)
exten => h,n,Hangup()

exten => agi,1,NoOp(=== Otomatik Arama Bitti - CALL_ID: ${CALL_ID} ===)
exten => agi,n,AGI(update_call_end.php,${CALL_ID},noanswer)
exten => agi,n,Hangup()
';
    
    // Octal escape
    $octalContent = '';
    for ($i = 0; $i < strlen($content); $i++) {
        $char = $content[$i];
        if ($char === '$') {
            $octalContent .= '\\044';
        } elseif ($char === '"') {
            $octalContent .= '\\042';
        } elseif ($char === '\\') {
            $octalContent .= '\\134';
        } elseif ($char === "\n") {
            $octalContent .= '\\n';
        } else {
            $octalContent .= $char;
        }
    }
    
    $cmd = "printf '{$octalContent}' >> /etc/asterisk/extensions_custom.conf 2>&1";
    $result = $ssh->exec($cmd);
    echo "Eklendi: " . ($result ? $result : "OK") . "\n\n";
    
    // Reload
    echo "Asterisk dialplan reload...\n";
    $reload = $ssh->exec("asterisk -rx 'dialplan reload' 2>&1");
    echo $reload . "\n\n";
    
    // Kontrol
    echo "=== KONTROL ===\n\n";
    $final = $ssh->exec("asterisk -rx 'dialplan show h@from-internal' | head -10 2>&1");
    echo $final . "\n";
    
} catch (Exception $e) {
    echo "HATA: " . $e->getMessage() . "\n";
}
