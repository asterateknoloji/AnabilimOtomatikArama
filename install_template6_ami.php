#!/usr/bin/env php
<?php
/**
 * TEMPLATE 6 SÜPER OTOMATİK KURULUM - AMI + WEB
 */

require_once __DIR__ . '/config.php';

echo "\n🤖 TEMPLATE 6 (FERHAT DAVET) TAM OTOMATİK KURULUM\n\n";

$dialplan_file = __DIR__ . '/asterisk/dialplan/extensions_template_6.conf';
$dialplan = file_get_contents($dialplan_file);

// AMI bağlantısı
echo "[1/4] 🔌 Asterisk AMI'ye bağlanılıyor...\n";
$socket = @fsockopen(AMI_HOST, AMI_PORT, $errno, $errstr, 10);
if (!$socket) die("❌ AMI bağlantısı başarısız\n");

fgets($socket, 1024); // Banner
echo "✅ AMI bağlandı\n\n";

// Login
echo "[2/4] 🔑 AMI Login...\n";
fwrite($socket, "Action: Login\r\nUsername: " . AMI_USER . "\r\nSecret: " . AMI_PASS . "\r\nEvents: off\r\n\r\n");
$resp = '';
while ($line = fgets($socket, 1024)) {
    $resp .= $line;
    if (trim($line) == '') break;
}
if (strpos($resp, 'Success') === false) die("❌ Login başarısız\n");
echo "✅ Login başarılı\n\n";

// Dialplan yükle
echo "[3/4] 📝 Dialplan extensions_custom.conf'a ekleniyor...\n";

// GetConfig ile mevcut dosyayı al
fwrite($socket, "Action: GetConfig\r\nFilename: extensions_custom.conf\r\n\r\n");
$current = '';
while ($line = fgets($socket, 1024)) {
    $current .= $line;
    if (trim($line) == '' && strpos($current, 'Response:') !== false) break;
}

// Eğer template-6 yoksa ekle
if (strpos($current, 'auto-calls-template-6') === false) {
    // UpdateConfig ile ekle
    $lines = explode("\n", $dialplan);
    $action_num = 0;
    
    $update = "Action: UpdateConfig\r\n";
    $update .= "SrcFilename: extensions_custom.conf\r\n";
    $update .= "DstFilename: extensions_custom.conf\r\n";
    $update .= "Reload: yes\r\n";
    
    // İçeriği dosyaya ekle (Append olarak)
    foreach ($lines as $line) {
        if (trim($line)) {
            $update .= sprintf("Action-%06d: Append\r\n", $action_num);
            $update .= sprintf("Cat-%06d: \r\n", $action_num);
            $update .= sprintf("Var-%06d: %s\r\n", $action_num, addslashes($line));
            $action_num++;
        }
    }
    
    $update .= "\r\n";
    fwrite($socket, $update);
    
    $resp = '';
    while ($line = fgets($socket, 1024)) {
        $resp .= $line;
        if (trim($line) == '') break;
    }
    
    echo "ℹ️  UpdateConfig yanıtı alındı\n\n";
} else {
    echo "ℹ️  Template 6 zaten mevcut\n\n";
}

// Reload
echo "[4/4] 🔄 Dialplan reload...\n";
fwrite($socket, "Action: Command\r\nCommand: dialplan reload\r\n\r\n");
$resp = '';
while ($line = fgets($socket, 1024)) {
    $resp .= $line;
    if (strpos($line, '--END COMMAND--') !== false) break;
}
echo "✅ Reload tamamlandı\n\n";

// Kontrol
echo "🔍 Context kontrolü...\n";
fwrite($socket, "Action: Command\r\nCommand: dialplan show auto-calls-template-6\r\n\r\n");
$resp = '';
while ($line = fgets($socket, 1024)) {
    $resp .= $line;
    if (strpos($line, '--END COMMAND--') !== false) break;
}

fwrite($socket, "Action: Logoff\r\n\r\n");
fclose($socket);

if (strpos($resp, 'Template 6') !== false || strpos($resp, "'s' =>") !== false) {
    echo "✅✅✅ BAŞARILI! Context yüklendi!\n\n";
    echo "================================================================================\n";
    echo "  🎉 KURULUM TAMAMEN TAMAMLANDI!\n";
    echo "================================================================================\n\n";
    echo "📋 KULLANMAYA BAŞLAYIN:\n";
    echo "   → http://localhost:8009/otomatikarama/login.php\n\n";
    exit(0);
} else {
    echo "⚠️  Context tespit edilemedi - Dialplan içeriği:\n\n";
    echo $dialplan;
    echo "\n\n📖 Lütfen bunu manuel olarak /etc/asterisk/extensions_custom.conf'a ekleyin\n\n";
    exit(1);
}
