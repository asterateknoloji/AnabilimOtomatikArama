<?php
/**
 * Yeni kullanıcı oluştur
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
if (!$auth->hasPermission('user.create')) {
    echo json_encode(['success' => false, 'message' => 'Permission denied']);
    exit;
}

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';
$adi = $_POST['adi'] ?? '';
$soyadi = $_POST['soyadi'] ?? '';
$dahili = $_POST['dahili'] ?? null;
$roles = $_POST['roles'] ?? [];

// Validasyon
if (empty($username) || empty($password) || empty($adi) || empty($soyadi)) {
    echo json_encode(['success' => false, 'message' => 'Tüm zorunlu alanları doldurun']);
    exit;
}

if (empty($roles)) {
    echo json_encode(['success' => false, 'message' => 'En az bir rol seçmelisiniz']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Kullanıcı adı kontrol
    $checkStmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $checkStmt->execute([$username]);
    
    if ($checkStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Bu kullanıcı adı zaten kullanılıyor']);
        exit;
    }
    
    // Şifreyi hash'le
    $encryption = new Encryption();
    $hashedPassword = $encryption->hashPassword($password);
    
    // Kullanıcıyı oluştur
    $stmt = $pdo->prepare("
        INSERT INTO users (username, password, adi, soyadi, dahili, isadmin, isview) 
        VALUES (?, ?, ?, ?, ?, 0, 1)
    ");
    $stmt->execute([$username, $hashedPassword, $adi, $soyadi, $dahili]);
    
    $userId = $pdo->lastInsertId();
    
    // Rolleri ata
    $currentUser = $auth->getCurrentUser();
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
        'create',
        'user',
        "Yeni kullanıcı oluşturuldu: $username (" . count($roles) . " rol atandı)",
        $userId
    );
    
    echo json_encode([
        'success' => true,
        'message' => 'Kullanıcı başarıyla oluşturuldu',
        'user_id' => $userId
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
