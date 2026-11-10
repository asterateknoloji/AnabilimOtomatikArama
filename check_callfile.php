<?php
/**
 * Son Oluşturulan Call File'ı Kontrol Et
 */

require_once 'config.php';
require_once 'includes/SshHelper.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>";
echo "<html lang='tr'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<title>Call File Kontrol</title>";
echo "<style>
    body { font-family: 'Segoe UI', Tahoma, sans-serif; padding: 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
    .container { max-width: 900px; margin: 0 auto; background: white; padding: 40px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
    h1 { color: #667eea; border-bottom: 3px solid #667eea; padding-bottom: 15px; margin-bottom: 30px; }
    .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #28a745; }
    .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #dc3545; }
    .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #17a2b8; }
    .warning { background: #fff3cd; color: #856404; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #ffc107; }
    pre { background: #f4f4f4; padding: 15px; border-radius: 5px; overflow-x: auto; font-size: 13px; border: 1px solid #ddd; }
    .highlight { background: yellow; font-weight: bold; }
</style>";
echo "</head>";
echo "<body>";
echo "<div class='container'>";

echo "<h1>🔍 Call File Kontrol</h1>";

try {
    $sshHelper = new SshHelper(
        ASTERISK_SSH_HOST,
        ASTERISK_SSH_PORT,
        ASTERISK_SSH_USER,
        ASTERISK_SSH_PASS
    );
    
    // Local call file klasörünü kontrol et
    $localPath = __DIR__ . '/asterisk/callfiles/';
    
    echo "<div class='info'>";
    echo "<h3>📁 Local Call File Klasörü:</h3>";
    echo "<code>$localPath</code>";
    echo "</div>";
    
    if (is_dir($localPath)) {
        $files = glob($localPath . '*.call');
        
        if ($files) {
            echo "<div class='warning'>";
            echo "<strong>⚠️ Local klasörde " . count($files) . " call file var!</strong><br>";
            echo "Bu dosyalar temizlenmeliş.";
            echo "</div>";
            
            // Son dosyayı göster
            $lastFile = end($files);
            $content = file_get_contents($lastFile);
            
            echo "<h4>📄 Son Local Call File:</h4>";
            echo "<pre>" . htmlspecialchars($content) . "</pre>";
            
            // Account satırını kontrol et
            if (strpos($content, 'Account:') !== false) {
                echo "<div class='success'>✅ <strong>Account:</strong> satırı VAR</div>";
            } else {
                echo "<div class='error'>❌ <strong>Account:</strong> satırı YOK</div>";
            }
        } else {
            echo "<div class='info'>Local klasörde call file yok (temiz).</div>";
        }
    }
    
    // Remote'da arşivlenmiş call file'ları kontrol et
    echo "<h3>📦 Asterisk Arşiv Klasörü (Son 5 dosya):</h3>";
    
    $archiveCmd = "ls -lt /var/spool/asterisk/outgoing_done/ | head -6";
    $archiveList = $sshHelper->exec($archiveCmd);
    
    echo "<pre>" . htmlspecialchars($archiveList) . "</pre>";
    
    // Son arşivlenmiş dosyayı oku
    $lastArchiveCmd = "ls -t /var/spool/asterisk/outgoing_done/*.call 2>/dev/null | head -1";
    $lastArchiveFile = trim($sshHelper->exec($lastArchiveCmd));
    
    if ($lastArchiveFile) {
        echo "<div class='info'>";
        echo "<strong>📄 Son Arşivlenmiş Call File:</strong><br>";
        echo "<code>$lastArchiveFile</code>";
        echo "</div>";
        
        $archiveContent = $sshHelper->exec("cat $lastArchiveFile");
        
        echo "<h4>📄 İçeriği:</h4>";
        echo "<pre>";
        $lines = explode("\n", $archiveContent);
        foreach ($lines as $line) {
            $displayLine = htmlspecialchars($line);
            
            // Account satırını vurgula
            if (stripos($line, 'Account:') !== false || stripos($line, 'account:') !== false) {
                $displayLine = "<span class='highlight'>" . $displayLine . "</span>";
            }
            
            echo $displayLine . "\n";
        }
        echo "</pre>";
        
        // Account satırını kontrol et
        if (stripos($archiveContent, 'Account:') !== false) {
            echo "<div class='success'><strong>✅ Account:</strong> satırı VAR!</div>";
            
            // Account değerini parse et
            preg_match('/Account:\s*(\d+)/i', $archiveContent, $matches);
            if (isset($matches[1])) {
                $accountCode = $matches[1];
                echo "<div class='info'>";
                echo "<strong>🔢 Account Code (Call ID):</strong> {$accountCode}";
                echo "</div>";
                
                // Bu Call ID'nin CDR kaydını kontrol et
                try {
                    $pdoCdr = new PDO(
                        "mysql:host=" . DB_HOST . ";dbname=asteriskcdrdb;charset=utf8",
                        DB_USER,
                        DB_PASS
                    );
                    
                    $stmt = $pdoCdr->prepare("SELECT * FROM cdr WHERE accountcode = ? ORDER BY calldate DESC LIMIT 1");
                    $stmt->execute([$accountCode]);
                    $cdr = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($cdr) {
                        echo "<div class='success'>";
                        echo "<h4>✅ CDR Kaydı Bulundu:</h4>";
                        echo "<ul>";
                        echo "<li><strong>accountcode:</strong> {$cdr['accountcode']}</li>";
                        echo "<li><strong>dst:</strong> {$cdr['dst']}</li>";
                        echo "<li><strong>src:</strong> {$cdr['src']}</li>";
                        echo "<li><strong>disposition:</strong> {$cdr['disposition']}</li>";
                        echo "</ul>";
                        echo "</div>";
                    } else {
                        echo "<div class='warning'>CDR kaydı bulunamadı (henüz yazılmamış olabilir).</div>";
                    }
                } catch (Exception $e) {
                    echo "<div class='error'>CDR sorgusu hatası: " . htmlspecialchars($e->getMessage()) . "</div>";
                }
            }
        } else {
            echo "<div class='error'><strong>❌ Account:</strong> satırı YOK!</div>";
            echo "<div class='warning'>";
            echo "<strong>Sorun:</strong> AsteriskCallFile.php güncellenmesi çalışmıyor.<br>";
            echo "Dosya içeriğini manuel kontrol edin.";
            echo "</div>";
        }
    } else {
        echo "<div class='warning'>Arşivlenmiş call file bulunamadı.</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<strong>❌ Hata:</strong> " . htmlspecialchars($e->getMessage());
    echo "</div>";
}

echo "</div>";
echo "</body>";
echo "</html>";
