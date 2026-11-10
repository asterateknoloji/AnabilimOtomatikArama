<?php
/**
 * Local Call File Klasörünü Temizle
 */

$callFilePath = __DIR__ . '/asterisk/callfiles/';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>";
echo "<html lang='tr'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<title>Call File Temizle</title>";
echo "<style>
    body { font-family: 'Segoe UI', Tahoma, sans-serif; padding: 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
    .container { max-width: 800px; margin: 0 auto; background: white; padding: 40px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
    h1 { color: #667eea; border-bottom: 3px solid #667eea; padding-bottom: 15px; margin-bottom: 30px; }
    .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #28a745; }
    .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #dc3545; }
    .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #17a2b8; }
    .warning { background: #fff3cd; color: #856404; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #ffc107; }
    .btn { display: inline-block; padding: 12px 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 8px; margin: 10px 5px; font-weight: 600; border: none; cursor: pointer; font-size: 16px; }
</style>";
echo "</head>";
echo "<body>";
echo "<div class='container'>";

echo "<h1>🧹 Call File Klasörünü Temizle</h1>";

if (isset($_POST['clean'])) {
    $files = glob($callFilePath . '*.call');
    $count = 0;
    
    foreach ($files as $file) {
        if (unlink($file)) {
            $count++;
        }
    }
    
    echo "<div class='success'>";
    echo "<h3>✅ Temizlik Tamamlandı!</h3>";
    echo "<p><strong>{$count} dosya silindi.</strong></p>";
    echo "</div>";
    
    echo "<div class='info'>";
    echo "<h4>📋 Sonraki Adımlar:</h4>";
    echo "<ol>";
    echo "<li>Yeni bir test araması yapın</li>";
    echo "<li><code>check_callfile.php</code> ile yeni call file'ı kontrol edin</li>";
    echo "<li>Yeni call file'da <strong>Account:</strong> satırı olmalı</li>";
    echo "</ol>";
    echo "</div>";
}

$files = glob($callFilePath . '*.call');

echo "<div class='info'>";
echo "<h3>📁 Klasör:</h3>";
echo "<code>$callFilePath</code>";
echo "</div>";

if ($files) {
    echo "<div class='warning'>";
    echo "<strong>⚠️ " . count($files) . " eski call file var!</strong><br>";
    echo "<ul>";
    foreach ($files as $file) {
        $size = filesize($file);
        $date = date('Y-m-d H:i:s', filemtime($file));
        echo "<li>" . basename($file) . " ({$size} bytes, {$date})</li>";
    }
    echo "</ul>";
    echo "</div>";
    
    echo "<form method='post'>";
    echo "<button type='submit' name='clean' class='btn' style='font-size: 18px; padding: 15px 40px;'>🧹 Tüm Call File'ları Temizle</button>";
    echo "</form>";
} else {
    echo "<div class='success'>";
    echo "<strong>✅ Klasör temiz!</strong><br>";
    echo "Hiç call file yok.";
    echo "</div>";
}

echo "<div style='text-align: center; margin-top: 30px;'>";
echo "<a href='test_insert_call.php' class='btn'>📞 Yeni Test Arama</a> ";
echo "<a href='check_callfile.php' class='btn'>🔍 Call File Kontrol</a>";
echo "</div>";

echo "</div>";
echo "</body>";
echo "</html>";
