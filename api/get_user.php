<?php
/**
 * Kullanıcı bilgilerini getir
 */

require_once '../config.php';
require_once '../includes/Auth.php';

header('Content-Type: application/json');

$auth = new Auth($pdo);

// Login kontrolü
if (!$auth->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// İzin kontrolü
if (!$auth->hasPermission('user.view')) {
    echo json_encode(['success' => false, 'message' => 'Permission denied']);
    exit;
}

$userId = $_GET['user_id'] ?? null;

if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'User ID required']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, username, adi, soyadi, dahili, isadmin FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Kullanıcı bulunamadı']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'user' => $user
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
