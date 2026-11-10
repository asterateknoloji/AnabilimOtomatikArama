<?php
/**
 * Template 2 Dialplan'ı Asterisk'e Yükle
 */

require_once 'config.php';
require_once 'includes/SshHelper.php';

echo "<!DOCTYPE html>";
echo "<html lang='tr'><head><meta charset='UTF-8'>";
echo "<title>Dialplan Yükle</title>";
echo "<style>
    body { font-family: Arial; margin: 20px; background: #f5f5f5; }
    .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
    h1 { color: #333; border-bottom: 3px solid #667eea; padding-bottom: 10px; }
    .success { background: #d4edda; color: #155724; padding: 15px; margin: 10px 0; border-radius: 5px; }
    .error { background: #f8d7da; color: #721c24; padding: 15px; margin: 10px 0; border-radius: 5px; }
    .info { background: #d1ecf1; color: #0c5460; padding: 15px; margin: 10px 0; border-radius: 5px; }
    .btn { display: inline-block; padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; border: none; cursor: pointer; margin: 5px; }
    pre { background: #f4f4f4; padding: 15px; border-radius: 5px; overflow-x: auto; font-size: 12px; }
    .warning { background: #fff3cd; color: #856404; padding: 15px; margin: 10px 0; border-radius: 5px; }
</style></head><body><div class='container'>";

echo "<h1>📤 Template 2 Dialplan Upload</h1>";

// Dosya yolu
$localFile = __DIR__ . '/asterisk/dialplan/extensions_template_2.conf';
$remoteFile = '/etc/asterisk/extensions_template_2.conf';

// Dosya var mı?
if (!file_exists($localFile)) {
    echo "<div class='error'>❌ Local dosya bulunamadı: $localFile</div>";
    echo "</div></body></html>";
    exit;
}

echo "<div class='info'>";
echo "<h3>📄 Dosya Bilgisi:</h3>";
echo "<p><strong>Local:</strong> $localFile</p>";
echo "<p><strong>Remote:</strong> $remoteFile</p>";
echo "<p><strong>Boyut:</strong> " . filesize($localFile) . " bytes</p>";
echo "</div>";

// Dosya içeriğini göster
echo "<h3>📝 Dialplan İçeriği:</h3>";
echo "<pre>" . htmlspecialchars(file_get_contents($localFile)) . "</pre>";

// Upload butonu
if (!isset($_POST['upload'])) {
    echo "<div class='warning'>";
    echo "<h3>⚠️ Dikkat!</h3>";
    echo "<p>Bu işlem Asterisk dialplan dosyasını güncelleyecek ve Asterisk'i reload edecek.</p>";
    echo "<ul>";
    echo "<li><strong>CDR(dst)</strong> artık telefon numarası olacak</li>";
    echo "<li><strong>CDR(userfield)</strong> artık Call ID olacak</li>";
    echo "<li>Aktif aramalar etkilenmez</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<form method='POST'>";
    echo "<button type='submit' name='upload' class='btn' style='font-size: 16px; padding: 15px 30px;'>📤 Asterisk'e Yükle ve Reload Et</button>";
    echo "</form>";
} else {
    // Upload işlemi
    echo "<div style='background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0;'>";
    echo "<h3>🔄 Upload İşlemi Başlatılıyor...</h3>";
    
    try {
        // SSH bağlantısı
        $ssh = new SshHelper(
            ASTERISK_SSH_HOST,
            ASTERISK_SSH_PORT,
            ASTERISK_SSH_USER,
            ASTERISK_SSH_PASS
        );
        
        echo "<p>✅ SSH bağlantısı başarılı</p>";
        
        // 1. Mevcut dosyanın yedeğini al
        $backupFile = $remoteFile . '.backup.' . date('YmdHis');
        $ssh->exec("cp $remoteFile $backupFile 2>/dev/null || true");
        echo "<p>✅ Yedek alındı: $backupFile</p>";
        
        // 2. Yeni dosyayı gönder
        $tempRemote = '/tmp/' . basename($localFile);
        $ssh->sendFile($localFile, $tempRemote);
        echo "<p>✅ Dosya /tmp/'ye gönderildi</p>";
        
        // 3. Dosyayı doğru yere taşı
        $ssh->exec("cp $tempRemote $remoteFile");
        $ssh->exec("chown asterisk:asterisk $remoteFile");
        $ssh->exec("chmod 644 $remoteFile");
        echo "<p>✅ Dosya $remoteFile konumuna taşındı</p>";
        
        // 4. Asterisk dialplan reload
        $output = $ssh->exec("asterisk -rx 'dialplan reload'");
        echo "<p>✅ Asterisk dialplan reload edildi</p>";
        echo "<pre>$output</pre>";
        
        // 5. Syntax check
        $syntaxCheck = $ssh->exec("asterisk -rx 'dialplan show auto-calls-template-2'");
        echo "<p>✅ Syntax check:</p>";
        echo "<pre>" . htmlspecialchars(substr($syntaxCheck, 0, 1000)) . "</pre>";
        
        echo "<div class='success'>";
        echo "<h3>🎉 İşlem Başarılı!</h3>";
        echo "<p><strong>Artık CDR'da:</strong></p>";
        echo "<ul>";
        echo "<li><code>dst</code> = 05307711717 (telefon numarası) ✅</li>";
        echo "<li><code>userfield</code> = Call ID ✅</li>";
        echo "</ul>";
        echo "<p>Yeni aramalar bu ayarları kullanacak!</p>";
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<div class='error'>";
        echo "<h3>❌ Hata Oluştu</h3>";
        echo "<p>" . $e->getMessage() . "</p>";
        
        // Rollback önerisi
        if (isset($backupFile)) {
            echo "<p><strong>Rollback komutu:</strong></p>";
            echo "<pre>cp $backupFile $remoteFile && asterisk -rx 'dialplan reload'</pre>";
        }
        
        echo "</div>";
    }
    
    echo "</div>";
}

echo "<div style='margin-top: 30px; text-align: center;'>";
echo "<a href='test_insert_call.php' class='btn'>📞 Test Arama</a> ";
echo "<a href='check_cdr_structure.php' class='btn'>📊 CDR Kontrol</a> ";
echo "<a href='otomatikarama.php' class='btn' style='background: #6c757d;'>🏠 Ana Sayfa</a>";
echo "</div>";

echo "</div></body></html>";
