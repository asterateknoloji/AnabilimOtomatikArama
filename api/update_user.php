<?php
/**
 * Kullanıcı bilgilerini güncelle
 */

require_once '../config.php';
require_once '../includes/Auth.php';
require_once '../includes/Encryption.php';

header('Content-Type: application/json');

$auth = new Auth($pdo);

// Login kontrolü
if (!$auth->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// İzin kontrolü
if (!$auth->hasPermission('user.edit')) {
    echo json_encode(['success' => false, 'message' => 'Permission denied']);
    exit;
}

$userId = $_POST['user_id'] ?? null;
$password = $_POST['password'] ?? '';
$adi = $_POST['adi'] ?? '';
$soyadi = $_POST['soyadi'] ?? '';
$dahili = $_POST['dahili'] ?? null;
$roles = $_POST['roles'] ?? [];

// Validasyon
if (!$userId || empty($adi) || empty($soyadi)) {
    echo json_encode(['success' => false, 'message' => 'Tüm zorunlu alanları doldurun']);
    exit;
}

if (empty($roles)) {
    echo json_encode(['success' => false, 'message' => 'En az bir rol seçmelisiniz']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Şifre güncellenecek mi?
    if (!empty($password)) {
        $encryption = new Encryption();
        $hashedPassword = $encryption->hashPassword($password);
        
        $stmt = $pdo->prepare("
            UPDATE users 
            SET adi = ?, soyadi = ?, dahili = ?, password = ?
            WHERE id = ?
        ");
        $stmt->execute([$adi, $soyadi, $dahili, $hashedPassword, $userId]);
    } else {
        $stmt = $pdo->prepare("
            UPDATE users 
            SET adi = ?, soyadi = ?, dahili = ?
            WHERE id = ?
        ");
        $stmt->execute([$adi, $soyadi, $dahili, $userId]);
    }
    
    // Rolleri güncelle
    $currentUser = $auth->getCurrentUser();
    
    // Mevcut rolleri sil
    $deleteStmt = $pdo->prepare("DELETE FROM USER_ROLES WHERE user_id = ?");
    $deleteStmt->execute([$userId]);
    
    // Yeni rolleri ekle
    $insertRoleStmt = $pdo->prepare("
        INSERT INTO USER_ROLES (user_id, role_id, assigned_at, assigned_by) 
        VALUES (?, ?, NOW(), ?)
    ");
    
    foreach ($roles as $roleId) {
        $insertRoleStmt->execute([$userId, $roleId, $currentUser['id']]);
    }
    
    $pdo->commit();
    
    // Log
    $auth->logActivity(
        $currentUser['id'],
        'update',
        'user',
        "Kullanıcı güncellendi (" . count($roles) . " rol atandı)",
        $userId
    );
    
    echo json_encode([
        'success' => true,
        'message' => 'Kullanıcı başarıyla güncellendi'
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
