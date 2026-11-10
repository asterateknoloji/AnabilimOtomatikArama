<?php
/**
 * Tüm Devam Eden Aramaları Sıfırla ve Yeniden Başlat
 */

require_once 'config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>";
echo "<html lang='tr'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<title>Aramaları Yeniden Başlat</title>";
echo "<style>
    body { font-family: 'Segoe UI', Tahoma, sans-serif; padding: 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
    .container { max-width: 800px; margin: 0 auto; background: white; padding: 40px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
    h1 { color: #667eea; border-bottom: 3px solid #667eea; padding-bottom: 15px; margin-bottom: 30px; }
    .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #28a745; }
    .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #dc3545; }
    .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #17a2b8; }
    .warning { background: #fff3cd; color: #856404; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #ffc107; }
    .btn { display: inline-block; padding: 12px 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 8px; margin: 10px 5px; font-weight: 600; border: none; cursor: pointer; font-size: 16px; }
    .btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4); }
</style>";
echo "</head>";
echo "<body>";
echo "<div class='container'>";

echo "<h1>🔄 Aramaları Yeniden Başlat</h1>";

try {
    if (isset($_POST['reset_calls'])) {
        echo "<div class='info'><strong>🔄 İşlem Başlatılıyor...</strong></div>";
        
        // Tüm kilitli (devam eden) aramaları bul
        $stmt = $pdo->query("
            SELECT COUNT(*) as locked_count 
            FROM CALLS 
            WHERE Locked = 1
        ");
        $lockedCount = $stmt->fetchColumn();
        
        echo "<div class='info'>";
        echo "<strong>🔒 Kilitli (Devam Eden) Arama:</strong> {$lockedCount}";
        echo "</div>";
        
        // Tüm kilitleri kaldır
        $pdo->exec("UPDATE CALLS SET Locked = 0 WHERE Locked = 1");
        
        echo "<div class='success'>";
        echo "✅ {$lockedCount} arama kilidi kaldırıldı.";
        echo "</div>";
        
        echo "<div class='success' style='margin-top: 30px; padding: 25px;'>";
        echo "<h3>🎉 İşlem Tamamlandı!</h3>";
        echo "<p>Artık yeni aramalar güncellenmiş call file formatı ile oluşturulacak.</p>";
        echo "<p><strong>✅ accountcode:</strong> Call ID</p>";
        echo "<p><strong>✅ dst:</strong> Telefon numarası (düzeltme scripti ile)</p>";
        echo "</div>";
        
        echo "<div class='warning'>";
        echo "<strong>📋 Sonraki Adım:</strong> <code>arama_yonetimi.php</code>'den aramaları yeniden başlatın.";
        echo "</div>";
    }
    
    // Mevcut durumu göster
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN Locked = 1 THEN 1 ELSE 0 END) as locked,
            SUM(CASE WHEN Status = 1 AND Locked = 0 THEN 1 ELSE 0 END) as pending
        FROM CALLS
    ");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<div class='info'>";
    echo "<h3>📊 Mevcut Durum</h3>";
    echo "<ul>";
    echo "<li><strong>Toplam Arama:</strong> {$stats['total']}</li>";
    echo "<li><strong>🔒 Kilitli (Devam Eden):</strong> {$stats['locked']}</li>";
    echo "<li><strong>⏳ Bekleyen:</strong> {$stats['pending']}</li>";
    echo "</ul>";
    echo "</div>";
    
    if ($stats['locked'] > 0) {
        echo "<div class='warning'>";
        echo "<strong>⚠️ {$stats['locked']} arama devam ediyor!</strong><br>";
        echo "Bu aramalar eski call file formatı ile oluşturulmuş olabilir.<br>";
        echo "Güncellenmiş formatı kullanmak için bu aramaları sıfırlayıp yeniden başlatmalısınız.";
        echo "</div>";
        
        echo "<form method='post'>";
        echo "<button type='submit' name='reset_calls' class='btn' style='font-size: 18px; padding: 15px 40px;'>🔄 Tüm Kilitleri Kaldır ve Sıfırla</button>";
        echo "</form>";
    } else {
        echo "<div class='success'>";
        echo "<strong>✅ Devam eden arama yok!</strong><br>";
        echo "Yeni aramalar otomatik olarak güncellenmiş formatı kullanacak.";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<strong>❌ Hata:</strong> " . htmlspecialchars($e->getMessage());
    echo "</div>";
}

echo "<div style='text-align: center; margin-top: 30px;'>";
echo "<a href='arama_yonetimi.php' class='btn'>📞 Arama Yönetimi</a> ";
echo "<a href='fix_cdr_dst.php' class='btn'>🔧 CDR Düzelt</a>";
echo "</div>";

echo "</div>";
echo "</body>";
echo "</html>";
