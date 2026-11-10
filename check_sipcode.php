<?php
/**
 * SipCode Alan Kontrolü ve Düzeltme
 */

require_once 'config.php';

echo "<!DOCTYPE html>";
echo "<html lang='tr'><head><meta charset='UTF-8'>";
echo "<title>SipCode Kontrol</title>";
echo "<style>
    body { font-family: Arial; margin: 20px; background: #f5f5f5; }
    .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
    h1 { color: #333; border-bottom: 3px solid #667eea; padding-bottom: 10px; }
    .success { background: #d4edda; color: #155724; padding: 15px; margin: 10px 0; border-radius: 5px; }
    .error { background: #f8d7da; color: #721c24; padding: 15px; margin: 10px 0; border-radius: 5px; }
    .warning { background: #fff3cd; color: #856404; padding: 15px; margin: 10px 0; border-radius: 5px; }
    .info { background: #d1ecf1; color: #0c5460; padding: 15px; margin: 10px 0; border-radius: 5px; }
    .btn { display: inline-block; padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; border: none; cursor: pointer; margin: 5px; }
    table { width: 100%; border-collapse: collapse; margin: 10px 0; font-size: 14px; }
    th, td { padding: 8px; border: 1px solid #ddd; text-align: left; }
    th { background: #667eea; color: white; }
    .sip-ok { color: #28a745; font-weight: bold; }
    .sip-error { color: #dc3545; font-weight: bold; }
    .sip-null { color: #6c757d; font-style: italic; }
</style></head><body><div class='container'>";

echo "<h1>🔍 SipCode Alan Kontrolü</h1>";

// SipCode değerleri açıklaması
echo "<div class='info'>";
echo "<h3>📖 SIP Kod Referansı:</h3>";
echo "<ul>";
echo "<li><strong>200</strong> - OK (Başarılı, cevaplandı)</li>";
echo "<li><strong>404</strong> - Not Found (Cevapsız)</li>";
echo "<li><strong>480</strong> - Temporarily Unavailable (Geçici olarak ulaşılamıyor)</li>";
echo "<li><strong>486</strong> - Busy Here (Meşgul)</li>";
echo "<li><strong>487</strong> - Request Terminated (İptal edildi)</li>";
echo "<li><strong>503</strong> - Service Unavailable (Tıkanıklık/Congestion)</li>";
echo "</ul>";
echo "</div>";

// Mevcut SipCode dağılımı
echo "<h2>📊 Mevcut SipCode Dağılımı</h2>";
try {
    $stmt = $pdo->query("
        SELECT 
            SipCode,
            COUNT(*) as total,
            SUM(CASE WHEN Status = 1 THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN Status = 2 THEN 1 ELSE 0 END) as completed
        FROM CALLS
        GROUP BY SipCode
        ORDER BY SipCode
    ");
    $distribution = $stmt->fetchAll();
    
    echo "<table>";
    echo "<tr><th>SipCode</th><th>Toplam</th><th>Status=1 (Bekliyor)</th><th>Status=2 (Bitti)</th><th>Açıklama</th></tr>";
    
    $sipDescriptions = [
        200 => 'OK - Başarılı',
        404 => 'Not Found - Cevapsız',
        480 => 'Temporarily Unavailable',
        486 => 'Busy - Meşgul',
        487 => 'Terminated - İptal',
        503 => 'Service Unavailable - Tıkanıklık'
    ];
    
    foreach ($distribution as $row) {
        $sipCode = $row['SipCode'];
        $desc = isset($sipDescriptions[$sipCode]) ? $sipDescriptions[$sipCode] : 'Bilinmeyen kod';
        
        $class = '';
        if ($sipCode == 200) $class = 'sip-ok';
        elseif ($sipCode === null) {
            $class = 'sip-null';
            $desc = 'NULL (Henüz işlenmemiş)';
        }
        elseif ($sipCode > 0) $class = 'sip-error';
        
        echo "<tr>";
        echo "<td class='$class'>" . ($sipCode ?? 'NULL') . "</td>";
        echo "<td>{$row['total']}</td>";
        echo "<td>{$row['pending']}</td>";
        echo "<td>{$row['completed']}</td>";
        echo "<td>$desc</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Hata: " . $e->getMessage() . "</div>";
}

// Hatalı kayıtlar
echo "<h2>⚠️ Potansiyel Sorunlu Kayıtlar</h2>";
try {
    $stmt = $pdo->query("
        SELECT 
            id, PhoneNumber, Adi, Status, SipCode, Durum, 
            PickupTime, EndCallTime, CallCount, Locked
        FROM CALLS
        WHERE (
            (Status = 2 AND SipCode IS NULL) OR
            (Status = 2 AND Durum = 'Cevaplandı' AND SipCode != 200) OR
            (PickupTime IS NOT NULL AND SipCode != 200)
        )
        ORDER BY id DESC
        LIMIT 20
    ");
    $problems = $stmt->fetchAll();
    
    if (empty($problems)) {
        echo "<div class='success'>✅ Sorunlu kayıt bulunamadı!</div>";
    } else {
        echo "<div class='warning'>⚠️ " . count($problems) . " sorunlu kayıt bulundu:</div>";
        echo "<table>";
        echo "<tr>";
        echo "<th>ID</th><th>Telefon</th><th>Adı</th><th>Status</th>";
        echo "<th>SipCode</th><th>Durum</th><th>PickupTime</th><th>Problem</th>";
        echo "</tr>";
        
        foreach ($problems as $call) {
            $problem = '';
            if ($call['Status'] == 2 && $call['SipCode'] === null) {
                $problem = 'Status=2 ama SipCode NULL';
            } elseif ($call['Durum'] == 'Cevaplandı' && $call['SipCode'] != 200) {
                $problem = 'Cevaplandı ama SipCode ≠ 200';
            } elseif ($call['PickupTime'] !== null && $call['SipCode'] != 200) {
                $problem = 'PickupTime var ama SipCode ≠ 200';
            }
            
            echo "<tr>";
            echo "<td>{$call['id']}</td>";
            echo "<td>{$call['PhoneNumber']}</td>";
            echo "<td>{$call['Adi']}</td>";
            echo "<td>{$call['Status']}</td>";
            echo "<td class='" . ($call['SipCode'] == 200 ? 'sip-ok' : ($call['SipCode'] ? 'sip-error' : 'sip-null')) . "'>";
            echo $call['SipCode'] ?? 'NULL';
            echo "</td>";
            echo "<td>{$call['Durum']}</td>";
            echo "<td>" . ($call['PickupTime'] ?? '-') . "</td>";
            echo "<td class='sip-error'>$problem</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Düzeltme butonu
        echo "<form method='POST'>";
        echo "<button type='submit' name='fix_sipcodes' class='btn'>🔧 Sorunlu Kayıtları Düzelt</button>";
        echo "</form>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Hata: " . $e->getMessage() . "</div>";
}

// Düzeltme işlemi
if (isset($_POST['fix_sipcodes'])) {
    echo "<div style='background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0;'>";
    echo "<h3>🔧 Düzeltme İşlemi Başlatıldı...</h3>";
    
    $fixed = 0;
    
    try {
        // 1. PickupTime var ama SipCode ≠ 200 olanları düzelt
        $stmt = $pdo->prepare("
            UPDATE CALLS 
            SET SipCode = 200, Durum = 'Cevaplandı'
            WHERE PickupTime IS NOT NULL AND SipCode != 200
        ");
        $stmt->execute();
        $count1 = $stmt->rowCount();
        echo "<p>✅ PickupTime var olanlar: $count1 kayıt → SipCode = 200</p>";
        $fixed += $count1;
        
        // 2. Durum='Cevaplandı' ama SipCode ≠ 200 olanları düzelt
        $stmt = $pdo->prepare("
            UPDATE CALLS 
            SET SipCode = 200
            WHERE Durum = 'Cevaplandı' AND SipCode != 200
        ");
        $stmt->execute();
        $count2 = $stmt->rowCount();
        echo "<p>✅ Durum='Cevaplandı' olanlar: $count2 kayıt → SipCode = 200</p>";
        $fixed += $count2;
        
        // 3. Status=2 ve SipCode=NULL olanları kontrol et
        $stmt = $pdo->query("
            SELECT id, Durum, PickupTime 
            FROM CALLS 
            WHERE Status = 2 AND SipCode IS NULL
        ");
        $nullCodes = $stmt->fetchAll();
        
        foreach ($nullCodes as $call) {
            $newSipCode = 404; // Varsayılan: Cevapsız
            
            if ($call['PickupTime']) {
                $newSipCode = 200; // Cevaplandı
            } elseif (stripos($call['Durum'], 'Meşgul') !== false) {
                $newSipCode = 486;
            } elseif (stripos($call['Durum'], 'Tıkanıklık') !== false || stripos($call['Durum'], 'Congestion') !== false) {
                $newSipCode = 503;
            } elseif (stripos($call['Durum'], 'Cevaplandı') !== false) {
                $newSipCode = 200;
            }
            
            $stmt = $pdo->prepare("UPDATE CALLS SET SipCode = ? WHERE id = ?");
            $stmt->execute([$newSipCode, $call['id']]);
        }
        
        $count3 = count($nullCodes);
        echo "<p>✅ SipCode=NULL olanlar: $count3 kayıt düzeltildi</p>";
        $fixed += $count3;
        
        echo "<div class='success'>";
        echo "<strong>✅ Toplam $fixed kayıt düzeltildi!</strong>";
        echo "</div>";
        
        echo "<p><a href='check_sipcode.php' class='btn'>🔄 Sayfayı Yenile</a></p>";
        
    } catch (Exception $e) {
        echo "<div class='error'>❌ Düzeltme hatası: " . $e->getMessage() . "</div>";
    }
    
    echo "</div>";
}

// En son aramalar
echo "<h2>📞 Son 10 Arama</h2>";
try {
    $stmt = $pdo->query("
        SELECT 
            id, PhoneNumber, Adi, Status, SipCode, Durum, 
            PickupTime, EndCallTime, CallCount, Locked,
            LastCallTime
        FROM CALLS
        ORDER BY id DESC
        LIMIT 10
    ");
    $recent = $stmt->fetchAll();
    
    echo "<table>";
    echo "<tr>";
    echo "<th>ID</th><th>Telefon</th><th>Adı</th><th>Status</th>";
    echo "<th>SipCode</th><th>Durum</th><th>PickupTime</th><th>EndCallTime</th>";
    echo "</tr>";
    
    foreach ($recent as $call) {
        echo "<tr>";
        echo "<td>{$call['id']}</td>";
        echo "<td>{$call['PhoneNumber']}</td>";
        echo "<td>{$call['Adi']}</td>";
        echo "<td>{$call['Status']}</td>";
        
        $sipClass = '';
        if ($call['SipCode'] == 200) $sipClass = 'sip-ok';
        elseif ($call['SipCode'] === null) $sipClass = 'sip-null';
        else $sipClass = 'sip-error';
        
        echo "<td class='$sipClass'>" . ($call['SipCode'] ?? 'NULL') . "</td>";
        echo "<td>{$call['Durum']}</td>";
        echo "<td>" . ($call['PickupTime'] ?? '-') . "</td>";
        echo "<td>" . ($call['EndCallTime'] ?? '-') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Hata: " . $e->getMessage() . "</div>";
}

echo "<div style='margin-top: 20px;'>";
echo "<a href='test_insert_call.php' class='btn'>📞 Test Arama</a> ";
echo "<a href='otomatikarama.php' class='btn' style='background: #6c757d;'>🏠 Ana Sayfa</a>";
echo "</div>";

echo "</div></body></html>";
