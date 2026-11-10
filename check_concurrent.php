<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/plain; charset=utf-8');

require_once 'config.php';

try {
    $importId = $pdo->query('SELECT MAX(id) FROM ' . table('IMPORT_HISTORY'))->fetchColumn();
    
    echo "=== EN SON IMPORT ID: $importId ===\n\n";
    
    // Import ayarlarını göster
    $stmt = $pdo->prepare('
        SELECT MaxConcurrentCalls, RetryIntervalMinutes, RetryCount, OriginalFileName
        FROM ' . table('IMPORT_HISTORY') . '
        WHERE id = ?
    ');
    $stmt->execute([$importId]);
    $import = $stmt->fetch();
    
    echo "=== IMPORT AYARLARI ===\n";
    echo "Dosya: " . $import['OriginalFileName'] . "\n";
    echo "MaxConcurrentCalls: " . $import['MaxConcurrentCalls'] . " (eşzamanlı arama limiti)\n";
    echo "RetryIntervalMinutes: " . $import['RetryIntervalMinutes'] . " dakika\n";
    echo "RetryCount: " . $import['RetryCount'] . " deneme\n\n";
    
    // Aktif aramaları say
    $activeStmt = $pdo->prepare('
        SELECT COUNT(*) as active_count
        FROM ' . table('CALLS') . '
        WHERE ImportId = ? AND Locked = 1
    ');
    $activeStmt->execute([$importId]);
    $active = $activeStmt->fetch();
    
    echo "=== AKTİF ARAMALAR ===\n";
    echo "Şu anda Locked=1: " . $active['active_count'] . "\n";
    echo "Limit: " . $import['MaxConcurrentCalls'] . "\n";
    echo "Müsait slot: " . ($import['MaxConcurrentCalls'] - $active['active_count']) . "\n\n";
    
    // Aranacak kayıtları say
    $readyStmt = $pdo->prepare('
        SELECT COUNT(*) as ready_count
        FROM ' . table('CALLS') . '
        WHERE ImportId = ?
        AND Status IN (0, 1)
        AND Aktif = 1
        AND Locked = 0
        AND (CallTime IS NULL OR CallTime <= NOW())
    ');
    $readyStmt->execute([$importId]);
    $ready = $readyStmt->fetch();
    
    echo "=== ARANACAK KAYITLAR ===\n";
    echo "Hazır kayıt sayısı: " . $ready['ready_count'] . "\n";
    echo "(Status=0/1, Aktif=1, Locked=0, CallTime<=NOW)\n";
    
} catch (Exception $e) {
    echo "❌ Hata: " . $e->getMessage() . "\n";
}
