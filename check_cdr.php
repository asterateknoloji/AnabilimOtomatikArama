<?php
/**
 * CDR Tablosu Kontrolü ve Düzeltme
 */

require_once 'config.php';

echo "<!DOCTYPE html>";
echo "<html lang='tr'><head><meta charset='UTF-8'>";
echo "<title>CDR Tablo Kontrolü</title>";
echo "<style>
    body { font-family: Arial; margin: 20px; background: #f5f5f5; }
    .container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
    h1 { color: #333; border-bottom: 3px solid #667eea; padding-bottom: 10px; }
    .success { background: #d4edda; color: #155724; padding: 15px; margin: 10px 0; border-radius: 5px; }
    .error { background: #f8d7da; color: #721c24; padding: 15px; margin: 10px 0; border-radius: 5px; }
    .info { background: #d1ecf1; color: #0c5460; padding: 15px; margin: 10px 0; border-radius: 5px; }
    .btn { display: inline-block; padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; border: none; cursor: pointer; margin: 5px; }
    pre { background: #f4f4f4; padding: 15px; border-radius: 5px; overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; margin: 10px 0; }
    th, td { padding: 8px; border: 1px solid #ddd; text-align: left; }
    th { background: #667eea; color: white; }
</style></head><body><div class='container'>";

echo "<h1>📊 CDR Veritabanı Kontrolü</h1>";

// 1. asteriskcdrdb veritabanını kontrol et
echo "<h2>1️⃣ asteriskcdrdb Veritabanı</h2>";
try {
    $dbHost = EnvLoader::get('DB_HOST', '192.168.181.49');
    $dbUser = EnvLoader::get('DB_USER', 'astera');
    $dbPass = EnvLoader::get('DB_PASS', 'astera2012');
    
    // asteriskcdrdb'ye bağlan
    $pdoCdr = new PDO(
        "mysql:host={$dbHost};charset=utf8mb4",
        $dbUser,
        $dbPass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Veritabanı var mı?
    $stmt = $pdoCdr->query("SHOW DATABASES LIKE 'asteriskcdrdb'");
    if ($stmt->rowCount() == 0) {
        echo "<div class='error'>❌ asteriskcdrdb veritabanı bulunamadı!</div>";
        echo "<form method='POST'>";
        echo "<button type='submit' name='create_cdrdb' class='btn'>Veritabanını Oluştur</button>";
        echo "</form>";
        
        if (isset($_POST['create_cdrdb'])) {
            try {
                $pdoCdr->exec("CREATE DATABASE asteriskcdrdb CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                echo "<div class='success'>✅ asteriskcdrdb veritabanı oluşturuldu!</div>";
                echo "<meta http-equiv='refresh' content='2'>";
            } catch (Exception $e) {
                echo "<div class='error'>❌ Hata: " . $e->getMessage() . "</div>";
            }
        }
    } else {
        echo "<div class='success'>✅ asteriskcdrdb veritabanı mevcut</div>";
        
        // Veritabanına geç
        $pdoCdr->exec("USE asteriskcdrdb");
        
        // cdr tablosunu kontrol et
        echo "<h2>2️⃣ CDR Tablosu</h2>";
        $stmt = $pdoCdr->query("SHOW TABLES LIKE 'cdr'");
        if ($stmt->rowCount() == 0) {
            echo "<div class='error'>❌ cdr tablosu bulunamadı!</div>";
            echo "<form method='POST'>";
            echo "<button type='submit' name='create_cdr_table' class='btn'>CDR Tablosunu Oluştur</button>";
            echo "</form>";
            
            if (isset($_POST['create_cdr_table'])) {
                try {
                    $pdoCdr->exec("
                        CREATE TABLE cdr (
                            calldate datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
                            clid varchar(80) NOT NULL DEFAULT '',
                            src varchar(80) NOT NULL DEFAULT '',
                            dst varchar(80) NOT NULL DEFAULT '',
                            dcontext varchar(80) NOT NULL DEFAULT '',
                            channel varchar(80) NOT NULL DEFAULT '',
                            dstchannel varchar(80) NOT NULL DEFAULT '',
                            lastapp varchar(80) NOT NULL DEFAULT '',
                            lastdata varchar(80) NOT NULL DEFAULT '',
                            duration int(11) NOT NULL DEFAULT 0,
                            billsec int(11) NOT NULL DEFAULT 0,
                            disposition varchar(45) NOT NULL DEFAULT '',
                            amaflags int(11) NOT NULL DEFAULT 0,
                            accountcode varchar(20) NOT NULL DEFAULT '',
                            uniqueid varchar(32) NOT NULL DEFAULT '',
                            userfield varchar(255) NOT NULL DEFAULT '',
                            peeraccount varchar(20) NOT NULL DEFAULT '',
                            linkedid varchar(32) NOT NULL DEFAULT '',
                            sequence int(11) NOT NULL DEFAULT 0,
                            PRIMARY KEY (calldate, uniqueid),
                            KEY src (src),
                            KEY dst (dst),
                            KEY dcontext (dcontext),
                            KEY uniqueid (uniqueid)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                    ");
                    echo "<div class='success'>✅ cdr tablosu oluşturuldu!</div>";
                    echo "<meta http-equiv='refresh' content='2'>";
                } catch (Exception $e) {
                    echo "<div class='error'>❌ Hata: " . $e->getMessage() . "</div>";
                }
            }
        } else {
            echo "<div class='success'>✅ cdr tablosu mevcut</div>";
            
            // Tablo yapısını göster
            echo "<h3>Tablo Yapısı:</h3>";
            $columns = $pdoCdr->query("SHOW COLUMNS FROM cdr")->fetchAll();
            echo "<table>";
            echo "<tr><th>Sütun</th><th>Tip</th><th>Null</th><th>Varsayılan</th></tr>";
            foreach ($columns as $col) {
                echo "<tr>";
                echo "<td>{$col['Field']}</td>";
                echo "<td>{$col['Type']}</td>";
                echo "<td>{$col['Null']}</td>";
                echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // Son CDR kayıtları
            echo "<h3>Son CDR Kayıtları:</h3>";
            try {
                $stmt = $pdoCdr->query("SELECT * FROM cdr ORDER BY calldate DESC LIMIT 10");
                $records = $stmt->fetchAll();
                
                if (empty($records)) {
                    echo "<div class='info'>ℹ️ Henüz CDR kaydı yok</div>";
                } else {
                    echo "<table>";
                    echo "<tr><th>Tarih</th><th>Src</th><th>Dst</th><th>Duration</th><th>Billsec</th><th>Disposition</th></tr>";
                    foreach ($records as $rec) {
                        echo "<tr>";
                        echo "<td>{$rec['calldate']}</td>";
                        echo "<td>{$rec['src']}</td>";
                        echo "<td>{$rec['dst']}</td>";
                        echo "<td>{$rec['duration']}s</td>";
                        echo "<td>{$rec['billsec']}s</td>";
                        echo "<td><strong>{$rec['disposition']}</strong></td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                }
            } catch (Exception $e) {
                echo "<div class='error'>❌ Kayıtlar okunamadı: " . $e->getMessage() . "</div>";
            }
        }
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Bağlantı hatası: " . $e->getMessage() . "</div>";
}

// 3. AGI Scriptleri kontrolü
echo "<h2>3️⃣ AGI Scriptleri (Alternatif Durum Güncelleme)</h2>";
echo "<div class='info'>";
echo "💡 <strong>Önemli:</strong> Asterisk CDR yazamasa bile, AGI scriptlerimiz durumu güncelleyebilir.<br>";
echo "AGI scriptleri aramalar sırasında otomatik olarak CALLS tablosunu günceller.<br><br>";
echo "<strong>AGI Scriptleri:</strong><br>";
echo "<ul>";
echo "<li>📝 <code>asterisk/agi/update_call_status.php</code> - Arama durumunu günceller</li>";
echo "<li>📞 <code>asterisk/agi/update_call_dtmf.php</code> - Tuşlama bilgisini kaydeder</li>";
echo "<li>🔚 <code>asterisk/agi/update_call_end.php</code> - Arama bitişini kaydeder</li>";
echo "</ul>";
echo "</div>";

// 4. Manuel test kaydı ekle
echo "<h2>4️⃣ Manuel Test CDR Kaydı</h2>";
if (isset($_POST['add_test_cdr'])) {
    try {
        $pdoCdr->exec("USE asteriskcdrdb");
        $stmt = $pdoCdr->prepare("
            INSERT INTO cdr (
                calldate, clid, src, dst, dcontext, channel, 
                lastapp, duration, billsec, disposition, amaflags, uniqueid
            ) VALUES (
                NOW(), '\"Test\" <05551234567>', '05551234567', 'hangup', 
                'auto-calls', 'SIP/Test-00000001', 'Hangup', 
                20, 15, 'ANSWERED', 3, ?
            )
        ");
        $stmt->execute(['TEST-' . time()]);
        echo "<div class='success'>✅ Test CDR kaydı eklendi!</div>";
        echo "<meta http-equiv='refresh' content='2'>";
    } catch (Exception $e) {
        echo "<div class='error'>❌ Test kaydı eklenemedi: " . $e->getMessage() . "</div>";
    }
}

echo "<form method='POST'>";
echo "<button type='submit' name='add_test_cdr' class='btn'>🧪 Test CDR Kaydı Ekle</button>";
echo "</form>";

// Çözüm önerileri
echo "<h2>💡 Çözüm Önerileri</h2>";
echo "<div class='info'>";
echo "<ol>";
echo "<li><strong>CDR Tablosu Yoksa:</strong> Yukarıdaki 'CDR Tablosunu Oluştur' butonunu kullanın</li>";
echo "<li><strong>Asterisk Yapılandırması:</strong> <code>/etc/asterisk/cdr_adaptive_odbc.conf</code> dosyasını kontrol edin</li>";
echo "<li><strong>ODBC Bağlantısı:</strong> <code>/etc/odbc.ini</code> ve <code>/etc/asterisk/res_odbc.conf</code> ayarlarını kontrol edin</li>";
echo "<li><strong>AGI ile Çözüm:</strong> CDR çalışmasa bile, AGI scriptleri CALLS tablosunu direkt günceller</li>";
echo "</ol>";
echo "</div>";

echo "<div style='margin-top: 20px;'>";
echo "<a href='test_insert_call.php' class='btn'>📞 Test Arama Ekle</a> ";
echo "<a href='otomatikarama.php' class='btn' style='background: #6c757d;'>🏠 Ana Sayfa</a>";
echo "</div>";

echo "</div></body></html>";
