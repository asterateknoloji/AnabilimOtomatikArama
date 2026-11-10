<?php
/**
 * Kullanıcı Silme API
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
if (!$auth->hasPermission('user.delete')) {
    echo json_encode(['success' => false, 'message' => 'Bu işlem için yetkiniz yok']);
    exit;
}

$userId = $_POST['user_id'] ?? null;

// Validasyon
if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'Kullanıcı ID gerekli']);
    exit;
}

// Kendi kendini silme kontrolü
$currentUser = $auth->getCurrentUser();
if ($userId == $currentUser['id']) {
    echo json_encode(['success' => false, 'message' => 'Kendi hesabınızı silemezsiniz']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Kullanıcı bilgilerini al (log için)
    $userStmt = $pdo->prepare("SELECT username, adi, soyadi FROM users WHERE id = ?");
    $userStmt->execute([$userId]);
    $user = $userStmt->fetch();
    
    if (!$user) {
        throw new Exception('Kullanıcı bulunamadı');
    }
    
    // 1. USER_ROLES tablosundan rolleri sil
    $deleteRolesStmt = $pdo->prepare("DELETE FROM USER_ROLES WHERE user_id = ?");
    $deleteRolesStmt->execute([$userId]);
    
    // 2. ACTIVITY_LOG tablosundan kullanıcının loglarını sil (opsiyonel - saklayabilirsiniz)
    // $deleteLogsStmt = $pdo->prepare("DELETE FROM ACTIVITY_LOG WHERE user_id = ?");
    // $deleteLogsStmt->execute([$userId]);
    
    // 3. users tablosundan kullanıcıyı sil
    $deleteUserStmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $deleteUserStmt->execute([$userId]);
    
    $pdo->commit();
    
    // Log
    $userName = $user['username'] . ' (' . $user['adi'] . ' ' . $user['soyadi'] . ')';
    $auth->logActivity(
        $currentUser['id'],
        'delete',
        'user',
        "Kullanıcı silindi: {$userName}",
        $userId
    );
    
    echo json_encode([
        'success' => true,
        'message' => 'Kullanıcı başarıyla silindi'
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
