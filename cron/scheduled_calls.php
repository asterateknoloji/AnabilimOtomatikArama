<?php
/**
 * Zamanlanmış Aramaları Başlat
 * Bu script Task Scheduler/Cron ile her dakika çalıştırılmalıdır
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/ImportManager.php';
require_once __DIR__ . '/../includes/AutoCallManager.php';

$importManager = new ImportManager($pdo);
$autoCallManager = new AutoCallManager($pdo);

echo "=== Zamanlanmış Arama Servisi ===\n";
echo "Tarih: " . date('Y-m-d H:i:s') . "\n\n";

// Zamanı gelmiş importları bul
$scheduledImports = $importManager->getScheduledImports();

echo "Zamanı gelmiş import sayısı: " . count($scheduledImports) . "\n\n";

foreach ($scheduledImports as $import) {
    try {
        echo "Import ID {$import['id']} işleniyor...\n";
        
        // Import durumunu güncelle
        $importManager->updateImportStatus($import['id'], 'in_progress');
        
        // Bu import'a ait bekleyen aramaları başlat
        $stmt = $pdo->prepare("
            UPDATE " . table('CALLS') . " 
            SET Status = 1 
            WHERE ImportId = ? AND Status = 1 AND Aktif = 1
        ");
        $stmt->execute([$import['id']]);
        
        // Aramaları başlat
        $count = $autoCallManager->startPendingCalls();
        
        echo "  -> {$count} arama başlatıldı\n";
        
        // Import tamamlandı
        $importManager->updateImportStatus($import['id'], 'completed');
        
    } catch (Exception $e) {
        echo "  -> HATA: " . $e->getMessage() . "\n";
        $importManager->updateImportStatus($import['id'], 'failed');
    }
}

echo "\n=== İşlem Tamamlandı ===\n";
