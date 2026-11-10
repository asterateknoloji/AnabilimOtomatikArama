<?php
/**
 * Asterisk Sunucusundaki Template Dialplan Dosyasını Kontrol Et
 */

require_once 'config.php';
require_once 'includes/SshHelper.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>";
echo "<html lang='tr'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<title>Asterisk Dialplan Kontrol</title>";
echo "<style>
    body { font-family: 'Segoe UI', Tahoma, sans-serif; padding: 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
    .container { max-width: 1200px; margin: 0 auto; background: white; padding: 40px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
    h1 { color: #667eea; border-bottom: 3px solid #667eea; padding-bottom: 15px; margin-bottom: 30px; }
    .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #28a745; }
    .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #dc3545; }
    .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #17a2b8; }
    .warning { background: #fff3cd; color: #856404; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #ffc107; }
    pre { background: #f4f4f4; padding: 15px; border-radius: 5px; overflow-x: auto; font-size: 12px; border: 1px solid #ddd; max-height: 600px; }
    .btn { display: inline-block; padding: 12px 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 8px; margin: 10px 5px; font-weight: 600; border: none; cursor: pointer; }
    .btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4); }
    .highlight { background: yellow; font-weight: bold; }
</style>";
echo "</head>";
echo "<body>";
echo "<div class='container'>";

echo "<h1>🔍 Asterisk Dialplan Kontrol: Template-2</h1>";

try {
    $sshHelper = new SshHelper(
        ASTERISK_SSH_HOST,
        ASTERISK_SSH_PORT,
        ASTERISK_SSH_USER,
        ASTERISK_SSH_PASS
    );
    
    $remoteFile = '/etc/asterisk/extensions_template_2.conf';
    
    echo "<div class='info'>";
    echo "<strong>📁 Kontrol Edilen Dosya:</strong> $remoteFile";
    echo "</div>";
    
    // Dosya var mı kontrol et
    $checkResult = $sshHelper->exec("test -f $remoteFile && echo 'EXISTS' || echo 'NOT_FOUND'");
    
    if (trim($checkResult) === 'NOT_FOUND') {
        echo "<div class='error'>";
        echo "<strong>❌ DOSYA BULUNAMADI!</strong><br>";
        echo "Template-2 dialplan dosyası Asterisk sunucusunda mevcut değil.<br>";
        echo "Lütfen önce template'leri oluşturun.";
        echo "</div>";
        
        echo "<form method='post' style='margin-top: 20px;'>";
        echo "<button type='submit' name='create_template' class='btn'>🔨 Template'leri Şimdi Oluştur</button>";
        echo "</form>";
        
        if (isset($_POST['create_template'])) {
            header('Location: regenerate_all_templates.php');
            exit;
        }
        
    } else {
        echo "<div class='success'>";
        echo "✅ Dosya mevcut";
        echo "</div>";
        
        // Dosya içeriğini oku
        $content = $sshHelper->exec("cat $remoteFile");
        
        // CDR ayarlarını kontrol et
        $hasCdrDst = strpos($content, 'CDR(dst)') !== false;
        $hasCdrUserfield = strpos($content, 'CDR(userfield)') !== false;
        $hasMarkAnswered = strpos($content, 'mark-answered') !== false;
        $hasPlayMessage = strpos($content, 'play-message') !== false;
        
        echo "<div class='info'>";
        echo "<h3>🔍 Dialplan Analizi:</h3>";
        echo "<ul>";
        echo "<li><strong>CDR(dst) ayarı:</strong> " . ($hasCdrDst ? "<span class='highlight'>✅ VAR</span>" : "❌ YOK") . "</li>";
        echo "<li><strong>CDR(userfield) ayarı:</strong> " . ($hasCdrUserfield ? "<span class='highlight'>✅ VAR</span>" : "❌ YOK") . "</li>";
        echo "<li><strong>'mark-answered' extension:</strong> " . ($hasMarkAnswered ? "⚠️ ESKİ YAPI VAR" : "✅ YOK (doğru)") . "</li>";
        echo "<li><strong>'play-message' extension:</strong> " . ($hasPlayMessage ? "✅ VAR" : "❌ YOK") . "</li>";
        echo "</ul>";
        echo "</div>";
        
        if (!$hasCdrDst || !$hasCdrUserfield) {
            echo "<div class='error'>";
            echo "<strong>❌ SORUN TESPİT EDİLDİ!</strong><br>";
            echo "Dialplan dosyasında CDR ayarları eksik. Template'leri yeniden oluşturmanız gerekiyor.";
            echo "</div>";
            
            echo "<form method='post' style='margin-top: 20px;'>";
            echo "<button type='submit' name='regenerate' class='btn'>🔄 Template'leri Yeniden Oluştur</button>";
            echo "</form>";
            
            if (isset($_POST['regenerate'])) {
                header('Location: regenerate_all_templates.php');
                exit;
            }
        } else {
            echo "<div class='success'>";
            echo "<strong>✅ DIALPLAN DOĞRU!</strong><br>";
            echo "CDR ayarları mevcut. Yeni aramalar doğru şekilde kaydedilecek.";
            echo "</div>";
        }
        
        // İlk 50 satırı göster
        $lines = explode("\n", $content);
        $preview = array_slice($lines, 0, 50);
        
        echo "<h3>📄 Dialplan İçeriği (İlk 50 satır):</h3>";
        echo "<pre>";
        foreach ($preview as $lineNum => $line) {
            $lineNum = $lineNum + 1;
            $displayLine = htmlspecialchars($line);
            
            // CDR satırlarını vurgula
            if (strpos($line, 'CDR(dst)') !== false || strpos($line, 'CDR(userfield)') !== false) {
                $displayLine = "<span class='highlight'>" . $displayLine . "</span>";
            }
            
            echo sprintf("%3d | %s\n", $lineNum, $displayLine);
        }
        echo "\n... (toplam " . count($lines) . " satır)</pre>";
        
        // Son değişiklik zamanı
        $modTime = $sshHelper->exec("stat -c '%y' $remoteFile 2>/dev/null || stat -f '%Sm' $remoteFile");
        echo "<div class='info'>";
        echo "<strong>🕐 Son Değişiklik:</strong> " . htmlspecialchars(trim($modTime));
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<strong>❌ Hata:</strong> " . htmlspecialchars($e->getMessage());
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}

echo "<div style='text-align: center; margin-top: 30px;'>";
echo "<a href='regenerate_all_templates.php' class='btn'>🔄 Template'leri Yeniden Oluştur</a> ";
echo "<a href='check_cdr.php' class='btn'>📊 CDR Kontrol</a>";
echo "</div>";

echo "</div>";
echo "</body>";
echo "</html>";
