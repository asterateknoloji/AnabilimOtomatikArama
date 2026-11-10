<?php
/**
 * Kullanıcının rollerini getir
 */

require_once '../config.php';
require_once '../includes/Auth.php';

header('Content-Type: application/json');

$auth = new Auth($pdo);

// Login kontrolü
if (!$auth->isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// İzin kontrolü
if (!$auth->hasPermission('user.view')) {
    echo json_encode(['error' => 'Permission denied']);
    exit;
}

$userId = $_GET['user_id'] ?? null;

if (!$userId) {
    echo json_encode(['error' => 'User ID required']);
    exit;
}

try {
    // Kullanıcının rol ID'lerini getir
    $stmt = $pdo->prepare("
        SELECT role_id 
        FROM USER_ROLES 
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    
    $roles = [];
    while ($row = $stmt->fetch()) {
        $roles[] = (int)$row['role_id'];
    }
    
    echo json_encode([
        'success' => true,
        'roles' => $roles
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
