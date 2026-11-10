<?php
/**
 * AJAX Endpoint - Import istatistiklerini döndür
 */
require_once 'config.php';
require_once 'includes/ImportManager.php';

header('Content-Type: application/json');

try {
    $importManager = new ImportManager($pdo);
    $imports = $importManager->getImportHistory();
    
    $result = [];
    
    foreach ($imports as $import) {
        $stats = $importManager->getImportCallStats($import['id']);
        
        // Aktif durumu
        $activeStmt = $pdo->prepare("SELECT COUNT(*) as active_count FROM " . table('CALLS') . " WHERE ImportId = ? AND Aktif = 1");
        $activeStmt->execute([$import['id']]);
        $activeData = $activeStmt->fetch();
        $isActive = ($activeData['active_count'] > 0);
        
        // Yüzde hesapla
        $percentage = 0;
        if ($stats['total'] > 0) {
            $percentage = round(($stats['completed'] / $stats['total']) * 100);
        }
        
        $result[] = [
            'id' => $import['id'],
            'stats' => $stats,
            'percentage' => $percentage,
            'isActive' => $isActive
        ];
    }
    
    echo json_encode(['success' => true, 'imports' => $result]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
