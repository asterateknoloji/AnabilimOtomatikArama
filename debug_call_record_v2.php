<?php
/**
 * ARAMA KAYDI DEBUG ARACI v2
 * CALLS tablosu için özel
 */

header('Content-Type: text/html; charset=utf-8');

require_once __DIR__ . '/config.php';

$recordId = isset($_GET['id']) ? (int)$_GET['id'] : 26759;

echo "<!DOCTYPE html>
<html lang='tr'>
<head>
    <meta charset='UTF-8'>
    <title>Arama Kaydı Debug v2</title>
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
        .status-badge { padding: 5px 10px; border-radius: 5px; font-weight: bold; display: inline-block; font-size: 13px; }
        .status-0 { background: #dcdcaa; color: #1e1e1e; }
        .status-1 { background: #569cd6; color: white; }
        .status-2 { background: #4ec9b0; color: white; }
        .status-3 { background: #f48771; color: white; }
        .issue { background: #f48771; color: white; padding: 15px 20px; border-radius: 8px; margin: 10px 0; font-size: 15px; }
        .check { background: #4ec9b0; color: white; padding: 15px 20px; border-radius: 8px; margin: 10px 0; font-size: 15px; }
        .fix-btn { display: inline-block; background: #f48771; color: white; padding: 12px 25px; text-decoration: none; border-radius: 8px; margin: 10px 5px; font-weight: bold; }
        .fix-btn:hover { background: #ff6b5a; }
    </style>
</head>
<body>
<div class='container'>";

echo "<h1>🔍 ARAMA KAYDI DEBUG ARACI v2</h1>";
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
            p.ProjectName as proje_adi,
            mt.TemplateName as sablon_adi
        FROM CALLS c
        LEFT JOIN PROJECTS p ON c.ProjectId = p.id
        LEFT JOIN MESSAGE_TEMPLATES mt ON p.TemplateId = mt.id
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
    echo "<tr><th style='width: 30%;'>Alan</th><th>Değer</th></tr>";
    
    $importantFields = [
        'id' => 'ID',
        'PhoneNumber' => 'Telefon',
        'Status' => 'Durum',
        'CallCount' => 'Arama Sayısı',
        'TryCallCount' => 'Deneme Sayısı',
        'Locked' => 'Kilitli',
        'Aktif' => 'Aktif',
        'CallTime' => 'Zamanlanmış Saat',
        'LastCallTime' => 'Son Arama Zamanı',
        'PickupTime' => 'Cevap Verme Zamanı',
        'EndCallTime' => 'Arama Bitiş Zamanı',
        'RecordDate' => 'Kayıt Tarihi',
        'ProjectId' => 'Proje ID',
        'ImportId' => 'Import ID',
        'proje_adi' => 'Proje Adı',
        'sablon_adi' => 'Şablon Adı',
        'Adi' => 'Adı',
        'Aramaadi' => 'Arama Adı',
        'Tutar' => 'Tutar',
        'Durum' => 'Durum Metni'
    ];
    
    foreach ($importantFields as $key => $label) {
        if (!isset($record[$key])) continue;
        
        $value = $record[$key];
        $displayValue = $value ?? '<span class="warning">NULL</span>';
        
        // Status değerini renklendir
        if ($key === 'Status') {
            $statusMap = [
                0 => '<span class="status-badge status-0">0 - Bekliyor</span>',
                1 => '<span class="status-badge status-1">1 - Aranıyor</span>',
                2 => '<span class="status-badge status-2">2 - Tamamlandı</span>',
                3 => '<span class="status-badge status-3">3 - Başarısız</span>'
            ];
            $displayValue = $statusMap[$value] ?? $value;
        }
        
        // Locked durumu
        if ($key === 'Locked') {
            $displayValue = $value == 1 ? 
                '<span class="error">🔒 KİLİTLİ (1)</span>' : 
                '<span class="success">🔓 Serbest (0)</span>';
        }
        
        // Aktif durumu
        if ($key === 'Aktif') {
            $displayValue = $value == 1 ? 
                '<span class="success">✅ Aktif (1)</span>' : 
                '<span class="error">❌ Pasif (0)</span>';
        }
        
        echo "<tr>";
        echo "<td><strong>$label</strong></td>";
        echo "<td>$displayValue</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "</div>";
    
    // 2. SORUN ANALİZİ
    echo "<div class='box'>";
    echo "<h2>🔍 2. SORUN ANALİZİ</h2>";
    
    $issues = [];
    $checks = [];
    $criticalIssues = [];
    
    // KRİTİK: Locked kontrolü
    if ($record['Locked'] == 1) {
        $criticalIssues[] = "🔒 <strong>KRİTİK:</strong> Kayıt KİLİTLİ! Bu yüzden arama yapılamıyor.";
    } else {
        $checks[] = "✅ Kayıt kilitli değil";
    }
    
    // Status kontrolü
    if ($record['Status'] == 0) {
        $checks[] = "✅ Kayıt durumu 0 (Bekliyor)";
    } elseif ($record['Status'] == 1) {
        if ($record['CallCount'] == 0) {
            $issues[] = "⚠️ Status=1 (Aranıyor) ama CallCount=0 - Arama başlamış ama tamamlanmamış";
        }
        if ($record['LastCallTime'] === null) {
            $issues[] = "⚠️ Status=1 ama LastCallTime NULL - Gerçekten arama yapılmamış";
        }
    } elseif ($record['Status'] == 2) {
        $checks[] = "✅ Arama tamamlandı";
    } elseif ($record['Status'] == 3) {
        $issues[] = "❌ Arama başarısız oldu";
    }
    
    // Aktif kontrolü
    if ($record['Aktif'] != 1) {
        $issues[] = "❌ Kayıt pasif (Aktif = 0)";
    } else {
        $checks[] = "✅ Kayıt aktif";
    }
    
    // TryCallCount kontrolü
    if ($record['TryCallCount'] > 0 && $record['CallCount'] == 0) {
        $issues[] = "⚠️ {$record['TryCallCount']} kez denenmiş ama hiç başarılı arama yok";
    }
    
    // Tarih kontrolü
    $scheduledTime = strtotime($record['CallTime']);
    $now = time();
    
    if ($scheduledTime > $now) {
        $issues[] = "⚠️ Zamanlanmış saat henüz gelmedi: " . $record['CallTime'];
    } else {
        $diff = $now - $scheduledTime;
        $diffMinutes = floor($diff / 60);
        $checks[] = "✅ Zamanlanmış saat geçti ($diffMinutes dakika önce)";
    }
    
    // Telefon kontrolü
    if (empty($record['PhoneNumber'])) {
        $criticalIssues[] = "❌ <strong>KRİTİK:</strong> Telefon numarası YOK!";
    } elseif (strlen($record['PhoneNumber']) < 10) {
        $issues[] = "⚠️ Telefon numarası çok kısa: " . $record['PhoneNumber'];
    } else {
        $checks[] = "✅ Telefon numarası mevcut";
    }
    
    // Sonuçları göster
    if (count($criticalIssues) > 0) {
        echo "<h3 class='error'>🚨 KRİTİK SORUNLAR:</h3>";
        foreach ($criticalIssues as $issue) {
            echo "<div class='issue'>$issue</div>";
        }
    }
    
    if (count($issues) > 0) {
        echo "<h3 class='warning'>⚠️ BULUNAN SORUNLAR:</h3>";
        foreach ($issues as $issue) {
            echo "<div class='issue' style='background: #dcdcaa; color: #1e1e1e;'>$issue</div>";
        }
    }
    
    if (count($checks) > 0) {
        echo "<h3 class='success'>✅ DOĞRU OLAN NOKTALAR:</h3>";
        foreach ($checks as $check) {
            echo "<div class='check'>$check</div>";
        }
    }
    
    echo "</div>";
    
    // 3. ÇALIŞMA SAATLERİ
    echo "<div class='box'>";
    echo "<h2>⏰ 3. ÇALIŞMA SAATLERİ KONTROLÜ</h2>";
    
    $now = new DateTime();
    $dayOfWeek = $now->format('N');
    $currentTime = $now->format('H:i:s');
    
    $stmt = $pdo->prepare("SELECT * FROM ZAMANLAR WHERE gun = ?");
    $stmt->execute([$dayOfWeek]);
    $workingHours = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($workingHours) {
        $isActive = $workingHours['aktif'];
        $startTime = $workingHours['baslangic'];
        $endTime = $workingHours['bitis'];
        
        $gunler = ['', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi', 'Pazar'];
        
        echo "<table>";
        echo "<tr><th>Gün</th><th>Aktif</th><th>Başlangıç</th><th>Bitiş</th><th>Şu An</th><th>Durum</th></tr>";
        echo "<tr>";
        echo "<td><strong>" . $gunler[$dayOfWeek] . "</strong></td>";
        echo "<td>" . ($isActive ? '<span class="success">✅</span>' : '<span class="error">❌</span>') . "</td>";
        echo "<td>$startTime</td>";
        echo "<td>$endTime</td>";
        echo "<td class='info'><strong>$currentTime</strong></td>";
        
        if (!$isActive) {
            echo "<td class='error'>Pasif</td>";
        } elseif ($currentTime < $startTime) {
            echo "<td class='warning'>Henüz başlamadı</td>";
        } elseif ($currentTime > $endTime) {
            echo "<td class='warning'>Sona erdi</td>";
        } else {
            echo "<td class='success'>✅ İçinde</td>";
        }
        
        echo "</tr>";
        echo "</table>";
    } else {
        echo "<p class='warning'>⚠️ Bu gün için çalışma saati tanımı yok</p>";
    }
    
    echo "</div>";
    
    // 4. HIZLI ÇÖZÜM
    if ($record['Locked'] == 1) {
        echo "<div class='box' style='border-left-color: #f48771;'>";
        echo "<h2>⚡ 4. HIZLI ÇÖZÜM</h2>";
        
        echo "<p class='error' style='font-size: 18px;'><strong>ANA SORUN: Kayıt kilitli!</strong></p>";
        echo "<p>Bu kayıt kilitli olduğu için arama yapılamıyor.</p>";
        
        echo "<h3>Çözüm Seçenekleri:</h3>";
        
        echo "<p><strong>Seçenek 1: Manuel Kilidi Kaldır (SQL)</strong></p>";
        echo "<pre>UPDATE CALLS SET Locked = 0, Status = 0 WHERE id = $recordId;</pre>";
        
        echo "<p><strong>Seçenek 2: Tüm Kilitli Kayıtları Temizle</strong></p>";
        echo "<pre>UPDATE CALLS SET Locked = 0 WHERE Locked = 1 AND Status = 1 AND CallCount = 0;</pre>";
        
        echo "<p><a href='fix_locked_calls.php?id=$recordId' class='fix-btn'>🔓 Bu Kaydın Kilidini Kaldır</a></p>";
        echo "<p><a href='fix_locked_calls.php?all=1' class='fix-btn'>🔓 Tüm Kilitli Kayıtları Düzelt</a></p>";
        
        echo "</div>";
    }
    
    // 5. İSTATİSTİKLER
    echo "<div class='box'>";
    echo "<h2>📊 5. GENEL İSTATİSTİKLER</h2>";
    
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as toplam,
            SUM(CASE WHEN Status = 0 THEN 1 ELSE 0 END) as bekleyen,
            SUM(CASE WHEN Status = 1 THEN 1 ELSE 0 END) as araniyor,
            SUM(CASE WHEN Status = 2 THEN 1 ELSE 0 END) as tamamlandi,
            SUM(CASE WHEN Status = 3 THEN 1 ELSE 0 END) as basarisiz,
            SUM(CASE WHEN Locked = 1 THEN 1 ELSE 0 END) as kilitli,
            SUM(CASE WHEN Aktif = 1 THEN 1 ELSE 0 END) as aktif
        FROM CALLS
    ");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<table>";
    echo "<tr><th>Durum</th><th>Sayı</th><th>Yüzde</th></tr>";
    echo "<tr><td>Toplam Kayıt</td><td>{$stats['toplam']}</td><td>100%</td></tr>";
    echo "<tr><td>Bekleyen (0)</td><td class='warning'><strong>{$stats['bekleyen']}</strong></td><td>" . number_format($stats['bekleyen'] / $stats['toplam'] * 100, 1) . "%</td></tr>";
    echo "<tr><td>Aranıyor (1)</td><td class='info'>{$stats['araniyor']}</td><td>" . number_format($stats['araniyor'] / $stats['toplam'] * 100, 1) . "%</td></tr>";
    echo "<tr><td>Tamamlandı (2)</td><td class='success'>{$stats['tamamlandi']}</td><td>" . number_format($stats['tamamlandi'] / $stats['toplam'] * 100, 1) . "%</td></tr>";
    echo "<tr><td>Başarısız (3)</td><td class='error'>{$stats['basarisiz']}</td><td>" . number_format($stats['basarisiz'] / $stats['toplam'] * 100, 1) . "%</td></tr>";
    echo "<tr><td>🔒 Kilitli</td><td class='error'><strong>{$stats['kilitli']}</strong></td><td>" . number_format($stats['kilitli'] / $stats['toplam'] * 100, 1) . "%</td></tr>";
    echo "<tr><td>✅ Aktif</td><td>{$stats['aktif']}</td><td>" . number_format($stats['aktif'] / $stats['toplam'] * 100, 1) . "%</td></tr>";
    echo "</table>";
    
    if ($stats['kilitli'] > 0) {
        echo "<div class='issue'>";
        echo "⚠️ <strong>{$stats['kilitli']} kayıt kilitli!</strong> Bu kayıtlar aranmayacak.";
        echo "</div>";
    }
    
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
