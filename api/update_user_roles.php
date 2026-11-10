<?php
/**
 * Kullanıcının rollerini güncelle
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
if (!$auth->hasPermission('user.assign_role')) {
    echo json_encode(['success' => false, 'message' => 'Permission denied']);
    exit;
}

$userId = $_POST['user_id'] ?? null;
$roles = $_POST['roles'] ?? [];

if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'User ID required']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Mevcut rolleri sil
    $deleteStmt = $pdo->prepare("DELETE FROM USER_ROLES WHERE user_id = ?");
    $deleteStmt->execute([$userId]);
    
    // Yeni rolleri ekle
    if (!empty($roles)) {
        $insertStmt = $pdo->prepare("
            INSERT INTO USER_ROLES (user_id, role_id, assigned_at, assigned_by) 
            VALUES (?, ?, NOW(), ?)
        ");
        
        foreach ($roles as $roleId) {
            $insertStmt->execute([$userId, $roleId, $auth->getCurrentUser()['id']]);
        }
    }
    
    $pdo->commit();
    
    // Log
    $currentUser = $auth->getCurrentUser();
    $auth->logActivity(
        $currentUser['id'],
        'update',
        'user',
        "Kullanıcı rolleri güncellendi (user_id: $userId)",
        $userId
    );
    
    echo json_encode([
        'success' => true,
        'message' => 'Roller başarıyla güncellendi'
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
