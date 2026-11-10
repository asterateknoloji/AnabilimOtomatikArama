<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/plain; charset=utf-8');

require_once 'config.php';

echo "Config loaded...\n";
echo "DB Host: {$dbHost}, DB Name: {$dbName}\n";

// $pdo zaten config.php'den geliyor, ama yeni bir bağlantı oluşturalım
try {
    if (!isset($pdo)) {
        throw new Exception('PDO object not found in config');
    }
    
    echo "PDO connected...\n";
    
    // En son import ID'yi al
    $importId = $pdo->query('SELECT MAX(id) FROM ' . table('IMPORT_HISTORY'))->fetchColumn();
    
    echo "=== EN SON IMPORT ID: $importId ===\n\n";
    
    // Status bazlı gruplandırma
    $stmt = $pdo->prepare('
        SELECT Status, Locked, Aktif, COUNT(*) as cnt 
        FROM ' . table('CALLS') . ' 
        WHERE ImportId = ? 
        GROUP BY Status, Locked, Aktif 
        ORDER BY Status, Locked, Aktif
    ');
    $stmt->execute([$importId]);
    
    echo "=== STATUS GRUPLAMA ===\n";
    $grandTotal = 0;
    while ($r = $stmt->fetch()) {
        echo sprintf(
            "Status=%d  Locked=%d  Aktif=%d  →  Count=%d\n",
            $r['Status'],
            $r['Locked'],
            $r['Aktif'],
            $r['cnt']
        );
        $grandTotal += $r['cnt'];
    }
    echo "\nGRAND TOTAL: $grandTotal\n\n";
    
    // İstatistik hesaplama (YENİ BASİT MANTIK)
    $statsStmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN Status != 2 THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN Status = 2 THEN 1 ELSE 0 END) as completed
        FROM " . table('CALLS') . " 
        WHERE ImportId = ?
    ");
    $statsStmt->execute([$importId]);
    $st = $statsStmt->fetch();
    
    echo "=== YENİ BASİT İSTATİSTİK ===\n";
    echo "TOPLAM:    " . $st['total'] . "\n";
    echo "Aranacak:  " . ($st['pending'] ?? 0) . "  (Status != 2)\n";
    echo "Aradı:     " . ($st['completed'] ?? 0) . "  (Status = 2)\n";
    
    $sum = ($st['pending'] ?? 0) + ($st['completed'] ?? 0);
    echo "\nToplam Check: $sum\n";
    
    if ($sum != $st['total']) {
        $missing = $st['total'] - $sum;
        echo "\n⚠️ KAYIP KAYIT: $missing\n\n";
        
        // Hangi durumda kayıp var?
        $lostStmt = $pdo->prepare("
            SELECT Status, Locked, Aktif, COUNT(*) as cnt
            FROM " . table('CALLS') . "
            WHERE ImportId = ?
            AND Status NOT IN (0, 1, 2)
            GROUP BY Status, Locked, Aktif
        ");
        $lostStmt->execute([$importId]);
        
        echo "=== KAYIP KAYITLARIN DURUMU ===\n";
        while ($lost = $lostStmt->fetch()) {
            echo sprintf(
                "Status=%d  Locked=%d  Aktif=%d  →  Count=%d\n",
                $lost['Status'],
                $lost['Locked'],
                $lost['Aktif'],
                $lost['cnt']
            );
        }
    }
    
} catch (Exception $e) {
    echo "❌ Hata: " . $e->getMessage();
}
