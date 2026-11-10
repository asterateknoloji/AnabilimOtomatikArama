<?php
/**
 * Otomatik Unlock Script
 * 
 * Locked=1 durumunda bekleyen kayıtları unlock eder
 * Bu script Windows Task Scheduler ile her 1 dakikada bir çalıştırılmalı
 * 
 * Windows Task Scheduler:
 * d:\xampp\php\php.exe d:\xampp\htdocs\WindowsOtomatikAramason97\unlock_stuck_calls.php
 */

require_once __DIR__ . '/config.php';

$timeoutMinutes = 0.5; // 30 saniyeden uzun Locked=1 olanları unlock et (çağrı reddedildi/timeout)
$now = date('Y-m-d H:i:s');

// Takılı kayıtları bul
$stmt = $pdo->prepare("
    SELECT 
        c.id,
        c.PhoneNumber,
        c.Adi,
        c.CallCount,
        c.TryCallCount,
        c.LastCallTime,
        c.RecordDate,
        i.RetryIntervalMinutes,
        TIMESTAMPDIFF(SECOND, 
            COALESCE(c.LastCallTime, c.RecordDate), 
            NOW()
        ) as StuckSeconds
    FROM " . table('CALLS') . " c
    LEFT JOIN " . table('IMPORT_HISTORY') . " i ON c.ImportId = i.id
    WHERE c.Locked = 1
    AND TIMESTAMPDIFF(SECOND, 
        COALESCE(c.LastCallTime, c.RecordDate), 
        NOW()
    ) >= ?
    ORDER BY c.id
");

$stmt->execute([($timeoutMinutes * 60)]);
$stuckCalls = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($stuckCalls) == 0) {
    // Sessizce çık (log yazmaya gerek yok)
    exit(0);
}

echo "=== UNLOCK STUCK CALLS ===\n";
echo "Tarih: " . date('Y-m-d H:i:s') . "\n";
echo "Stuck call sayısı: " . count($stuckCalls) . "\n\n";

$unlocked = 0;
$finished = 0;

foreach ($stuckCalls as $call) {
    $retryInterval = $call['RetryIntervalMinutes'] ?? 60;
    $newCallCount = $call['CallCount'] + 1;
    $remainingTries = $call['TryCallCount'] - $newCallCount;
    
    echo "ID:{$call['id']} - {$call['Adi']} ({$call['PhoneNumber']}) - {$call['StuckSeconds']}s stuck\n";
    
    // Kalan deneme var mı?
    if ($newCallCount < $call['TryCallCount']) {
        // Hala deneme hakkı var, tekrar aranacak
        $nextCallTime = date('Y-m-d H:i:s', strtotime("+{$retryInterval} minutes"));
        
        $updateStmt = $pdo->prepare("
            UPDATE " . table('CALLS') . " 
            SET 
                Locked = 0,
                CallCount = CallCount + 1,
                LastCallTime = NOW(),
                CallTime = DATE_ADD(NOW(), INTERVAL ? MINUTE),
                SipCode = 503,
                Durum = CONCAT('[Unlock: ', ?, ' dk, ', ?, '/', ?, ']')
            WHERE id = ?
        ");
        
        $updateStmt->execute([
            $retryInterval,
            round($timeoutMinutes * 60 / 60, 1),
            $newCallCount,
            $call['TryCallCount'],
            $call['id']
        ]);
        
        echo "  ✅ Unlocked - Sonraki arama: {$nextCallTime} ({$remainingTries} deneme kaldı)\n";
        $unlocked++;
        
    } else {
        // Deneme hakkı bitti
        $updateStmt = $pdo->prepare("
            UPDATE " . table('CALLS') . " 
            SET 
                Locked = 0,
                Status = 2,
                CallCount = CallCount + 1,
                LastCallTime = NOW(),
                CallTime = NOW(),
                SipCode = 503,
                Durum = CONCAT('[Timeout: Deneme tamamlandı (', ?, '/', ?, ')]')
            WHERE id = ?
        ");
        
        $updateStmt->execute([
            $call['TryCallCount'],
            $call['TryCallCount'],
            $call['id']
        ]);
        
        echo "  ⏹️ Tamamlandı - Deneme hakkı bitti\n";
        $finished++;
    }
}

echo "\n";
echo "Özet:\n";
echo "  - Unlocked (tekrar aranacak): {$unlocked}\n";
echo "  - Tamamlandı (deneme bitti): {$finished}\n";
echo "  - Toplam: " . ($unlocked + $finished) . "\n";
echo "=========================\n";
