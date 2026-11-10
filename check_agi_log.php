<?php
/**
 * AGI Log Dosyasını Kontrol Et
 */

require_once 'config.php';
require_once 'includes/SshHelper.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>";
echo "<html lang='tr'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<title>AGI Log Kontrol</title>";
echo "<style>
    body { font-family: 'Segoe UI', Tahoma, sans-serif; padding: 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
    .container { max-width: 1200px; margin: 0 auto; background: white; padding: 40px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
    h1 { color: #667eea; border-bottom: 3px solid #667eea; padding-bottom: 15px; margin-bottom: 30px; }
    .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #28a745; }
    .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #dc3545; }
    .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #17a2b8; }
    .warning { background: #fff3cd; color: #856404; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #ffc107; }
    pre { background: #f4f4f4; padding: 15px; border-radius: 5px; overflow-x: auto; font-size: 12px; border: 1px solid #ddd; max-height: 600px; }
    .btn { display: inline-block; padding: 12px 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 8px; margin: 10px 5px; font-weight: 600; }
    .highlight { background: yellow; font-weight: bold; }
</style>";
echo "</head>";
echo "<body>";
echo "<div class='container'>";

echo "<h1>🔍 AGI Log Kontrol</h1>";

try {
    $sshHelper = new SshHelper(
        ASTERISK_SSH_HOST,
        ASTERISK_SSH_PORT,
        ASTERISK_SSH_USER,
        ASTERISK_SSH_PASS
    );
    
    $logFile = '/tmp/agi_call_end.log';
    
    echo "<div class='info'>";
    echo "<strong>📁 Log Dosyası:</strong> $logFile";
    echo "</div>";
    
    // Dosya var mı kontrol et
    $checkResult = $sshHelper->exec("test -f $logFile && echo 'EXISTS' || echo 'NOT_FOUND'");
    
    if (trim($checkResult) === 'NOT_FOUND') {
        echo "<div class='warning'>";
        echo "<strong>⚠️ Log dosyası bulunamadı!</strong><br>";
        echo "Bu, AGI script'inin hiç çalışmadığı anlamına gelebilir.";
        echo "</div>";
    } else {
        // Son 100 satırı oku
        $logContent = $sshHelper->exec("tail -100 $logFile");
        
        // CDR UPDATE satırlarını kontrol et
        $hasCdrUpdate = strpos($logContent, 'CDR UPDATE') !== false;
        $hasCdrSuccess = strpos($logContent, 'CDR UPDATE - dst güncellendi') !== false;
        $hasCdrError = strpos($logContent, 'CDR UPDATE HATA') !== false;
        
        echo "<div class='info'>";
        echo "<h3>📊 Log Analizi:</h3>";
        echo "<ul>";
        echo "<li><strong>CDR UPDATE komutu:</strong> " . ($hasCdrUpdate ? "✅ VAR" : "❌ YOK") . "</li>";
        echo "<li><strong>CDR UPDATE başarılı:</strong> " . ($hasCdrSuccess ? "<span class='highlight'>✅ VAR</span>" : "❌ YOK") . "</li>";
        echo "<li><strong>CDR UPDATE hatası:</strong> " . ($hasCdrError ? "⚠️ VAR" : "✅ YOK") . "</li>";
        echo "</ul>";
        echo "</div>";
        
        if ($hasCdrError) {
            echo "<div class='error'>";
            echo "<strong>❌ CDR Güncellemesinde Hata Tespit Edildi!</strong><br>";
            echo "Aşağıdaki log'u kontrol edin.";
            echo "</div>";
        } elseif (!$hasCdrUpdate) {
            echo "<div class='warning'>";
            echo "<strong>⚠️ CDR UPDATE komutu görünmüyor!</strong><br>";
            echo "AGI script'i çalışmış olabilir ama CDR güncelleme kısmına ulaşmamış olabilir.";
            echo "</div>";
        } elseif ($hasCdrSuccess) {
            echo "<div class='success'>";
            echo "<strong>✅ CDR Güncellemesi Başarılı!</strong><br>";
            echo "Log'da CDR update kaydı var. Eğer CDR'da hala 's' görünüyorsa, farklı bir call'ın CDR kaydına bakıyor olabilirsiniz.";
            echo "</div>";
        }
        
        // Log içeriğini göster
        echo "<h3>📄 Log İçeriği (Son 100 satır):</h3>";
        echo "<pre>";
        $lines = explode("\n", $logContent);
        foreach ($lines as $line) {
            $displayLine = htmlspecialchars($line);
            
            // CDR satırlarını vurgula
            if (stripos($line, 'CDR UPDATE') !== false || stripos($line, 'CDR') !== false) {
                $displayLine = "<span class='highlight'>" . $displayLine . "</span>";
            }
            
            // Hata satırlarını vurgula
            if (stripos($line, 'HATA') !== false || stripos($line, 'ERROR') !== false) {
                $displayLine = "<span style='color: red; font-weight: bold;'>" . $displayLine . "</span>";
            }
            
            echo $displayLine . "\n";
        }
        echo "</pre>";
    }
    
    // Son test aramasının CDR kaydını göster
    echo "<div class='info' style='margin-top: 30px;'>";
    echo "<h3>📊 Son Test Aramasının CDR Kaydı:</h3>";
    echo "<p>Son aranan telefon numarasını girin:</p>";
    echo "<form method='post'>";
    echo "<input type='text' name='phone' placeholder='05307711717' style='padding: 10px; width: 200px; border: 2px solid #667eea; border-radius: 5px;'> ";
    echo "<button type='submit' class='btn'>🔍 CDR Kontrol Et</button>";
    echo "</form>";
    echo "</div>";
    
    if (isset($_POST['phone'])) {
        $phone = $_POST['phone'];
        
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=asteriskcdrdb;charset=utf8",
                DB_USER,
                DB_PASS,
                array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
            );
            
            // Son CDR kaydını bul
            $stmt = $pdo->prepare("
                SELECT * FROM cdr 
                WHERE userfield != '' 
                ORDER BY calldate DESC 
                LIMIT 5
            ");
            $stmt->execute();
            $cdrs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if ($cdrs) {
                echo "<div class='success'>";
                echo "<h4>📞 Son 5 CDR Kaydı (userfield ile):</h4>";
                echo "<table style='width: 100%; border-collapse: collapse; font-size: 12px;'>";
                echo "<tr style='background: #667eea; color: white;'>";
                echo "<th style='border: 1px solid #ddd; padding: 8px;'>calldate</th>";
                echo "<th style='border: 1px solid #ddd; padding: 8px;'>userfield</th>";
                echo "<th style='border: 1px solid #ddd; padding: 8px;'>dst</th>";
                echo "<th style='border: 1px solid #ddd; padding: 8px;'>src</th>";
                echo "<th style='border: 1px solid #ddd; padding: 8px;'>disposition</th>";
                echo "<th style='border: 1px solid #ddd; padding: 8px;'>duration</th>";
                echo "</tr>";
                
                foreach ($cdrs as $cdr) {
                    $isDstCorrect = ($cdr['dst'] != 's' && $cdr['dst'] != 'hangup' && $cdr['dst'] != 'play-message');
                    $rowStyle = $isDstCorrect ? "background: #d4edda;" : "background: #f8d7da;";
                    
                    echo "<tr style='$rowStyle'>";
                    echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . htmlspecialchars($cdr['calldate']) . "</td>";
                    echo "<td style='border: 1px solid #ddd; padding: 8px;'><strong>" . htmlspecialchars($cdr['userfield']) . "</strong></td>";
                    echo "<td style='border: 1px solid #ddd; padding: 8px; font-weight: bold;'>" . htmlspecialchars($cdr['dst']) . "</td>";
                    echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . htmlspecialchars($cdr['src']) . "</td>";
                    echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . htmlspecialchars($cdr['disposition']) . "</td>";
                    echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . htmlspecialchars($cdr['duration']) . "s</td>";
                    echo "</tr>";
                }
                
                echo "</table>";
                echo "</div>";
            } else {
                echo "<div class='warning'>Hiç CDR kaydı bulunamadı.</div>";
            }
            
        } catch (Exception $e) {
            echo "<div class='error'>CDR sorgusu hatası: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
    
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
