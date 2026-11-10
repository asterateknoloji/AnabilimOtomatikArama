<?php
session_start();

// Login kontrolü
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    exit;
}

require_once 'config.php';

if (isset($_POST['import_id']) && isset($_POST['template_id'])) {
    $importId = (int)$_POST['import_id'];
    $templateId = (int)$_POST['template_id'];
    
    try {
        $stmt = $pdo->prepare("
            UPDATE " . table('IMPORT_HISTORY') . " 
            SET TemplateId = ? 
            WHERE id = ?
        ");
        $stmt->execute([$templateId, $importId]);
        
        http_response_code(200);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing parameters']);
}
