<?php
/**
 * Tüm Template'leri Yeniden Oluştur ve Asterisk'e Gönder
 */

require_once 'config.php';
require_once 'includes/TemplateManager.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>";
echo "<html lang='tr'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<title>Template'leri Yeniden Oluştur</title>";
echo "<style>
    body { font-family: 'Segoe UI', Tahoma, sans-serif; padding: 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
    .container { max-width: 900px; margin: 0 auto; background: white; padding: 40px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
    h1 { color: #667eea; border-bottom: 3px solid #667eea; padding-bottom: 15px; margin-bottom: 30px; }
    .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #28a745; }
    .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #dc3545; }
    .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #17a2b8; }
    .warning { background: #fff3cd; color: #856404; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #ffc107; }
    pre { background: #f4f4f4; padding: 15px; border-radius: 5px; overflow-x: auto; font-size: 13px; border: 1px solid #ddd; }
    .template-box { background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; border: 2px solid #667eea; }
    .btn { display: inline-block; padding: 12px 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 8px; margin-top: 20px; font-weight: 600; }
    .btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4); }
</style>";
echo "</head>";
echo "<body>";
echo "<div class='container'>";

echo "<h1>🔄 Tüm Template'leri Yeniden Oluştur</h1>";

try {
    $templateManager = new TemplateManager($pdo);
    
    // Tüm template'leri al (aktif olmayanlar dahil)
    $templates = $templateManager->getTemplates(false);
    
    echo "<div class='info'>";
    echo "<strong>📊 Toplam Template Sayısı:</strong> " . count($templates);
    echo "</div>";
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($templates as $template) {
        echo "<div class='template-box'>";
        echo "<h3>📝 Template #{$template['id']}: {$template['TemplateName']}</h3>";
        echo "<p><strong>Context:</strong> {$template['DialplanContext']}</p>";
        echo "<p><strong>Açıklama:</strong> " . htmlspecialchars($template['Description']) . "</p>";
        
        try {
            // Dialplan yeniden oluştur
            $dialplan = $templateManager->generateDialplanForTemplate($template['id']);
            
            echo "<div class='success'>";
            echo "✅ <strong>Başarılı!</strong> Dialplan yeniden oluşturuldu ve Asterisk'e gönderildi.";
            echo "</div>";
            
            // Dialplan önizlemesi (ilk 15 satır)
            $lines = explode("\n", $dialplan);
            $preview = array_slice($lines, 0, 20);
            echo "<details>";
            echo "<summary style='cursor: pointer; color: #667eea; font-weight: 600;'>📄 Dialplan Önizleme (İlk 20 satır)</summary>";
            echo "<pre>" . htmlspecialchars(implode("\n", $preview)) . "\n...</pre>";
            echo "</details>";
            
            $successCount++;
            
        } catch (Exception $e) {
            echo "<div class='error'>";
            echo "❌ <strong>Hata:</strong> " . htmlspecialchars($e->getMessage());
            echo "</div>";
            $errorCount++;
        }
        
        echo "</div>";
    }
    
    // Özet
    echo "<div class='info' style='margin-top: 30px; padding: 25px;'>";
    echo "<h3>📊 İşlem Özeti</h3>";
    echo "<p><strong style='color: #28a745;'>✅ Başarılı:</strong> {$successCount}</p>";
    echo "<p><strong style='color: #dc3545;'>❌ Hatalı:</strong> {$errorCount}</p>";
    echo "<p><strong>📂 Toplam:</strong> " . count($templates) . "</p>";
    echo "</div>";
    
    if ($successCount > 0) {
        echo "<div class='success' style='margin-top: 20px;'>";
        echo "<h3>🎉 Tamamlandı!</h3>";
        echo "<p>Template'ler başarıyla yeniden oluşturuldu ve Asterisk sunucusuna gönderildi.</p>";
        echo "<p><strong>Önemli:</strong> Artık tüm template'ler için CDR kayıtlarında:</p>";
        echo "<ul>";
        echo "<li><code>dst</code> = Telefon numarası (örn: 05307711717)</li>";
        echo "<li><code>userfield</code> = Call ID</li>";
        echo "</ul>";
        echo "</div>";
        
        echo "<div class='warning'>";
        echo "<strong>⚠️ Test Önerisi:</strong> Yeni bir arama yaparak CDR kayıtlarını kontrol edin.";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<strong>❌ Fatal Hata:</strong> " . htmlspecialchars($e->getMessage());
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}

echo "<div style='text-align: center; margin-top: 30px;'>";
echo "<a href='sablonlar.php' class='btn'>📝 Şablonlar Sayfası</a> ";
echo "<a href='check_cdr.php' class='btn'>📊 CDR Kontrol</a> ";
echo "<a href='test_insert_call.php' class='btn'>📞 Test Arama</a>";
echo "</div>";

echo "</div>";
echo "</body>";
echo "</html>";
