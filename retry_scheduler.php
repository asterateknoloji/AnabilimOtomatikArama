<?php
/**
 * Otomatik Tekrar Arama Scheduler
 * 
 * Bu script belirli aralıklarla çalışarak:
 * 1. Başarısız/ulaşılamayan aramaları bulur
 * 2. Belirlenen süre geçtiyse tekrar başlatır
 * 3. Max tekrar sayısına ulaşıldıysa artık aramaz
 * 
 * Kullanım: Windows Task Scheduler ile her 30 dakikada bir çalıştırın
 * Komut: php d:\xampp\htdocs\OtomatikArama\retry_scheduler.php
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/AutoCallManager.php';
require_once __DIR__ . '/includes/WindowsTTS.php';
require_once __DIR__ . '/includes/AsteriskCallFile.php';
require_once __DIR__ . '/includes/BatchWorkerLauncher.php';

echo "===========================================\n";
echo "Otomatik Tekrar Arama Scheduler\n";
echo "Çalışma Zamanı: " . date('Y-m-d H:i:s') . "\n";
echo "===========================================\n\n";

/**
 * Şu an çalışma saatleri içinde mi kontrol et
 */
function isWorkingHours($pdo) {
    // Şu anki gün ve saat
    $currentDayOfWeek = date('N'); // 1=Pazartesi, 7=Pazar
    $currentTime = date('H:i:s');
    $currentDate = date('Y-m-d');
    
    // ZAMANLAR tablosundan bugünün çalışma saatlerini çek
    $stmt = $pdo->prepare("
        SELECT BaslamaSaati, BitisSaati 
        FROM " . table('ZAMANLAR') . " 
        WHERE Gun = ?
        ORDER BY id
    ");
    $stmt->execute([$currentDayOfWeek]);
    $workingHours = $stmt->fetchAll();
    
    if (empty($workingHours)) {
        echo "⚠️ UYARI: Bugün için ({$currentDayOfWeek}) çalışma saati tanımlı değil!\n";
        echo "   ZAMANLAR tablosuna kayıt ekleyin.\n";
        return false;
    }
    
    // Her bir çalışma saati aralığını kontrol et
    foreach ($workingHours as $hours) {
        // datetime'dan sadece saat kısmını al
        $startTime = date('H:i:s', strtotime($hours['BaslamaSaati']));
        $endTime = date('H:i:s', strtotime($hours['BitisSaati']));
        
        // Şu an bu aralıkta mı?
        if ($currentTime >= $startTime && $currentTime <= $endTime) {
            echo "✅ Çalışma saatleri içinde: {$startTime} - {$endTime}\n\n";
            return true;
        }
    }
    
    echo "⏸️ DURDURULDU: Çalışma saatleri dışında!\n";
    echo "   Şu an: {$currentTime}\n";
    echo "   Çalışma saatleri:\n";
    foreach ($workingHours as $hours) {
        $startTime = date('H:i:s', strtotime($hours['BaslamaSaati']));
        $endTime = date('H:i:s', strtotime($hours['BitisSaati']));
        echo "   - {$startTime} ile {$endTime} arası\n";
    }
    echo "\n";
    
    return false;
}

try {
    // Önce çalışma saatlerini kontrol et
    if (!isWorkingHours($pdo)) {
        echo "❌ Çalışma saatleri dışında olduğu için aramalar başlatılmadı.\n";
        echo "   Bir sonraki çalışma saatinde tekrar çalışacak.\n";
        exit(0);
    }
    
    $autoCallManager = new AutoCallManager($pdo);
    
    // Aramalar bitmiş import'larda Aktif=0 yap (mevcut verileri düzelt)
    $deactivateStmt = $pdo->query("
        SELECT ImportId FROM " . table('CALLS') . " 
        WHERE ImportId IS NOT NULL AND Aktif = 1 
        GROUP BY ImportId
        HAVING SUM(CASE WHEN Status != 2 THEN 1 ELSE 0 END) = 0
    ");
    $deactivated = 0;
    while ($row = $deactivateStmt->fetch()) {
        $upd = $pdo->prepare("UPDATE " . table('CALLS') . " SET Aktif = 0 WHERE ImportId = ?");
        $upd->execute([$row['ImportId']]);
        $deactivated += $upd->rowCount();
    }
    if ($deactivated > 0) {
        echo "✅ Aramalar bitmiş {$deactivated} kayıt Pasif yapıldı (Aktif=0).\n";
    }
    
    // Aktif ve tamamlanmamış her import için parti işçisini ayakta tut.
    // Aynı import için flock kilidi ikinci işçinin çalışmasını engeller.
    $activeImports = $pdo->query("
        SELECT DISTINCT ImportId
        FROM " . table('CALLS') . "
        WHERE ImportId IS NOT NULL
          AND Aktif = 1
          AND Status IN (0, 1)
    ")->fetchAll(PDO::FETCH_COLUMN);

    $launched = 0;
    foreach ($activeImports as $importId) {
        if (launchBatchCallWorker((int)$importId)) {
            $launched++;
        }
    }

    echo "Aktif import sayısı: " . count($activeImports) . "\n";
    echo "İşçi başlatma isteği: {$launched}\n";
    
} catch (Exception $e) {
    echo "❌ HATA: " . $e->getMessage() . "\n";
    exit(1);
}
