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
    echo "=== macro-hangupcall-custom YENİDEN YAZILIYOR ===\n\n";
    
    // 1. Boş başlığı ve önceki from-internal-custom comment'ini sil
    echo "1. Eski içerik temizleniyor...\n";
    $ssh->exec("sed -i '/^; Otomatik Arama - macro-hangupcall-custom/,\$d' /etc/asterisk/extensions_custom.conf 2>&1");
    
    // 2. Doğru içeriği ekle (heredoc ile)
    echo "2. Yeni içerik ekleniyor...\n";
    
    // NOT: Asterisk değişkenleri için \ kullanıyoruz
    $content = '
; ============================================
; Otomatik Arama - macro-hangupcall-custom
; ============================================
[macro-hangupcall-custom]
exten => s,1,GotoIf($["${CALL_ID}" != ""]?agi,1)
exten => s,n,MacroExit()

exten => agi,1,NoOp(=== Otomatik Arama Bitti - CALL_ID: ${CALL_ID} ===)
exten => agi,n,AGI(update_call_end.php,${CALL_ID},noanswer)
exten => agi,n,MacroExit()
';
    
    // Dosyaya yaz (octal escape kullanarak)
    $octalContent = '';
    for ($i = 0; $i < strlen($content); $i++) {
        $char = $content[$i];
        if ($char === '$') {
            $octalContent .= '\\044';  // $ için octal
        } elseif ($char === '"') {
            $octalContent .= '\\042';  // " için octal
        } elseif ($char === '\\') {
            $octalContent .= '\\134';  // \ için octal
        } elseif ($char === "\n") {
            $octalContent .= '\\n';
        } else {
            $octalContent .= $char;
        }
    }
    
    $cmd = "printf '{$octalContent}' >> /etc/asterisk/extensions_custom.conf 2>&1";
    $result = $ssh->exec($cmd);
    echo "Sonuç: " . ($result ? $result : "OK") . "\n\n";
    
    // 3. Reload
    echo "3. Asterisk dialplan reload...\n";
    $reload = $ssh->exec("asterisk -rx 'dialplan reload' 2>&1");
    echo $reload . "\n\n";
    
    // 4. Kontrol
    echo "=== KONTROL ===\n\n";
    $check = $ssh->exec("tail -15 /etc/asterisk/extensions_custom.conf 2>&1");
    echo $check . "\n";
    
} catch (Exception $e) {
    echo "HATA: " . $e->getMessage() . "\n";
}
