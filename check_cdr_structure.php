<?php
/**
 * CDR Tablo Yapısı ve Gerçek Veri Kontrolü
 */

require_once 'config.php';

echo "<!DOCTYPE html>";
echo "<html lang='tr'><head><meta charset='UTF-8'>";
echo "<title>CDR Yapı Kontrolü</title>";
echo "<style>
    body { font-family: Arial; margin: 20px; background: #f5f5f5; }
    .container { max-width: 1400px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
    h1 { color: #333; border-bottom: 3px solid #667eea; padding-bottom: 10px; }
    .info { background: #d1ecf1; color: #0c5460; padding: 15px; margin: 10px 0; border-radius: 5px; }
    .warning { background: #fff3cd; color: #856404; padding: 15px; margin: 10px 0; border-radius: 5px; }
    table { width: 100%; border-collapse: collapse; margin: 10px 0; font-size: 12px; }
    th, td { padding: 6px; border: 1px solid #ddd; text-align: left; word-break: break-word; }
    th { background: #667eea; color: white; position: sticky; top: 0; }
    .highlight { background: #fff3cd; font-weight: bold; }
    pre { background: #f4f4f4; padding: 10px; border-radius: 5px; font-size: 11px; overflow-x: auto; }
</style></head><body><div class='container'>";

echo "<h1>🔍 CDR Tablo Yapısı ve Veri Analizi</h1>";

// CDR bağlantısı
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
    
    // Tablo yapısı
    echo "<h2>📋 CDR Tablo Yapısı</h2>";
    $columns = $pdoCdr->query("SHOW COLUMNS FROM cdr")->fetchAll();
    echo "<table style='width: auto;'>";
    echo "<tr><th>Sütun Adı</th><th>Veri Tipi</th><th>Açıklama</th></tr>";
    
    $columnDescriptions = [
        'calldate' => 'Arama başlangıç tarihi ve saati',
        'clid' => 'Caller ID (Arayan kimliği)',
        'src' => 'Kaynak numara (Arayan)',
        'dst' => 'Hedef (Extension, numara veya hangup)',
        'dcontext' => 'Dialplan Context (auto-calls-template-2 gibi)',
        'channel' => 'Kullanılan kanal (SIP/Telsam-xxx)',
        'dstchannel' => 'Hedef kanal',
        'lastapp' => 'Son çalışan uygulama (Hangup, Dial, vb.)',
        'lastdata' => 'Son uygulama parametreleri',
        'duration' => 'Toplam arama süresi (saniye)',
        'billsec' => 'Konuşma süresi (saniye)',
        'disposition' => 'Arama sonucu (ANSWERED, NO ANSWER, BUSY, vb.)',
        'amaflags' => 'AMA bayrakları',
        'accountcode' => 'Hesap kodu',
        'uniqueid' => 'Benzersiz arama ID',
        'userfield' => 'Kullanıcı tanımlı alan',
        'peeraccount' => 'Peer hesap',
        'linkedid' => 'Bağlantılı arama ID',
        'sequence' => 'Sıra numarası'
    ];
    
    foreach ($columns as $col) {
        $desc = $columnDescriptions[$col['Field']] ?? '-';
        echo "<tr>";
        echo "<td><strong>{$col['Field']}</strong></td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>$desc</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Son 5 CDR kaydının TÜM alanlarını göster
    echo "<h2>📞 Son 5 CDR Kaydı (TÜM Alanlar)</h2>";
    $stmt = $pdoCdr->query("SELECT * FROM cdr ORDER BY calldate DESC LIMIT 5");
    $records = $stmt->fetchAll();
    
    if (empty($records)) {
        echo "<div class='warning'>⚠️ CDR tablosunda kayıt yok</div>";
    } else {
        echo "<div style='overflow-x: auto;'>";
        echo "<table>";
        echo "<tr>";
        foreach (array_keys($records[0]) as $col) {
            if (!is_numeric($col)) {
                echo "<th>$col</th>";
            }
        }
        echo "</tr>";
        
        foreach ($records as $rec) {
            echo "<tr>";
            foreach ($rec as $key => $value) {
                if (!is_numeric($key)) {
                    // dst ve dcontext'i vurgula
                    $class = ($key == 'dst' || $key == 'dcontext' || $key == 'lastdata') ? 'highlight' : '';
                    echo "<td class='$class'>" . htmlspecialchars($value ?? '-') . "</td>";
                }
            }
            echo "</tr>";
        }
        echo "</table>";
        echo "</div>";
    }
    
    // dcontext = 'auto-calls-template-2' olan kayıtlar
    echo "<h2>🎯 auto-calls-template-2 Context'li Kayıtlar</h2>";
    $stmt = $pdoCdr->query("
        SELECT calldate, clid, src, dst, dcontext, channel, lastapp, lastdata, 
               duration, billsec, disposition, uniqueid
        FROM cdr 
        WHERE dcontext LIKE '%auto-calls%' OR dcontext LIKE '%template%'
        ORDER BY calldate DESC 
        LIMIT 10
    ");
    $templateRecords = $stmt->fetchAll();
    
    if (empty($templateRecords)) {
        echo "<div class='warning'>⚠️ auto-calls context'li kayıt bulunamadı</div>";
    } else {
        echo "<div style='overflow-x: auto;'>";
        echo "<table>";
        echo "<tr>";
        echo "<th>Tarih</th><th>CLID</th><th>Src</th><th>Dst</th><th>DContext</th>";
        echo "<th>Channel</th><th>LastApp</th><th>LastData</th><th>Duration</th><th>Billsec</th><th>Disposition</th>";
        echo "</tr>";
        
        foreach ($templateRecords as $rec) {
            echo "<tr>";
            echo "<td>{$rec['calldate']}</td>";
            echo "<td>{$rec['clid']}</td>";
            echo "<td>{$rec['src']}</td>";
            echo "<td class='highlight'>{$rec['dst']}</td>";
            echo "<td class='highlight'>{$rec['dcontext']}</td>";
            echo "<td>{$rec['channel']}</td>";
            echo "<td>{$rec['lastapp']}</td>";
            echo "<td class='highlight'>" . htmlspecialchars($rec['lastdata'] ?? '-') . "</td>";
            echo "<td>{$rec['duration']}s</td>";
            echo "<td>{$rec['billsec']}s</td>";
            echo "<td><strong>{$rec['disposition']}</strong></td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "</div>";
    }
    
    // Analiz ve öneriler
    echo "<h2>💡 Analiz ve Çözüm</h2>";
    echo "<div class='info'>";
    echo "<h3>🔍 Sorun Tespit:</h3>";
    echo "<p>CDR kaydında <strong>dst</strong> alanı 'hangup' (extension adı) görünüyor.</p>";
    echo "<p><strong>Beklenen:</strong></p>";
    echo "<ul>";
    echo "<li><strong>src</strong> = 08503338007 (Arayan - Sistemin CallerID'si) ✅</li>";
    echo "<li><strong>dst</strong> = 05307711717 (Aranan - Müşteri telefonu) ❌ Şu an 'hangup'</li>";
    echo "</ul>";
    echo "<p>Gerçek telefon numarası muhtemelen şuralarda:</p>";
    echo "<ul>";
    echo "<li><strong>dst</strong> - İdeal durum, ama şu an 'hangup' extension'ı var</li>";
    echo "<li><strong>lastdata</strong> - Dial komutu parametreleri (SIP/trunk/NUMARA formatında)</li>";
    echo "<li><strong>userfield</strong> - Özel alan (varsa Call ID)</li>";
    echo "</ul>";
    
    echo "<h3>✅ Uygulanan Çözüm:</h3>";
    echo "<p>CDR eşleştirmesi için telefon numarasını şu sırayla arıyoruz:</p>";
    echo "<ol>";
    echo "<li><strong>userfield</strong> = Call ID (en güvenilir, ama şu an boş)</li>";
    echo "<li><strong>dst</strong> = CALLS.PhoneNumber (ideal, ama şu an 'hangup')</li>";
    echo "<li><strong>src</strong> = CALLS.PhoneNumber (nadiren)</li>";
    echo "<li><strong>lastdata LIKE</strong> = CALLS.PhoneNumber (parametre içinde)</li>";
    echo "</ol>";
    echo "<p>Context kontrolü: <code>dcontext LIKE '%auto-calls%'</code></p>";
    echo "</div>";
    
    // SQL örneği
    echo "<h3>📝 Güncellenmiş SQL Sorgusu:</h3>";
    echo "<pre>";
    echo "-- Telefon numarasını bul (lastdata veya src'den)
SELECT 
    calldate, 
    disposition,
    CASE
        WHEN lastdata LIKE '%/%' THEN SUBSTRING_INDEX(lastdata, '/', -1)
        ELSE src
    END as phone_number,
    src,
    dst,
    lastdata,
    dcontext
FROM cdr
WHERE dcontext LIKE '%auto-calls%'
ORDER BY calldate DESC
LIMIT 10;";
    echo "</pre>";
    
    // Test sorgusu çalıştır
    echo "<h2>🧪 Test: Telefon Numarası Parse</h2>";
    $stmt = $pdoCdr->query("
        SELECT 
            calldate, 
            disposition,
            CASE
                WHEN lastdata LIKE '%/%' THEN SUBSTRING_INDEX(SUBSTRING_INDEX(lastdata, '/', -1), ',', 1)
                ELSE src
            END as parsed_phone,
            src,
            dst,
            lastdata,
            dcontext
        FROM cdr
        WHERE (dcontext LIKE '%auto-calls%' OR dcontext LIKE '%template%')
        ORDER BY calldate DESC
        LIMIT 5
    ");
    $testRecords = $stmt->fetchAll();
    
    if (!empty($testRecords)) {
        echo "<table>";
        echo "<tr><th>Tarih</th><th>Parse Edilen Telefon</th><th>Src</th><th>Dst</th><th>LastData</th><th>Disposition</th></tr>";
        foreach ($testRecords as $rec) {
            echo "<tr>";
            echo "<td>{$rec['calldate']}</td>";
            echo "<td class='highlight'><strong>{$rec['parsed_phone']}</strong></td>";
            echo "<td>{$rec['src']}</td>";
            echo "<td>{$rec['dst']}</td>";
            echo "<td>" . htmlspecialchars(substr($rec['lastdata'] ?? '-', 0, 50)) . "</td>";
            echo "<td>{$rec['disposition']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<div class='error' style='background: #f8d7da; color: #721c24; padding: 15px; margin: 10px 0; border-radius: 5px;'>❌ Hata: " . $e->getMessage() . "</div>";
}

echo "<div style='margin-top: 20px;'>";
echo "<a href='sync_cdr_sipcode.php' class='btn' style='display: inline-block; padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px;'>🔄 Sync Sayfası</a> ";
echo "<a href='otomatikarama.php' class='btn' style='display: inline-block; padding: 10px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 5px;'>🏠 Ana Sayfa</a>";
echo "</div>";

echo "</div></body></html>";
