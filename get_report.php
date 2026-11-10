<?php
header('Content-Type: application/json; charset=utf-8');

require_once 'config.php';

try {
    if (!isset($_GET['import_id'])) {
        throw new Exception('Import ID gerekli');
    }
    
    $importId = (int)$_GET['import_id'];
    
    // Özet istatistikler
    $summaryStmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN Status = 2 THEN 1 ELSE 0 END) as answered,
            SUM(CASE WHEN Locked = 1 THEN 1 ELSE 0 END) as calling,
            SUM(CASE WHEN Status = 1 AND CallCount = 0 THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN Status = 1 AND CallCount > 0 THEN 1 ELSE 0 END) as retry_waiting
        FROM " . table('CALLS') . "
        WHERE ImportId = ?
    ");
    
    $summaryStmt->execute([$importId]);
    $summary = $summaryStmt->fetch(PDO::FETCH_ASSOC);
    
    // Detaylı kayıtlar
    $detailsStmt = $pdo->prepare("
        SELECT 
            PhoneNumber,
            Adi,
            CallCount,
            TryCallCount,
            Status,
            SipCode,
            DATE_FORMAT(CallTime, '%d.%m.%Y %H:%i') as CallTime,
            DATE_FORMAT(LastCallTime, '%d.%m.%Y %H:%i') as LastCallTime,
            tus1,
            tus2,
            Durum
        FROM " . table('CALLS') . "
        WHERE ImportId = ?
        ORDER BY 
            CASE 
                WHEN Locked = 1 THEN 1
                WHEN Status = 1 THEN 2
                WHEN SipCode = 200 THEN 3
                ELSE 4
            END,
            LastCallTime DESC,
            id DESC
        LIMIT 500
    ");
    
    $detailsStmt->execute([$importId]);
    $details = $detailsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'summary' => $summary,
        'details' => $details
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
