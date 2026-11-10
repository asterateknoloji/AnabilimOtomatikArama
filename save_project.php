<?php
/**
 * AJAX: Import'a proje atama
 */

require_once 'config.php';

if (isset($_POST['import_id']) && isset($_POST['project_id'])) {
    $importId = (int)$_POST['import_id'];
    $projectId = $_POST['project_id'] ? (int)$_POST['project_id'] : null;
    
    $stmt = $pdo->prepare("UPDATE " . table('IMPORT_HISTORY') . " SET ProjectId = ? WHERE id = ?");
    $success = $stmt->execute([$projectId, $importId]);
    
    if ($success) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Güncelleme başarısız']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Eksik parametre']);
}
