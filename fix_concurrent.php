<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/plain; charset=utf-8');

require_once 'config.php';

try {
    $importId = $pdo->query('SELECT MAX(id) FROM ' . table('IMPORT_HISTORY'))->fetchColumn();
    
    echo "=== Import ID: $importId ===\n\n";
    
    // MaxConcurrentCalls'u 120'ye yükselt
    $stmt = $pdo->prepare('
        UPDATE ' . table('IMPORT_HISTORY') . '
        SET MaxConcurrentCalls = 120
        WHERE id = ?
    ');
    $stmt->execute([$importId]);
    
    echo "✅ MaxConcurrentCalls güncellendi: 1 → 120\n\n";
    echo "Artık START'a basınca tüm aramalar aynı anda başlayacak!\n";
    
} catch (Exception $e) {
    echo "❌ Hata: " . $e->getMessage() . "\n";
}
