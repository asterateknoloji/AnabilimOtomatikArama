<?php
/**
 * Takılı Kalmış Aramaları Temizle
 * 
 * Windows Task Scheduler ile her 1 dakikada çalıştırılacak
 * 
 * Görevleri:
 * 1. Locked=1 ve 5 dakikadan eski kayıtları bul
 * 2. Status, SipCode, EndCallTime, LastCallTime güncelle
 * 3. Cevapsız aramaları tekrar aranacak şekilde işaretle
 */

require_once __DIR__ . '/config.php';

// Log dosyası
$logFile = __DIR__ . '/logs/cleanup_' . date('Y-m-d') . '.log';
$logDir = dirname($logFile);
if (!is_dir($logDir)) {
    mkdir($logDir, 0777, true);
}

function writeLog($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[{$timestamp}] {$message}\n", FILE_APPEND);
}

writeLog("=== Cleanup Script Başladı ===");

try {
    // 1. Takılı kalmış kayıtları bul (Locked=1 ve 5 dakikadan eski)
    $stuckQuery = "
        SELECT 
            c.id,
            c.ImportId,
            c.Status,
            c.CallCount,
            c.LastCallTime,
            TIMESTAMPDIFF(MINUTE, c.LastCallTime, NOW()) as MinutesStuck,
            ih.RetryIntervalMinutes
        FROM " . table('CALLS') . " c
        LEFT JOIN " . table('IMPORT_HISTORY') . " ih ON c.ImportId = ih.id
        WHERE c.Locked = 1
        AND TIMESTAMPDIFF(MINUTE, c.LastCallTime, NOW()) >= 5
        AND c.Aktif = 1
    ";
    
    $stmt = $pdo->query($stuckQuery);
    $stuckCalls = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    writeLog("Takılı kayıt sayısı: " . count($stuckCalls));
    
    if (empty($stuckCalls)) {
        writeLog("Temizlenecek kayıt yok.");
        exit(0);
    }
    
    // 2. Her kayıt için temizlik yap
    $cleanedCount = 0;
    
    foreach ($stuckCalls as $call) {
        $callId = $call['id'];
        $retryMinutes = $call['RetryIntervalMinutes'] ?? 120;
        
        // Cevapsız/başarısız arama olarak işaretle
        // Status=1 (tekrar aranacak), SipCode=503 (Ulaşılamadı)
        // CallCount artır, CallTime'ı öteleme ile güncelle, LastCallTime'ı şimdi yap
        
        // Önce mevcut CallCount ve TryCallCount'u oku
        $checkStmt = $pdo->prepare("SELECT CallCount, TryCallCount FROM " . table('CALLS') . " WHERE id = ?");
        $checkStmt->execute([$callId]);
        $currentCall = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        $newCallCount = $currentCall['CallCount'] + 1;
        $maxReached = ($newCallCount >= $currentCall['TryCallCount']);
        $newStatus = $maxReached ? 2 : 1;
        $durumMsg = $maxReached 
            ? "Timeout ({$newCallCount}/{$currentCall['TryCallCount']}) - Max deneme" 
            : "Timeout ({$newCallCount}/{$currentCall['TryCallCount']}) - {$retryMinutes} dk sonra tekrar";
        
        $updateQuery = "
            UPDATE " . table('CALLS') . "
            SET 
                CallCount = ?,
                Status = ?,
                SipCode = 503,
                EndCallTime = NOW(),
                CallTime = DATE_ADD(NOW(), INTERVAL ? MINUTE),
                LastCallTime = NOW(),
                Locked = 0,
                Durum = ?
            WHERE id = ?
        ";
        
        $updateStmt = $pdo->prepare($updateQuery);
        $updateStmt->execute([
            $newCallCount,
            $newStatus,
            $retryMinutes,
            $durumMsg,
            $callId
        ]);
        
        if ($updateStmt->rowCount() > 0) {
            $cleanedCount++;
            writeLog("Temizlendi: CallID={$callId}, NextRetry=" . date('Y-m-d H:i', strtotime("+{$retryMinutes} minutes")));
        }
    }
    
    writeLog("Toplam {$cleanedCount} kayıt temizlendi.");
    writeLog("=== Cleanup Script Tamamlandı ===\n");
    
} catch (Exception $e) {
    writeLog("HATA: " . $e->getMessage());
    writeLog("=== Cleanup Script HATA ile sonlandı ===\n");
    exit(1);
}
