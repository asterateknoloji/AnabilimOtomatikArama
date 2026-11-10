<?php
/**
 * Test ve Debug: Process Calls
 * process_calls.php scriptini test eder ve hataları gösterir
 */

// Hata raporlamayı aç
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>";
echo "<html lang='tr'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<title>Process Calls Test</title>";
echo "<style>
    body { font-family: Arial; margin: 20px; background: #f5f5f5; }
    .container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
    h1 { color: #333; border-bottom: 3px solid #667eea; padding-bottom: 10px; }
    .success { background: #d4edda; color: #155724; padding: 15px; margin: 10px 0; border-radius: 5px; }
    .error { background: #f8d7da; color: #721c24; padding: 15px; margin: 10px 0; border-radius: 5px; }
    .info { background: #d1ecf1; color: #0c5460; padding: 15px; margin: 10px 0; border-radius: 5px; }
    pre { background: #f4f4f4; padding: 15px; border-radius: 5px; overflow-x: auto; }
    .step { background: #fff3cd; padding: 10px; margin: 10px 0; border-left: 4px solid #ffc107; }
</style>";
echo "</head>";
echo "<body>";
echo "<div class='container'>";
echo "<h1>🔍 Process Calls Test & Debug</h1>";

// 1. Config yükleme testi
echo "<div class='step'><strong>1. Config.php Yükleniyor...</strong></div>";
try {
    require_once __DIR__ . '/config.php';
    echo "<div class='success'>✅ Config başarıyla yüklendi</div>";
    echo "<div class='info'>";
    echo "DB Host: " . $dbHost . "<br>";
    echo "DB Name: " . $dbName . "<br>";
    echo "PDO Bağlantı: " . ($pdo ? "✅ Aktif" : "❌ Yok") . "<br>";
    echo "</div>";
} catch (Exception $e) {
    echo "<div class='error'>❌ Config yüklenemedi: " . $e->getMessage() . "</div>";
    exit;
}

// 2. AutoCallManager yükleme testi
echo "<div class='step'><strong>2. AutoCallManager.php Yükleniyor...</strong></div>";
try {
    require_once __DIR__ . '/includes/AutoCallManager.php';
    echo "<div class='success'>✅ AutoCallManager sınıfı yüklendi</div>";
    
    // Sınıf metodlarını kontrol et
    $methods = get_class_methods('AutoCallManager');
    echo "<div class='info'>";
    echo "<strong>Mevcut Metodlar:</strong><br>";
    echo "<ul>";
    foreach ($methods as $method) {
        echo "<li>$method</li>";
    }
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ AutoCallManager yüklenemedi: " . $e->getMessage() . "</div>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    exit;
}

// 3. Manager instance oluşturma
echo "<div class='step'><strong>3. AutoCallManager Instance Oluşturuluyor...</strong></div>";
try {
    $manager = new AutoCallManager($pdo);
    echo "<div class='success'>✅ Manager instance oluşturuldu</div>";
} catch (Exception $e) {
    echo "<div class='error'>❌ Manager oluşturulamadı: " . $e->getMessage() . "</div>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    exit;
}

// 4. getStats metodu kontrolü
echo "<div class='step'><strong>4. getStats() Metodu Test Ediliyor...</strong></div>";
if (method_exists($manager, 'getStats')) {
    try {
        $stats = $manager->getStats();
        echo "<div class='success'>✅ getStats() çalıştı</div>";
        echo "<div class='info'>";
        echo "<strong>İstatistikler:</strong><br>";
        echo "<pre>" . print_r($stats, true) . "</pre>";
        echo "</div>";
    } catch (Exception $e) {
        echo "<div class='error'>❌ getStats() hatası: " . $e->getMessage() . "</div>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
} else {
    echo "<div class='error'>❌ getStats() metodu bulunamadı!</div>";
    echo "<div class='info'>Bu metodu AutoCallManager sınıfına eklememiz gerekiyor.</div>";
}

// 5. Bekleyen aramalar
echo "<div class='step'><strong>5. Bekleyen Aramalar Kontrol Ediliyor...</strong></div>";
try {
    $stmt = $pdo->query("
        SELECT COUNT(*) as total 
        FROM CALLS 
        WHERE Status = 1 AND Locked = 0 AND Aktif = 1
    ");
    $result = $stmt->fetch();
    
    echo "<div class='success'>✅ Sorgu çalıştı</div>";
    echo "<div class='info'>";
    echo "<strong>Bekleyen Arama Sayısı:</strong> {$result['total']}<br>";
    echo "</div>";
    
    // Detaylı liste
    if ($result['total'] > 0) {
        $stmt = $pdo->query("
            SELECT id, PhoneNumber, Adi, Status, Locked, CallCount 
            FROM CALLS 
            WHERE Status = 1 AND Locked = 0 AND Aktif = 1
            ORDER BY id DESC
            LIMIT 5
        ");
        $calls = $stmt->fetchAll();
        
        echo "<table border='1' cellpadding='8' style='border-collapse: collapse; width: 100%; margin-top: 10px;'>";
        echo "<tr style='background: #667eea; color: white;'>";
        echo "<th>ID</th><th>Telefon</th><th>Adı</th><th>Status</th><th>Locked</th><th>CallCount</th>";
        echo "</tr>";
        foreach ($calls as $call) {
            echo "<tr>";
            echo "<td>{$call['id']}</td>";
            echo "<td>{$call['PhoneNumber']}</td>";
            echo "<td>{$call['Adi']}</td>";
            echo "<td>{$call['Status']}</td>";
            echo "<td>{$call['Locked']}</td>";
            echo "<td>{$call['CallCount']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Sorgu hatası: " . $e->getMessage() . "</div>";
}

// 6. startPendingCalls metodu kontrolü
echo "<div class='step'><strong>6. startPendingCalls() Metodu Test Ediliyor...</strong></div>";
if (method_exists($manager, 'startPendingCalls')) {
    echo "<div class='success'>✅ startPendingCalls() metodu mevcut</div>";
    echo "<div class='info'>⚠️ Gerçek arama başlatmak için manuel olarak çalıştırın.</div>";
} else {
    echo "<div class='error'>❌ startPendingCalls() metodu bulunamadı!</div>";
    echo "<div class='info'>Bu metodu AutoCallManager sınıfına eklememiz gerekiyor.</div>";
}

// 7. processCall metodu kontrolü
echo "<div class='step'><strong>7. processCall() Metodu Test Ediliyor...</strong></div>";
if (method_exists($manager, 'processCall')) {
    echo "<div class='success'>✅ processCall() metodu mevcut</div>";
    echo "<div class='info'>Tek bir aramayı işlemek için kullanılabilir.</div>";
} else {
    echo "<div class='error'>❌ processCall() metodu bulunamadı!</div>";
}

echo "<hr style='margin: 30px 0;'>";
echo "<h2>📝 Sonuç ve Öneriler</h2>";

echo "<div class='info'>";
echo "<strong>Eksik olan metodları eklememiz gerekiyor:</strong><br>";
echo "<ul>";
echo "<li><code>getStats()</code> - İstatistikleri döndürür</li>";
echo "<li><code>startPendingCalls()</code> - Bekleyen aramaları başlatır</li>";
echo "</ul>";
echo "</div>";

echo "<div style='margin-top: 20px;'>";
echo "<a href='quick_add_call.php' style='display: inline-block; padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px;'>➕ Test Kaydı Ekle</a> ";
echo "<a href='otomatikarama.php' style='display: inline-block; padding: 10px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 5px;'>🏠 Ana Sayfa</a>";
echo "</div>";

echo "</div>";
echo "</body>";
echo "</html>";
