<?php
/**
 * CDR dst Alanını Düzelt
 * userfield'den Call ID alıp, CALLS tablosundan telefon numarasını çekip dst'ye yaz
 */

require_once 'config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>";
echo "<html lang='tr'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<title>CDR dst Düzelt</title>";
echo "<style>
    body { font-family: 'Segoe UI', Tahoma, sans-serif; padding: 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
    .container { max-width: 900px; margin: 0 auto; background: white; padding: 40px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
    h1 { color: #667eea; border-bottom: 3px solid #667eea; padding-bottom: 15px; margin-bottom: 30px; }
    .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #28a745; }
    .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #dc3545; }
    .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #17a2b8; }
    .warning { background: #fff3cd; color: #856404; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #ffc107; }
    table { width: 100%; border-collapse: collapse; margin: 20px 0; font-size: 13px; }
    th { background: #667eea; color: white; padding: 12px; text-align: left; }
    td { border: 1px solid #ddd; padding: 10px; }
    tr:nth-child(even) { background: #f8f9fa; }
    .btn { display: inline-block; padding: 12px 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 8px; margin: 10px 5px; font-weight: 600; border: none; cursor: pointer; }
    .btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4); }
</style>";
echo "</head>";
echo "<body>";
echo "<div class='container'>";

echo "<h1>🔧 CDR dst Alanını Düzelt</h1>";

try {
    // ANABILIMNEW ve asteriskcdrdb PDO bağlantıları
    $pdoMain = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8",
        DB_USER,
        DB_PASS,
        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
    );
    
    $pdoCdr = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=asteriskcdrdb;charset=utf8",
        DB_USER,
        DB_PASS,
        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
    );
    
    // İşlem yapılacak mı?
    if (isset($_POST['fix_cdr'])) {
        echo "<div class='info'><strong>🔄 İşlem Başlatılıyor...</strong></div>";
        
        // userfield veya accountcode boş olmayan ve dst = 's' veya 'hangup' veya 'play-message' olan CDR kayıtları
        $stmt = $pdoCdr->query("
            SELECT calldate, userfield, accountcode, dst, src, uniqueid 
            FROM cdr 
            WHERE (userfield != '' OR accountcode != '')
            AND (userfield IS NOT NULL OR accountcode IS NOT NULL)
            AND (dst = 's' OR dst = 'hangup' OR dst = 'play-message' OR dst LIKE '%mark%')
            ORDER BY calldate DESC 
            LIMIT 100
        ");
        
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<div class='info'>";
        echo "<strong>📊 Düzeltilecek Kayıt Sayısı:</strong> " . count($records);
        echo "</div>";
        
        $updatedCount = 0;
        $errorCount = 0;
        
        foreach ($records as $record) {
            // userfield veya accountcode'dan Call ID al
            $callId = !empty($record['userfield']) ? $record['userfield'] : $record['accountcode'];
            
            // CALLS tablosundan telefon numarasını al
            $callStmt = $pdoMain->prepare("SELECT PhoneNumber FROM CALLS WHERE id = ?");
            $callStmt->execute([$callId]);
            $callData = $callStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($callData && !empty($callData['PhoneNumber'])) {
                $phoneNumber = $callData['PhoneNumber'];
                
                // CDR'da dst'yi güncelle
                $updateStmt = $pdoCdr->prepare("
                    UPDATE cdr 
                    SET dst = ? 
                    WHERE uniqueid = ? 
                    LIMIT 1
                ");
                
                if ($updateStmt->execute([$phoneNumber, $record['uniqueid']])) {
                    $updatedCount++;
                    echo "<div class='success' style='padding: 8px; margin: 5px 0; font-size: 12px;'>";
                    echo "✅ Call ID: {$callId} → dst güncellendi: <strong>{$phoneNumber}</strong> (uniqueid: {$record['uniqueid']})";
                    echo "</div>";
                } else {
                    $errorCount++;
                    echo "<div class='error' style='padding: 8px; margin: 5px 0; font-size: 12px;'>";
                    echo "❌ Call ID: {$callId} → Güncelleme BAŞARISIZ";
                    echo "</div>";
                }
            } else {
                $errorCount++;
                echo "<div class='warning' style='padding: 8px; margin: 5px 0; font-size: 12px;'>";
                echo "⚠️ Call ID: {$callId} → CALLS tablosunda bulunamadı veya telefon numarası boş";
                echo "</div>";
            }
        }
        
        echo "<div class='success' style='margin-top: 30px; padding: 25px;'>";
        echo "<h3>🎉 İşlem Tamamlandı!</h3>";
        echo "<p><strong style='color: #28a745;'>✅ Güncellenen:</strong> {$updatedCount}</p>";
        echo "<p><strong style='color: #dc3545;'>❌ Hatalı:</strong> {$errorCount}</p>";
        echo "<p><strong>📂 Toplam:</strong> " . count($records) . "</p>";
        echo "</div>";
    }
    
    // Mevcut durumu göster
    echo "<div class='info'>";
    echo "<h3>📊 CDR Durumu</h3>";
    echo "</div>";
    
    // Önce tüm CDR kayıtlarını kontrol et (userfield şartı olmadan)
    $stmtAll = $pdoCdr->query("
        SELECT COUNT(*) as total,
               SUM(CASE WHEN userfield != '' AND userfield IS NOT NULL THEN 1 ELSE 0 END) as with_userfield,
               SUM(CASE WHEN accountcode != '' AND accountcode IS NOT NULL THEN 1 ELSE 0 END) as with_accountcode,
               SUM(CASE WHEN (userfield = '' OR userfield IS NULL) AND (accountcode = '' OR accountcode IS NULL) THEN 1 ELSE 0 END) as without_callid
        FROM cdr 
        WHERE calldate >= DATE_SUB(NOW(), INTERVAL 1 DAY)
    ");
    $stats = $stmtAll->fetch(PDO::FETCH_ASSOC);
    
    echo "<div class='info'>";
    echo "<h4>📈 Son 24 Saat CDR İstatistikleri:</h4>";
    echo "<ul>";
    echo "<li><strong>Toplam CDR:</strong> {$stats['total']}</li>";
    echo "<li><strong>userfield DOLU:</strong> {$stats['with_userfield']}</li>";
    echo "<li><strong>accountcode DOLU:</strong> {$stats['with_accountcode']}</li>";
    echo "<li><strong>Her ikisi de BOŞ:</strong> {$stats['without_callid']}</li>";
    echo "</ul>";
    echo "</div>";
    
    // userfield veya accountcode ile olanlar
    echo "<h4>🔍 Son 10 CDR (Call ID olanlar):</h4>";
    $stmt = $pdoCdr->query("
        SELECT calldate, userfield, accountcode, dst, src, disposition, duration, uniqueid 
        FROM cdr 
        WHERE (userfield != '' OR accountcode != '')
        AND (userfield IS NOT NULL OR accountcode IS NOT NULL)
        ORDER BY calldate DESC 
        LIMIT 10
    ");
    
    $currentRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($currentRecords) {
        $needsFixCount = 0;
        
        echo "<table>";
        echo "<thead>";
        echo "<tr>";
        echo "<th>Tarih/Saat</th>";
        echo "<th>userfield</th>";
        echo "<th>accountcode</th>";
        echo "<th>dst</th>";
        echo "<th>src</th>";
        echo "<th>Durum</th>";
        echo "<th>Süre</th>";
        echo "</tr>";
        echo "</thead>";
        echo "<tbody>";
        
        foreach ($currentRecords as $rec) {
            $callId = !empty($rec['userfield']) ? $rec['userfield'] : $rec['accountcode'];
            $needsFix = ($rec['dst'] == 's' || $rec['dst'] == 'hangup' || $rec['dst'] == 'play-message' || strpos($rec['dst'], 'mark') !== false);
            if ($needsFix) $needsFixCount++;
            
            $rowStyle = $needsFix ? "background: #f8d7da;" : "background: #d4edda;";
            
            echo "<tr style='$rowStyle'>";
            echo "<td>" . htmlspecialchars($rec['calldate']) . "</td>";
            echo "<td>" . htmlspecialchars($rec['userfield'] ?: '-') . "</td>";
            echo "<td><strong>" . htmlspecialchars($rec['accountcode'] ?: '-') . "</strong></td>";
            echo "<td><strong>" . htmlspecialchars($rec['dst']) . "</strong>" . ($needsFix ? " ❌" : " ✅") . "</td>";
            echo "<td>" . htmlspecialchars($rec['src']) . "</td>";
            echo "<td>" . htmlspecialchars($rec['disposition']) . "</td>";
            echo "<td>" . htmlspecialchars($rec['duration']) . "s</td>";
            echo "</tr>";
        }
        
        echo "</tbody>";
        echo "</table>";
        
        if ($needsFixCount > 0) {
            echo "<div class='warning'>";
            echo "<strong>⚠️ {$needsFixCount} kayıt düzeltilmesi gerekiyor!</strong>";
            echo "</div>";
        } else {
            echo "<div class='success'>";
            echo "<strong>✅ Görüntülenen kayıtlar doğru görünüyor!</strong>";
            echo "</div>";
        }
        
        // Butonu her zaman göster
        echo "<div style='text-align: center; margin: 30px 0;'>";
        echo "<form method='post'>";
        echo "<button type='submit' name='fix_cdr' class='btn' style='font-size: 18px; padding: 15px 40px;'>🔧 Tüm Hatalı CDR Kayıtlarını Düzelt (Son 100)</button>";
        echo "</form>";
        echo "<p style='color: #666; font-size: 13px; margin-top: 10px;'>dst = 's', 'hangup', 'play-message' olan kayıtları düzeltir</p>";
        echo "</div>";
    } else {
        echo "<div class='warning'>userfield DOLU hiç CDR kaydı bulunamadı.</div>";
    }
    
    // userfield BOŞ olanları da göster
    echo "<h4>🔍 Son 10 CDR (TÜM kayıtlar):</h4>";
    $stmtAll = $pdoCdr->query("
        SELECT calldate, userfield, accountcode, dst, src, dcontext, disposition, duration, uniqueid 
        FROM cdr 
        ORDER BY calldate DESC 
        LIMIT 10
    ");
    
    $allRecords = $stmtAll->fetchAll(PDO::FETCH_ASSOC);
    
    if ($allRecords) {
        echo "<table>";
        echo "<thead>";
        echo "<tr>";
        echo "<th>Tarih/Saat</th>";
        echo "<th>userfield</th>";
        echo "<th>accountcode</th>";
        echo "<th>dst</th>";
        echo "<th>src</th>";
        echo "<th>Context</th>";
        echo "<th>Durum</th>";
        echo "<th>Süre</th>";
        echo "</tr>";
        echo "</thead>";
        echo "<tbody>";
        
        foreach ($allRecords as $rec) {
            $hasCallId = !empty($rec['userfield']) || !empty($rec['accountcode']);
            $rowStyle = $hasCallId ? "background: #d4edda;" : "background: #fff3cd;";
            
            echo "<tr style='$rowStyle'>";
            echo "<td>" . htmlspecialchars($rec['calldate']) . "</td>";
            echo "<td>" . htmlspecialchars($rec['userfield'] ?: '(BOŞ)') . "</td>";
            echo "<td><strong>" . htmlspecialchars($rec['accountcode'] ?: '(BOŞ)') . "</strong></td>";
            echo "<td>" . htmlspecialchars($rec['dst']) . "</td>";
            echo "<td>" . htmlspecialchars($rec['src']) . "</td>";
            echo "<td>" . htmlspecialchars($rec['dcontext']) . "</td>";
            echo "<td>" . htmlspecialchars($rec['disposition']) . "</td>";
            echo "<td>" . htmlspecialchars($rec['duration']) . "s</td>";
            echo "</tr>";
        }
        
        echo "</tbody>";
        echo "</table>";
    } else {
        echo "<div class='warning'>Hiç CDR kaydı bulunamadı!</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<strong>❌ Hata:</strong> " . htmlspecialchars($e->getMessage());
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}

echo "<div style='text-align: center; margin-top: 30px;'>";
echo "<a href='test_insert_call.php' class='btn'>📞 Yeni Test Arama</a> ";
echo "<a href='check_cdr.php' class='btn'>📊 CDR Kontrol</a>";
echo "</div>";

echo "</div>";
echo "</body>";
echo "</html>";
