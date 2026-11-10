<?php
/**
 * Update Call End AGI Script'ini Asterisk Sunucusuna Yükle
 */

require_once 'config.php';
require_once 'includes/SshHelper.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>";
echo "<html lang='tr'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<title>AGI Script Yükle</title>";
echo "<style>
    body { font-family: 'Segoe UI', Tahoma, sans-serif; padding: 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
    .container { max-width: 800px; margin: 0 auto; background: white; padding: 40px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
    h1 { color: #667eea; border-bottom: 3px solid #667eea; padding-bottom: 15px; margin-bottom: 30px; }
    .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #28a745; }
    .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #dc3545; }
    .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #17a2b8; }
    .warning { background: #fff3cd; color: #856404; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #ffc107; }
    pre { background: #f4f4f4; padding: 15px; border-radius: 5px; overflow-x: auto; font-size: 13px; border: 1px solid #ddd; }
    .step { background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #667eea; }
    .btn { display: inline-block; padding: 12px 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 8px; margin-top: 20px; font-weight: 600; }
    .btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4); }
</style>";
echo "</head>";
echo "<body>";
echo "<div class='container'>";

echo "<h1>🚀 AGI Script Yükle: update_call_end.php</h1>";

try {
    $sshHelper = new SshHelper(
        ASTERISK_SSH_HOST,
        ASTERISK_SSH_PORT,
        ASTERISK_SSH_USER,
        ASTERISK_SSH_PASS
    );
    
    $localFile = __DIR__ . '/asterisk/agi/update_call_end.php';
    $remoteFile = '/var/lib/asterisk/agi-bin/update_call_end.php';
    $backupFile = '/var/lib/asterisk/agi-bin/update_call_end.php.backup.' . date('YmdHis');
    
    echo "<div class='info'>";
    echo "<strong>📁 Dosya Bilgileri:</strong><br>";
    echo "<strong>Local:</strong> $localFile<br>";
    echo "<strong>Remote:</strong> $remoteFile<br>";
    echo "<strong>Backup:</strong> $backupFile";
    echo "</div>";
    
    // Adım 1: Mevcut dosyayı yedekle
    echo "<div class='step'>";
    echo "<strong>🔄 Adım 1:</strong> Mevcut dosya yedekleniyor...";
    try {
        $result = $sshHelper->exec("test -f $remoteFile && cp $remoteFile $backupFile || echo 'Dosya yok'");
        echo "<div class='success'>✅ Yedek alındı: $backupFile</div>";
    } catch (Exception $e) {
        echo "<div class='warning'>⚠️ Yedek alınamadı (dosya mevcut değil olabilir): " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    echo "</div>";
    
    // Adım 2: Yeni dosyayı gönder
    echo "<div class='step'>";
    echo "<strong>📤 Adım 2:</strong> Yeni dosya gönderiliyor...";
    $sshHelper->sendFile($localFile, $remoteFile);
    echo "<div class='success'>✅ Dosya gönderildi</div>";
    echo "</div>";
    
    // Adım 3: İzinleri ayarla
    echo "<div class='step'>";
    echo "<strong>🔐 Adım 3:</strong> Dosya izinleri ayarlanıyor...";
    $sshHelper->exec("chmod +x $remoteFile");
    $sshHelper->exec("chown asterisk:asterisk $remoteFile");
    echo "<div class='success'>✅ İzinler ayarlandı (755, asterisk:asterisk)</div>";
    echo "</div>";
    
    // Adım 4: Dosyayı doğrula
    echo "<div class='step'>";
    echo "<strong>✔️ Adım 4:</strong> Dosya doğrulanıyor...";
    $checkResult = $sshHelper->exec("ls -lh $remoteFile");
    echo "<pre>" . htmlspecialchars($checkResult) . "</pre>";
    echo "<div class='success'>✅ Dosya başarıyla yüklendi</div>";
    echo "</div>";
    
    // Başarı mesajı
    echo "<div class='success' style='margin-top: 30px; padding: 25px;'>";
    echo "<h3>🎉 İşlem Tamamlandı!</h3>";
    echo "<p><strong>update_call_end.php</strong> dosyası başarıyla Asterisk sunucusuna yüklendi.</p>";
    echo "<p><strong>📋 Yeni Özellik:</strong> Görüşme bittiğinde CDR.dst alanı otomatik olarak telefon numarası ile güncellenecek.</p>";
    echo "<ul>";
    echo "<li><strong>CDR.userfield:</strong> Call ID (örn: 27127)</li>";
    echo "<li><strong>CDR.dst:</strong> Telefon numarası (örn: 05307711717) - ✨ YENİ!</li>";
    echo "<li><strong>CDR.src:</strong> Caller ID (örn: 08503338007)</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div class='warning'>";
    echo "<strong>⚠️ Test Önerisi:</strong> Yeni bir arama yaparak CDR kayıtlarını kontrol edin.";
    echo "</div>";
    
    echo "<div class='info'>";
    echo "<strong>📝 Log Dosyası:</strong> <code>/tmp/agi_call_end.log</code><br>";
    echo "CDR güncelleme detayları bu dosyada görülebilir.";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<strong>❌ Hata:</strong> " . htmlspecialchars($e->getMessage());
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}

echo "<div style='text-align: center; margin-top: 30px;'>";
echo "<a href='test_insert_call.php' class='btn'>📞 Test Arama</a> ";
echo "<a href='check_cdr.php' class='btn'>📊 CDR Kontrol</a>";
echo "</div>";

echo "</div>";
echo "</body>";
echo "</html>";
