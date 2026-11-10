<?php
require_once 'config.php';
require_once 'includes/SshHelper.php';

$ssh = new SshHelper(
    ASTERISK_SSH_HOST,
    ASTERISK_SSH_PORT,
    ASTERISK_SSH_USER,
    ASTERISK_SSH_PASS
);

// Yeni macro-hangupcall-custom içeriği
$newContent = "; ============================================
; Otomatik Arama - macro-hangupcall-custom
; ============================================
[macro-hangupcall-custom]
exten => s,1,GotoIf(\$[\"\${CALL_ID}\" != \"\"]?agi,1)
exten => s,n,MacroExit()

exten => agi,1,NoOp(=== Otomatik Arama Bitti - CALL_ID: \${CALL_ID} ===)
exten => agi,n,AGI(update_call_end.php,\${CALL_ID},noanswer)
exten => agi,n,MacroExit()
";

try {
    echo "=== macro-hangupcall-custom EKLEME ===\n\n";
    
    // Önce mevcut içeriği kontrol et
    $current = $ssh->exec("grep -A 10 '^\[macro-hangupcall-custom\]' /etc/asterisk/extensions_custom.conf 2>&1");
    
    if (strpos($current, 'macro-hangupcall-custom') !== false) {
        echo "macro-hangupcall-custom zaten mevcut:\n";
        echo $current . "\n\n";
        echo "Mevcut içeriği silip yeniden eklemek ister misiniz? (y/n): ";
    } else {
        echo "macro-hangupcall-custom bulunamadı, eklenecek...\n\n";
        
        // extensions_custom.conf sonuna ekle (printf ile atomic update)
        $escapedContent = addcslashes($newContent, '$"`\\');
        $escapedContent = str_replace("\n", "\\n", $escapedContent);
        
        $cmd = sprintf(
            "printf '%s' >> /etc/asterisk/extensions_custom.conf && asterisk -rx 'dialplan reload' 2>&1",
            $escapedContent
        );
        
        $result = $ssh->exec($cmd);
        echo "Sonuç:\n" . $result . "\n\n";
        
        // Kontrol
        echo "=== KONTROL: macro-hangupcall-custom ===\n\n";
        $check = $ssh->exec("grep -A 10 '^\[macro-hangupcall-custom\]' /etc/asterisk/extensions_custom.conf 2>&1");
        echo $check . "\n";
    }
    
} catch (Exception $e) {
    echo "HATA: " . $e->getMessage() . "\n";
}
