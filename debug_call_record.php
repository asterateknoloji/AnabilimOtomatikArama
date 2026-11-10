<?php
/**
 * ARAMA KAYDI DEBUG ARACI
 * Belirli bir kaydın neden aramadığını araştırır
 */

header('Content-Type: text/html; charset=utf-8');

require_once __DIR__ . '/config.php';

$recordId = isset($_GET['id']) ? (int)$_GET['id'] : 26759;

echo "<!DOCTYPE html>
<html lang='tr'>
<head>
    <meta charset='UTF-8'>
    <title>Arama Kaydı Debug</title>
    <style>
        body { font-family: 'Courier New', monospace; background: #1e1e1e; color: #d4d4d4; padding: 20px; line-height: 1.8; }
        .container { max-width: 1400px; margin: 0 auto; background: #252526; padding: 40px; border-radius: 10px; }
        h1 { color: #4ec9b0; border-bottom: 3px solid #4ec9b0; padding-bottom: 15px; }
        h2 { color: #569cd6; margin-top: 30px; }
        .success { color: #4ec9b0; font-weight: bold; }
        .error { color: #f48771; font-weight: bold; }
        .warning { color: #dcdcaa; font-weight: bold; }
        .info { color: #9cdcfe; }
        pre { background: #1e1e1e; padding: 20px; border-radius: 8px; border-left: 4px solid #4ec9b0; overflow-x: auto; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; font-size: 14px; }
        th, td { padding: 12px; text-align: left; border-bottom: 2px solid #3e3e42; }
        th { background: #1e1e1e; color: #4ec9b0; font-weight: bold; }
        tr:hover { background: #2d2d30; }
        .box { background: #2d2d30; padding: 25px; margin: 20px 0; border-radius: 8px; border-left: 5px solid #569cd6; }
        .status-badge { padding: 5px 10px; border-radius: 5px; font-weight: bold; display: inline-block; }
        .status-waiting { background: #dcdcaa; color: #1e1e1e; }
        .status-calling { background: #569cd6; color: white; }
        .status-completed { background: #4ec9b0; color: white; }
        .status-failed { background: #f48771; color: white; }
        .issue { background: #f48771; color: white; padding: 10px 15px; border-radius: 5px; margin: 10px 0; }
        .check { background: #4ec9b0; color: white; padding: 10px 15px; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>
<div class='container'>";

echo "<h1>🔍 ARAMA KAYDI DEBUG ARACI</h1>";
echo "<p class='info'>Kayıt ID: <strong>$recordId</strong></p>";
echo "<p class='info'>Tarih: " . date('Y-m-d H:i:s') . "</p>";
echo "<hr>";

try {
    $pdo->exec("SET NAMES utf8mb4");
    
    // 1. Kayıt detaylarını çek
    echo "<div class='box'>";
    echo "<h2>📋 1. KAYIT DETAYLARI</h2>";
    
    $stmt = $pdo->prepare("
        SELECT 
            c.*,
            i.proje_id,
            i.sablon_id,
            i.dosya_adi,
            i.durum as import_durum,
            i.aktif as import_aktif,
            p.proje_adi,
            s.sablon_adi
        FROM CALLS c
        LEFT JOIN imports i ON c.import_id = i.id
        LEFT JOIN projeler p ON i.proje_id = p.id
        LEFT JOIN sablonlar s ON i.sablon_id = s.id
        WHERE c.id = ?
    ");
    $stmt->execute([$recordId]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$record) {
        echo "<p class='error'>❌ Kayıt bulunamadı! ID: $recordId</p>";
        echo "</div></div></body></html>";
        exit;
    }
    
    echo "<table>";
    echo "<tr><th>Alan</th><th>Değer</th></tr>";
    foreach ($record as $key => $value) {
        if (is_numeric($key)) continue;
        
        $displayValue = $value ?? '<span class="warning">NULL</span>';
        
        // Status değerlerini renklendir
        if ($key === 'durum') {
            $statusMap = [
                0 => '<span class="status-badge status-waiting">0 - Bekliyor</span>',
                1 => '<span class="status-badge status-calling">1 - Aranıyor</span>',
                2 => '<span class="status-badge status-completed">2 - Tamamlandı</span>',
                3 => '<span class="status-badge status-failed">3 - Başarısız</span>'
            ];
            $displayValue = $statusMap[$value] ?? $value;
        }
        
        if ($key === 'import_aktif' || $key === 'aktif') {
            $displayValue = $value ? '<span class="success">✅ Aktif</span>' : '<span class="error">❌ Pasif</span>';
        }
        
        echo "<tr>";
        echo "<td><strong>" . htmlspecialchars($key) . "</strong></td>";
        echo "<td>$displayValue</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "</div>";
    
    // 2. Sorun analizi
    echo "<div class='box'>";
    echo "<h2>🔍 2. SORUN ANALİZİ</h2>";
    
    $issues = [];
    $checks = [];
    
    // Durum kontrolü
    if ($record['durum'] == 0) {
        $checks[] = "✅ Kayıt durumu 0 (Bekliyor) - Normal";
    } elseif ($record['durum'] == 1) {
        $issues[] = "⚠️ Kayıt durumu 1 (Aranıyor) - Hala işlemde";
    } elseif ($record['durum'] == 2) {
        $checks[] = "✅ Kayıt durumu 2 (Tamamlandı)";
    } elseif ($record['durum'] == 3) {
        $issues[] = "❌ Kayıt durumu 3 (Başarısız)";
    }
    
    // Import aktiflik kontrolü
    if ($record['import_aktif'] != 1) {
        $issues[] = "❌ Import pasif (import_aktif = " . $record['import_aktif'] . ")";
    } else {
        $checks[] = "✅ Import aktif";
    }
    
    // Import durum kontrolü
    if ($record['import_durum'] == 'stopped') {
        $issues[] = "❌ Import durdurulmuş (durum = stopped)";
    } elseif ($record['import_durum'] == 'completed') {
        $issues[] = "⚠️ Import tamamlanmış (durum = completed)";
    } else {
        $checks[] = "✅ Import durum: " . $record['import_durum'];
    }
    
    // Arama süresi kontrolü
    $aramaSuresi = $record['arama_suresi'];
    if ($aramaSuresi !== null && $aramaSuresi == 0) {
        $issues[] = "⚠️ Arama süresi 0 - Arama hiç bağlanmamış";
    }
    
    // Call date kontrolü
    $callDate = $record['call_date'];
    if ($callDate) {
        $checks[] = "✅ Call date mevcut: " . $callDate;
    } else {
        $issues[] = "❌ Call date yok - Call file oluşturulmamış olabilir";
    }
    
    // Caller ID kontrolü
    if (empty($record['caller_id'])) {
        $issues[] = "⚠️ Caller ID boş";
    }
    
    // Tarih/saat kontrolü
    $scheduledTime = strtotime($record['tarih']);
    $now = time();
    
    if ($scheduledTime > $now) {
        $issues[] = "⚠️ Zamanlanmış saat henüz gelmedi: " . $record['tarih'];
    } else {
        $diff = $now - $scheduledTime;
        $diffMinutes = floor($diff / 60);
        $checks[] = "✅ Zamanlanmış saat geçti ($diffMinutes dakika önce)";
    }
    
    // Sonuçları göster
    if (count($issues) > 0) {
        echo "<h3 class='error'>❌ BULUNAN SORUNLAR:</h3>";
        foreach ($issues as $issue) {
            echo "<div class='issue'>$issue</div>";
        }
    }
    
    if (count($checks) > 0) {
        echo "<h3 class='success'>✅ DOĞRU OLAN NOKTALAR:</h3>";
        foreach ($checks as $check) {
            echo "<div class='check'>$check</div>";
        }
    }
    
    echo "</div>";
    
    // 3. Çalışma saatleri kontrolü
    echo "<div class='box'>";
    echo "<h2>⏰ 3. ÇALIŞMA SAATLERİ KONTROLÜ</h2>";
    
    $now = new DateTime();
    $dayOfWeek = $now->format('N'); // 1=Pazartesi, 7=Pazar
    $currentTime = $now->format('H:i:s');
    
    $stmt = $pdo->prepare("SELECT * FROM calisma_saatleri WHERE gun = ?");
    $stmt->execute([$dayOfWeek]);
    $workingHours = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($workingHours) {
        $isActive = $workingHours['aktif'];
        $startTime = $workingHours['baslangic'];
        $endTime = $workingHours['bitis'];
        
        echo "<table>";
        echo "<tr><th>Gün</th><th>Aktif</th><th>Başlangıç</th><th>Bitiş</th><th>Şu An</th></tr>";
        echo "<tr>";
        
        $gunler = ['', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi', 'Pazar'];
        echo "<td><strong>" . $gunler[$dayOfWeek] . "</strong></td>";
        echo "<td>" . ($isActive ? '<span class="success">✅ Aktif</span>' : '<span class="error">❌ Pasif</span>') . "</td>";
        echo "<td>$startTime</td>";
        echo "<td>$endTime</td>";
        echo "<td class='info'><strong>$currentTime</strong></td>";
        echo "</tr>";
        echo "</table>";
        
        if (!$isActive) {
            echo "<div class='issue'>❌ SORUN: Bugün çalışma saatleri pasif!</div>";
        } elseif ($currentTime < $startTime) {
            echo "<div class='issue'>⚠️ SORUN: Henüz çalışma saati başlamadı (Başlangıç: $startTime)</div>";
        } elseif ($currentTime > $endTime) {
            echo "<div class='issue'>⚠️ SORUN: Çalışma saati sona erdi (Bitiş: $endTime)</div>";
        } else {
            echo "<div class='check'>✅ Şu an çalışma saatleri içinde</div>";
        }
    } else {
        echo "<p class='warning'>⚠️ Bu gün için çalışma saati tanımı yok</p>";
    }
    
    echo "</div>";
    
    // 4. Import detayları
    echo "<div class='box'>";
    echo "<h2>📦 4. IMPORT DETAYLARI</h2>";
    
    $stmt = $pdo->prepare("
        SELECT 
            i.*,
            p.proje_adi,
            s.sablon_adi,
            COUNT(c.id) as toplam_kayit,
            SUM(CASE WHEN c.durum = 0 THEN 1 ELSE 0 END) as bekleyen,
            SUM(CASE WHEN c.durum = 1 THEN 1 ELSE 0 END) as araniyor,
            SUM(CASE WHEN c.durum = 2 THEN 1 ELSE 0 END) as tamamlandi,
            SUM(CASE WHEN c.durum = 3 THEN 1 ELSE 0 END) as basarisiz
        FROM imports i
        LEFT JOIN projeler p ON i.proje_id = p.id
        LEFT JOIN sablonlar s ON i.sablon_id = s.id
        LEFT JOIN CALLS c ON i.id = c.import_id
        WHERE i.id = ?
        GROUP BY i.id
    ");
    $stmt->execute([$record['import_id']]);
    $import = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($import) {
        echo "<table>";
        echo "<tr><th>Alan</th><th>Değer</th></tr>";
        echo "<tr><td><strong>Import ID</strong></td><td>{$import['id']}</td></tr>";
        echo "<tr><td><strong>Proje</strong></td><td>{$import['proje_adi']}</td></tr>";
        echo "<tr><td><strong>Şablon</strong></td><td>{$import['sablon_adi']}</td></tr>";
        echo "<tr><td><strong>Dosya Adı</strong></td><td>{$import['dosya_adi']}</td></tr>";
        echo "<tr><td><strong>Durum</strong></td><td><span class='info'>{$import['durum']}</span></td></tr>";
        echo "<tr><td><strong>Aktif</strong></td><td>" . ($import['aktif'] ? '<span class="success">✅</span>' : '<span class="error">❌</span>') . "</td></tr>";
        echo "<tr><td><strong>Toplam Kayıt</strong></td><td>{$import['toplam_kayit']}</td></tr>";
        echo "<tr><td><strong>Bekleyen</strong></td><td class='warning'><strong>{$import['bekleyen']}</strong></td></tr>";
        echo "<tr><td><strong>Aranıyor</strong></td><td class='info'>{$import['araniyor']}</td></tr>";
        echo "<tr><td><strong>Tamamlandı</strong></td><td class='success'>{$import['tamamlandi']}</td></tr>";
        echo "<tr><td><strong>Başarısız</strong></td><td class='error'>{$import['basarisiz']}</td></tr>";
        echo "</table>";
    }
    
    echo "</div>";
    
    // 5. Çözüm önerileri
    echo "<div class='box'>";
    echo "<h2>💡 5. ÇÖZÜM ÖNERİLERİ</h2>";
    
    echo "<ol style='line-height: 2.5;'>";
    
    if ($record['import_aktif'] != 1) {
        echo "<li class='error'><strong>Import'u aktif hale getirin:</strong><br>";
        echo "Arama Yönetimi → Import ID {$record['import_id']} → ▶️ Başlat</li>";
    }
    
    if ($record['import_durum'] == 'stopped') {
        echo "<li class='error'><strong>Import'u yeniden başlatın:</strong><br>";
        echo "Import durdurulmuş, tekrar başlatın</li>";
    }
    
    if (!$workingHours || !$workingHours['aktif']) {
        echo "<li class='error'><strong>Çalışma saatlerini aktif edin:</strong><br>";
        echo "Çalışma Saatleri → " . ($gunler[$dayOfWeek] ?? 'Bugün') . " → Aktif yap</li>";
    }
    
    if ($currentTime < $startTime || $currentTime > $endTime) {
        echo "<li class='warning'><strong>Çalışma saati dışında:</strong><br>";
        echo "Arama çalışma saatleri içinde yapılacak ($startTime - $endTime)</li>";
    }
    
    if (empty($record['call_date'])) {
        echo "<li class='warning'><strong>Call file oluşturulmamış:</strong><br>";
        echo "Scheduler (cron job) çalışıyor mu kontrol edin:<br>";
        echo "<code>ps aux | grep scheduled_calls</code></li>";
    }
    
    echo "<li class='info'><strong>Manuel test:</strong><br>";
    echo "retry_scheduler.php scriptini çalıştırarak tüm bekleyen kayıtları işleyin</li>";
    
    echo "<li class='info'><strong>Log kontrolü:</strong><br>";
    echo "Activity Logs'da bu kayıt için işlem var mı kontrol edin</li>";
    
    echo "</ol>";
    
    echo "</div>";
    
    // 6. Hızlı işlemler
    echo "<div class='box'>";
    echo "<h2>⚡ 6. HIZLI İŞLEMLER</h2>";
    
    echo "<p>Bu kayıt için hızlı işlemler:</p>";
    
    echo "<div style='margin: 20px 0;'>";
    echo "<a href='arama_yonetimi.php?import_id={$record['import_id']}' style='display: inline-block; background: #0e639c; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; margin: 5px;'>📋 Import Sayfasına Git</a>";
    echo "<a href='retry_scheduler.php' style='display: inline-block; background: #4ec9b0; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; margin: 5px;'>🔄 Scheduler Çalıştır</a>";
    echo "<a href='activity_logs.php?search={$record['telefon']}' style='display: inline-block; background: #569cd6; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; margin: 5px;'>📊 Log Geçmişi</a>";
    echo "</div>";
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='box' style='border-left-color: #f48771;'>";
    echo "<h2 class='error'>❌ HATA!</h2>";
    echo "<p class='error'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}

echo "</div>
</body>
</html>";
?>
