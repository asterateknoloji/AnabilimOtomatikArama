#!/usr/bin/env php
<?php
/**
 * TEMPLATE 6 OTOMATİK KURULUM
 * OtomatikArama sistemi için
 */

require_once __DIR__ . '/config.php';

echo "\n";
echo "================================================================================\n";
echo "  🚀 TEMPLATE 6 (FERHAT DAVET) OTOMATİK KURULUM\n";
echo "================================================================================\n\n";

// 1. Dialplan dosyasını SSH ile yükle
echo "[1/3] 📤 Dialplan dosyası Asterisk sunucusuna yükleniyor...\n";

$dialplan_file = __DIR__ . '/asterisk/dialplan/extensions_template_6.conf';
$dialplan_content = file_get_contents($dialplan_file);

// SSH ile bağlan
$connection = ssh2_connect(ASTERISK_SSH_HOST, ASTERISK_SSH_PORT);

if ($connection && ssh2_auth_password($connection, ASTERISK_SSH_USER, ASTERISK_SSH_PASS)) {
    echo "✅ SSH bağlantısı başarılı\n";
    
    // Dosyayı yükle
    $sftp = ssh2_sftp($connection);
    $remote_file = '/etc/asterisk/extensions_template_6.conf';
    
    $stream = fopen("ssh2.sftp://$sftp$remote_file", 'w');
    fwrite($stream, $dialplan_content);
    fclose($stream);
    
    echo "✅ Dialplan dosyası yüklendi: $remote_file\n\n";
    
    // extensions_custom.conf'a include ekle
    echo "[2/3] 📝 extensions_custom.conf'a include ekleniyor...\n";
    
    $check = ssh2_exec($connection, "grep -q 'extensions_template_6.conf' /etc/asterisk/extensions_custom.conf && echo 'EXISTS' || echo 'NOT_EXISTS'");
    stream_set_blocking($check, true);
    $result = trim(stream_get_contents($check));
    
    if ($result === 'NOT_EXISTS') {
        $add_include = ssh2_exec($connection, "echo '\n#include extensions_template_6.conf' >> /etc/asterisk/extensions_custom.conf");
        stream_set_blocking($add_include, true);
        stream_get_contents($add_include);
        echo "✅ Include satırı eklendi\n\n";
    } else {
        echo "ℹ️  Include satırı zaten mevcut\n\n";
    }
    
    // Dialplan reload
    echo "[3/3] 🔄 Asterisk dialplan reload...\n";
    $reload = ssh2_exec($connection, "asterisk -rx 'dialplan reload'");
    stream_set_blocking($reload, true);
    $output = stream_get_contents($reload);
    echo "✅ Reload tamamlandı\n";
    echo "   $output\n\n";
    
    // Kontrol
    echo "🔍 Context kontrolü...\n";
    $verify = ssh2_exec($connection, "asterisk -rx 'dialplan show auto-calls-template-6'");
    stream_set_blocking($verify, true);
    $verify_output = stream_get_contents($verify);
    
    if (strpos($verify_output, 'Template 6') !== false) {
        echo "✅✅✅ BAŞARILI! Context yüklendi!\n\n";
        
        echo "================================================================================\n";
        echo "  🎉 KURULUM TAMAMEN TAMAMLANDI!\n";
        echo "================================================================================\n\n";
        
        echo "📊 Sonraki Adımlar:\n";
        echo "   1. http://localhost:8009/otomatikarama/login.php adresini açın\n";
        echo "   2. Template 6 (Ferhat Davet) seçin\n";
        echo "   3. Excel dosyası yükleyin\n";
        echo "   4. Aramaları başlatın\n\n";
        
        echo "✅ Artık 'invalid extension' hatası OLMAYACAK!\n\n";
        exit(0);
    } else {
        echo "⚠️  Context görünmüyor, manuel kontrol gerekebilir\n\n";
    }
    
} else {
    echo "❌ SSH bağlantısı başarısız\n\n";
    echo "📖 Manuel Kurulum:\n";
    echo "   1. SSH ile sunucuya bağlanın: ssh " . ASTERISK_SSH_USER . "@" . ASTERISK_SSH_HOST . "\n";
    echo "   2. Dosyayı kopyalayın:\n";
    echo "      scp $dialplan_file " . ASTERISK_SSH_USER . "@" . ASTERISK_SSH_HOST . ":/etc/asterisk/\n";
    echo "   3. Include ekleyin:\n";
    echo "      echo '#include extensions_template_6.conf' >> /etc/asterisk/extensions_custom.conf\n";
    echo "   4. Reload:\n";
    echo "      asterisk -rx 'dialplan reload'\n\n";
    exit(1);
}
