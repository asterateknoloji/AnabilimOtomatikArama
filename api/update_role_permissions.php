<?php
/**
 * Rol izinlerini güncelle
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

// İzin kontrolü (sadece superadmin yapabilir)
if (!$auth->hasPermission('config.edit')) {
    echo json_encode(['success' => false, 'message' => 'Permission denied']);
    exit;
}

$roleId = $_POST['role_id'] ?? null;
$permissions = $_POST['permissions'] ?? [];

if (!$roleId) {
    echo json_encode(['success' => false, 'message' => 'Role ID required']);
    exit;
}

try {
    // Superadmin rolü değiştirilemez
    $checkStmt = $pdo->prepare("SELECT role_code FROM ROLES WHERE id = ?");
    $checkStmt->execute([$roleId]);
    $role = $checkStmt->fetch();
    
    if ($role && $role['role_code'] === 'superadmin') {
        echo json_encode(['success' => false, 'message' => 'Superadmin rolü değiştirilemez']);
        exit;
    }
    
    $pdo->beginTransaction();
    
    // Mevcut izinleri sil
    $deleteStmt = $pdo->prepare("DELETE FROM ROLE_PERMISSIONS WHERE role_id = ?");
    $deleteStmt->execute([$roleId]);
    
    // Yeni izinleri ekle
    if (!empty($permissions)) {
        $insertStmt = $pdo->prepare("
            INSERT INTO ROLE_PERMISSIONS (role_id, permission_id, granted_at) 
            VALUES (?, ?, NOW())
        ");
        
        foreach ($permissions as $permId) {
            $insertStmt->execute([$roleId, $permId]);
        }
    }
    
    $pdo->commit();
    
    // Log
    $currentUser = $auth->getCurrentUser();
    $auth->logActivity(
        $currentUser['id'],
        'update',
        'role',
        "Rol izinleri güncellendi (role_id: $roleId, " . count($permissions) . " izin)",
        $roleId
    );
    
    echo json_encode([
        'success' => true,
        'message' => 'İzinler başarıyla güncellendi',
        'permission_count' => count($permissions)
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
