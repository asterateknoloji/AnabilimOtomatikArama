<?php
/**
 * Import Ayarlarını Kaydet
 * Tekrar, Aralık, Hız değerlerini IMPORT_HISTORY'ye kaydeder
 */

require_once 'includes/check_auth.php';
requirePermission('import.control');

require_once 'config.php';

header('Content-Type: application/json');

try {
    if (!isset($_POST['import_id'])) {
        throw new Exception('Eksik parametre: import_id');
    }
    
    $importId = (int)$_POST['import_id'];
    $retryCount = isset($_POST['retry_count']) ? (int)$_POST['retry_count'] : null;
    $retryInterval = isset($_POST['retry_interval']) ? (int)$_POST['retry_interval'] : null;
    $maxConcurrent = isset($_POST['max_concurrent']) ? (int)$_POST['max_concurrent'] : null;
    $projectId = isset($_POST['project_id']) ? (int)$_POST['project_id'] : null;
    $templateId = isset($_POST['template_id']) ? (int)$_POST['template_id'] : null;
    
    // En az bir değer gönderilmeli
    if ($retryCount === null && $retryInterval === null && $maxConcurrent === null && $projectId === null && $templateId === null) {
        throw new Exception('Güncellenecek en az bir alan gerekli');
    }
    
    // Import var mı kontrol et
    $checkStmt = $pdo->prepare("SELECT id FROM " . table('IMPORT_HISTORY') . " WHERE id = ?");
    $checkStmt->execute([$importId]);
    if (!$checkStmt->fetch()) {
        throw new Exception('Import bulunamadı');
    }
    
    $updates = [];
    $params = [];
    
    if ($retryCount !== null) {
        $retryCount = max(1, min(10, $retryCount));
        $updates[] = "RetryCount = ?";
        $params[] = $retryCount;
    }
    if ($retryInterval !== null) {
        $retryInterval = max(5, min(1440, $retryInterval));
        $updates[] = "RetryIntervalMinutes = ?";
        $params[] = $retryInterval;
    }
    if ($maxConcurrent !== null) {
        $maxConcurrent = max(1, min(100, $maxConcurrent));
        $updates[] = "MaxConcurrentCalls = ?";
        $params[] = $maxConcurrent;
    }
    if ($projectId !== null && $projectId > 0) {
        $updates[] = "ProjectId = ?";
        $params[] = $projectId;
    }
    if ($templateId !== null && $templateId > 0) {
        $updates[] = "TemplateId = ?";
        $params[] = $templateId;
    }
    
    if (empty($updates)) {
        throw new Exception('Geçerli güncelleme yok');
    }
    
    $params[] = $importId;
    $sql = "UPDATE " . table('IMPORT_HISTORY') . " SET " . implode(", ", $updates) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    // Tekrar (RetryCount) güncellendiyse → CALLS.TryCallCount'u aranmamış (Status=1) kayıtlara uygula
    if ($retryCount !== null) {
        $applyRetryStmt = $pdo->prepare("
            UPDATE " . table('CALLS') . " 
            SET TryCallCount = ? 
            WHERE ImportId = ? AND Status = 1
        ");
        $applyRetryStmt->execute([$retryCount, $importId]);
    }
    
    // ProjectId güncellendiyse, aranmamış (Status=1) kayıtlara da uygula
    if ($projectId !== null && $projectId > 0) {
        $applyStmt = $pdo->prepare("
            UPDATE " . table('CALLS') . " 
            SET ProjectId = ? 
            WHERE ImportId = ? AND Status = 1
        ");
        $applyStmt->execute([$projectId, $importId]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Ayarlar kaydedildi!'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
