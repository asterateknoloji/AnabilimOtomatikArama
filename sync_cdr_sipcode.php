<?php
/**
 * CDR'dan SipCode Senkronizasyonu
 * asteriskcdrdb.cdr tablosundaki disposition bilgisini kullanarak
 * CALLS tablosundaki SipCode alanını günceller
 */

require_once 'config.php';

echo "<!DOCTYPE html>";
echo "<html lang='tr'><head><meta charset='UTF-8'>";
echo "<title>CDR → SipCode Sync</title>";
echo "<style>
    body { font-family: Arial; margin: 20px; background: #f5f5f5; }
    .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
    h1 { color: #333; border-bottom: 3px solid #667eea; padding-bottom: 10px; }
    .success { background: #d4edda; color: #155724; padding: 15px; margin: 10px 0; border-radius: 5px; }
    .error { background: #f8d7da; color: #721c24; padding: 15px; margin: 10px 0; border-radius: 5px; }
    .warning { background: #fff3cd; color: #856404; padding: 15px; margin: 10px 0; border-radius: 5px; }
    .info { background: #d1ecf1; color: #0c5460; padding: 15px; margin: 10px 0; border-radius: 5px; }
    .btn { display: inline-block; padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; border: none; cursor: pointer; margin: 5px; font-size: 14px; }
    .btn-success { background: #28a745; }
    .btn-danger { background: #dc3545; }
    table { width: 100%; border-collapse: collapse; margin: 10px 0; font-size: 13px; }
    th, td { padding: 8px; border: 1px solid #ddd; text-align: left; }
    th { background: #667eea; color: white; }
    pre { background: #f4f4f4; padding: 10px; border-radius: 5px; font-size: 12px; }
    .form-group { margin: 15px 0; }
    input[type='text'] { padding: 8px; width: 300px; border: 2px solid #ddd; border-radius: 5px; }
</style></head><body><div class='container'>";

echo "<h1>🔄 CDR → SipCode Senkronizasyonu</h1>";

// Disposition → SipCode mapping
$dispositionToSipCode = [
    'ANSWERED' => 200,
    'NO ANSWER' => 480,
    'BUSY' => 486,
    'FAILED' => 503,
    'CONGESTION' => 503
];

echo "<div class='info'>";
echo "<h3>📖 Disposition → SipCode Eşleştirmesi:</h3>";
echo "<table style='width: auto;'>";
echo "<tr><th>CDR Disposition</th><th>→</th><th>SipCode</th><th>Anlamı</th></tr>";
echo "<tr><td>ANSWERED</td><td>→</td><td>200</td><td>Cevaplandı (Başarılı)</td></tr>";
echo "<tr><td>NO ANSWER</td><td>→</td><td>480</td><td>Cevapsız</td></tr>";
echo "<tr><td>BUSY</td><td>→</td><td>486</td><td>Meşgul</td></tr>";
echo "<tr><td>FAILED</td><td>→</td><td>503</td><td>Başarısız</td></tr>";
echo "<tr><td>CONGESTION</td><td>→</td><td>503</td><td>Tıkanıklık</td></tr>";
echo "</table>";
echo "</div>";

// CDR veritabanı bağlantısı
$pdoCdr = null;
try {
    $dbHost = EnvLoader::get('DB_HOST', '192.168.181.49');
    $dbUser = EnvLoader::get('DB_USER', 'astera');
    $dbPass = EnvLoader::get('DB_PASS', 'astera2012');
    
    $pdoCdr = new PDO(
        "mysql:host={$dbHost};dbname=asteriskcdrdb;charset=utf8mb4",
        $dbUser,
        $dbPass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "<div class='success'>✅ asteriskcdrdb veritabanına bağlantı başarılı</div>";
} catch (Exception $e) {
    echo "<div class='error'>❌ CDR veritabanına bağlanılamadı: " . $e->getMessage() . "</div>";
    echo "<p>CDR tablosu olmadan senkronizasyon yapılamaz.</p>";
    echo "</div></body></html>";
    exit;
}

// Tek numara için sync
if (isset($_POST['sync_phone'])) {
    $phoneNumber = preg_replace('/[^0-9]/', '', $_POST['phone_number']);
    
    echo "<div style='background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0;'>";
    echo "<h3>🔄 Telefon Numarası için Sync: $phoneNumber</h3>";
    
    try {
        // CALLS tablosundan kaydı bul
        $stmt = $pdo->prepare("SELECT id, PhoneNumber, SipCode, Status FROM CALLS WHERE PhoneNumber = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$phoneNumber]);
        $call = $stmt->fetch();
        
        if (!$call) {
            echo "<div class='error'>❌ CALLS tablosunda bu numara bulunamadı: $phoneNumber</div>";
        } else {
            echo "<p><strong>CALLS Kaydı Bulundu:</strong> ID = {$call['id']}, Mevcut SipCode = " . ($call['SipCode'] ?? 'NULL') . "</p>";
            
            // ÇÖZÜM 1: userfield'da Call ID varsa, direkt eşleştir
            $cdrStmt = $pdoCdr->prepare("
                SELECT 
                    calldate, 
                    src, 
                    dst, 
                    dcontext,
                    lastdata,
                    disposition, 
                    duration, 
                    billsec,
                    userfield
                FROM cdr 
                WHERE userfield = ?
                AND (dcontext LIKE '%auto-calls%' OR dcontext LIKE '%template%')
                ORDER BY calldate DESC 
                LIMIT 1
            ");
            $cdrStmt->execute([$call['id']]);
            $cdr = $cdrStmt->fetch();
            
            // ÇÖZÜM 2: userfield yoksa, telefon numarası ile eşleştir
            // Template için özel: context + src + zaman aralığı ile eşleştir
            if (!$cdr) {
                echo "<p class='warning' style='background: #fff3cd; padding: 10px; border-radius: 5px;'>⚠️ userfield ile eşleşme bulunamadı, telefon numarası ile deneniyor...</p>";
                
                // Önce CALLS kaydından EndCallTime'ı al
                $callStmt = $pdo->prepare("SELECT EndCallTime FROM CALLS WHERE id = ?");
                $callStmt->execute([$call['id']]);
                $callInfo = $callStmt->fetch();
                
                if ($callInfo && $callInfo['EndCallTime']) {
                    // Template için: context + src (sistem numarası) + zaman aralığı ile eşleştir
                    // dst = "hangup" olduğu için, src ve context'e göre bul
                    $cdrStmt = $pdoCdr->prepare("
                        SELECT 
                            calldate, 
                            src, 
                            dst, 
                            dcontext,
                            lastdata,
                            disposition, 
                            duration, 
                            billsec,
                            userfield,
                            uniqueid
                        FROM cdr 
                        WHERE dcontext LIKE '%auto-calls%' OR dcontext LIKE '%template%'
                        AND calldate >= DATE_SUB(?, INTERVAL 2 MINUTE)
                        AND calldate <= DATE_ADD(?, INTERVAL 2 MINUTE)
                        ORDER BY ABS(TIMESTAMPDIFF(SECOND, calldate, ?)) ASC
                        LIMIT 1
                    ");
                    $cdrStmt->execute([
                        $callInfo['EndCallTime'],
                        $callInfo['EndCallTime'],
                        $callInfo['EndCallTime']
                    ]);
                    $cdr = $cdrStmt->fetch();
                    
                    if ($cdr) {
                        echo "<p class='info' style='background: #d1ecf1; padding: 10px; border-radius: 5px;'>✅ Template araması context + zaman ile eşleştirildi (dst=hangup olduğu için)</p>";
                    }
                }
                
                // Hala bulamadıysa, telefon numarası ile dene (normal aramalar için)
                if (!$cdr) {
                    $cdrStmt = $pdoCdr->prepare("
                        SELECT 
                            calldate, 
                            src, 
                            dst, 
                            dcontext,
                            lastdata,
                            disposition, 
                            duration, 
                            billsec,
                            userfield,
                            uniqueid
                        FROM cdr 
                        WHERE (
                            dst = ? OR 
                            src = ? OR 
                            lastdata LIKE CONCAT('%', ?, '%')
                        )
                        AND (dcontext LIKE '%auto-calls%' OR dcontext LIKE '%template%' OR dcontext = '')
                        ORDER BY calldate DESC 
                        LIMIT 1
                    ");
                    $cdrStmt->execute([
                        $phoneNumber,  // dst (aranan numara - normal aramalar için)
                        $phoneNumber,  // src (nadiren)
                        $phoneNumber   // lastdata LIKE
                    ]);
                    $cdr = $cdrStmt->fetch();
                }
            }
            
            if (!$cdr) {
                echo "<div class='error'>❌ CDR tablosunda bu numara bulunamadı</div>";
                echo "<p><strong>Aramalar:</strong></p>";
                echo "<ul>";
                echo "<li>userfield = {$call['id']} (Call ID ile)</li>";
                echo "<li>src, dst veya lastdata = $phoneNumber (Telefon ile)</li>";
                echo "<li>dcontext LIKE '%auto-calls%' veya '%template%'</li>";
                echo "</ul>";
            } else {
                echo "<p><strong>CDR Kaydı Bulundu:</strong></p>";
                echo "<table style='width: auto;'>";
                echo "<tr><th>Alan</th><th>Değer</th></tr>";
                echo "<tr><td>Tarih</td><td>{$cdr['calldate']}</td></tr>";
                echo "<tr><td>Src</td><td>{$cdr['src']}</td></tr>";
                echo "<tr><td>Dst</td><td>{$cdr['dst']}</td></tr>";
                echo "<tr><td>DContext</td><td>{$cdr['dcontext']}</td></tr>";
                echo "<tr><td>UserField (Call ID)</td><td>" . ($cdr['userfield'] ?: '<span style=\"color: #dc3545;\">BOŞ!</span>') . "</td></tr>";
                echo "<tr><td><strong>Disposition</strong></td><td><strong>{$cdr['disposition']}</strong></td></tr>";
                echo "<tr><td>Duration</td><td>{$cdr['duration']}s</td></tr>";
                echo "<tr><td>Billsec</td><td>{$cdr['billsec']}s</td></tr>";
                echo "</table>";
                
                if (empty($cdr['userfield'])) {
                    echo "<div class='warning'>⚠️ <strong>UserField boş!</strong> Dialplan'da Call ID set edilmemiş. AGI scripti ile userfield'a Call ID yazılmalı.</div>";
                }
                
                // SipCode belirle
                $newSipCode = $dispositionToSipCode[$cdr['disposition']] ?? 503;
                echo "<p><strong>Yeni SipCode:</strong> {$cdr['disposition']} → <span style='color: #28a745; font-weight: bold;'>$newSipCode</span></p>";
                
                // Güncelle
                $updateStmt = $pdo->prepare("UPDATE CALLS SET SipCode = ? WHERE id = ?");
                $updateStmt->execute([$newSipCode, $call['id']]);
                
                echo "<div class='success'>✅ CALLS tablosu güncellendi! Call ID: {$call['id']}, SipCode: $newSipCode</div>";
            }
        }
    } catch (Exception $e) {
        echo "<div class='error'>❌ Hata: " . $e->getMessage() . "</div>";
    }
    
    echo "</div>";
}

// Toplu sync
if (isset($_POST['sync_all'])) {
    echo "<div style='background: #d1ecf1; border-left: 4px solid #17a2b8; padding: 15px; margin: 20px 0;'>";
    echo "<h3>🔄 Toplu Senkronizasyon Başlatıldı...</h3>";
    
    $updated = 0;
    $skipped = 0;
    $errors = 0;
    
    try {
        // SipCode NULL veya hatalı olan CALLS kayıtları
        $stmt = $pdo->query("
            SELECT id, PhoneNumber, SipCode, Status, EndCallTime 
            FROM CALLS 
            WHERE Status = 2 
            AND (SipCode IS NULL OR SipCode = 0)
            AND EndCallTime IS NOT NULL
            ORDER BY id DESC
            LIMIT 100
        ");
        $calls = $stmt->fetchAll();
        
        echo "<p>İşlenecek kayıt sayısı: <strong>" . count($calls) . "</strong></p>";
        
        foreach ($calls as $call) {
            try {
                // ÇÖZÜM 1: userfield (Call ID) ile direkt eşleştir
                $cdrStmt = $pdoCdr->prepare("
                    SELECT disposition, calldate, src, dst, dcontext, lastdata, userfield
                    FROM cdr 
                    WHERE userfield = ?
                    AND (dcontext LIKE '%auto-calls%' OR dcontext LIKE '%template%' OR dcontext = '')
                    AND calldate >= DATE_SUB(?, INTERVAL 1 DAY)
                    ORDER BY calldate DESC 
                    LIMIT 1
                ");
                $cdrStmt->execute([
                    $call['id'],  // userfield = Call ID
                    $call['EndCallTime']
                ]);
                $cdr = $cdrStmt->fetch();
                
                // ÇÖZÜM 2: Template için context + zaman ile eşleştir (dst=hangup olduğu için)
                if (!$cdr && stripos($call['EndCallTime'], '2026') !== false) {
                    $cdrStmt = $pdoCdr->prepare("
                        SELECT disposition, calldate, src, dst, dcontext, lastdata, userfield
                        FROM cdr 
                        WHERE (dcontext LIKE '%auto-calls%' OR dcontext LIKE '%template%')
                        AND calldate >= DATE_SUB(?, INTERVAL 2 MINUTE)
                        AND calldate <= DATE_ADD(?, INTERVAL 2 MINUTE)
                        ORDER BY ABS(TIMESTAMPDIFF(SECOND, calldate, ?)) ASC
                        LIMIT 1
                    ");
                    $cdrStmt->execute([
                        $call['EndCallTime'],
                        $call['EndCallTime'],
                        $call['EndCallTime']
                    ]);
                    $cdr = $cdrStmt->fetch();
                }
                
                // ÇÖZÜM 3: Normal aramalar için telefon numarası ile eşleştir
                if (!$cdr) {
                    $cdrStmt = $pdoCdr->prepare("
                        SELECT disposition, calldate, src, dst, dcontext, lastdata, userfield
                        FROM cdr 
                        WHERE (
                            dst = ? OR 
                            src = ? OR 
                            lastdata LIKE CONCAT('%', ?, '%')
                        )
                        AND calldate >= DATE_SUB(?, INTERVAL 1 DAY)
                        ORDER BY calldate DESC 
                        LIMIT 1
                    ");
                    $cdrStmt->execute([
                        $call['PhoneNumber'],  // dst
                        $call['PhoneNumber'],  // src
                        $call['PhoneNumber'],  // lastdata LIKE
                        $call['EndCallTime']
                    ]);
                    $cdr = $cdrStmt->fetch();
                }
                
                if ($cdr) {
                    $newSipCode = $dispositionToSipCode[$cdr['disposition']] ?? 503;
                    
                    $updateStmt = $pdo->prepare("UPDATE CALLS SET SipCode = ? WHERE id = ?");
                    $updateStmt->execute([$newSipCode, $call['id']]);
                    
                    echo "<p style='color: #28a745;'>✅ ID: {$call['id']}, Tel: {$call['PhoneNumber']}, Disposition: {$cdr['disposition']} → SipCode: $newSipCode</p>";
                    $updated++;
                } else {
                    echo "<p style='color: #6c757d;'>⚠️ ID: {$call['id']}, Tel: {$call['PhoneNumber']} - CDR'da bulunamadı</p>";
                    $skipped++;
                }
            } catch (Exception $e) {
                echo "<p style='color: #dc3545;'>❌ ID: {$call['id']} - Hata: {$e->getMessage()}</p>";
                $errors++;
            }
        }
        
        echo "<div class='success'>";
        echo "<h4>📊 Özet:</h4>";
        echo "<ul>";
        echo "<li>✅ Güncellenen: <strong>$updated</strong></li>";
        echo "<li>⚠️ Atlanan: <strong>$skipped</strong></li>";
        echo "<li>❌ Hata: <strong>$errors</strong></li>";
        echo "</ul>";
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<div class='error'>❌ Toplu güncelleme hatası: " . $e->getMessage() . "</div>";
    }
    
    echo "</div>";
}

// Tek numara için form
echo "<h2>🔍 Tek Telefon Numarası için Sync</h2>";
echo "<form method='POST'>";
echo "<div class='form-group'>";
echo "<label>Telefon Numarası:</label><br>";
echo "<input type='text' name='phone_number' placeholder='05307711717' value='05307711717' required>";
echo "<button type='submit' name='sync_phone' class='btn btn-success'>🔄 Bu Numarayı Sync Et</button>";
echo "</div>";
echo "</form>";

// Toplu sync butonu
echo "<h2>🔄 Toplu Senkronizasyon</h2>";
echo "<div class='warning'>";
echo "<p>⚠️ Bu işlem, SipCode NULL veya 0 olan tüm kayıtları (son 100) CDR tablosu ile eşleştirecek.</p>";
echo "<form method='POST'>";
echo "<button type='submit' name='sync_all' class='btn btn-danger' onclick='return confirm(\"Toplu senkronizasyon başlatılsın mı?\")'>🔄 Toplu Sync Başlat (100 kayıt)</button>";
echo "</form>";
echo "</div>";

// SipCode NULL olan kayıtlar
echo "<h2>📋 SipCode NULL/Hatalı Kayıtlar</h2>";
try {
    $stmt = $pdo->query("
        SELECT id, PhoneNumber, Status, SipCode, Durum, EndCallTime
        FROM CALLS
        WHERE Status = 2 AND (SipCode IS NULL OR SipCode = 0)
        ORDER BY id DESC
        LIMIT 20
    ");
    $nullRecords = $stmt->fetchAll();
    
    if (empty($nullRecords)) {
        echo "<div class='success'>✅ SipCode NULL olan kayıt yok!</div>";
    } else {
        echo "<p>Toplam <strong>" . count($nullRecords) . "</strong> kayıt bulundu (ilk 20):</p>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Telefon</th><th>Status</th><th>SipCode</th><th>Durum</th><th>EndCallTime</th></tr>";
        foreach ($nullRecords as $rec) {
            echo "<tr>";
            echo "<td>{$rec['id']}</td>";
            echo "<td>{$rec['PhoneNumber']}</td>";
            echo "<td>{$rec['Status']}</td>";
            echo "<td>" . ($rec['SipCode'] ?? 'NULL') . "</td>";
            echo "<td>{$rec['Durum']}</td>";
            echo "<td>" . ($rec['EndCallTime'] ?? '-') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Hata: " . $e->getMessage() . "</div>";
}

// Son CDR kayıtları
echo "<h2>📞 Son CDR Kayıtları (asteriskcdrdb.cdr)</h2>";
try {
    $stmt = $pdoCdr->query("
        SELECT calldate, clid, src, dst, disposition, duration, billsec
        FROM cdr
        ORDER BY calldate DESC
        LIMIT 10
    ");
    $cdrRecords = $stmt->fetchAll();
    
    echo "<table>";
    echo "<tr><th>Tarih</th><th>CLID</th><th>Src</th><th>Dst</th><th>Disposition</th><th>Duration</th><th>Billsec</th></tr>";
    foreach ($cdrRecords as $rec) {
        echo "<tr>";
        echo "<td>{$rec['calldate']}</td>";
        echo "<td>{$rec['clid']}</td>";
        echo "<td>{$rec['src']}</td>";
        echo "<td>{$rec['dst']}</td>";
        echo "<td><strong>{$rec['disposition']}</strong></td>";
        echo "<td>{$rec['duration']}s</td>";
        echo "<td>{$rec['billsec']}s</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<div class='error'>❌ CDR kayıtları okunamadı: " . $e->getMessage() . "</div>";
}

echo "<div style='margin-top: 20px;'>";
echo "<a href='check_sipcode.php' class='btn'>📊 SipCode Kontrol</a> ";
echo "<a href='otomatikarama.php' class='btn' style='background: #6c757d;'>🏠 Ana Sayfa</a>";
echo "</div>";

echo "</div></body></html>";
