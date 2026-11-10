<?php
/**
 * Proje Caller ID ve Trunk Güncelleme
 */

// Output buffering başlat (istenmeyen çıktıları yakala)
ob_start();

// Tüm hataları logla
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Auth kontrolü (session_start dahil)
require_once 'includes/check_auth.php';
require_once 'config.php';

// İstenmeyen çıktıları temizle
ob_end_clean();

// Şimdi JSON header'ı gönder
header('Content-Type: application/json');

try {
    if (!isset($_POST['project_id'])) {
        throw new Exception('project_id parametresi eksik!');
    }
    
    $projectId = (int)$_POST['project_id'];
    $callerId = isset($_POST['caller_id']) ? trim($_POST['caller_id']) : '';
    $trunk = isset($_POST['trunk']) ? trim($_POST['trunk']) : '';
    
    // Caller ID format kontrolü (opsiyonel)
    if (!empty($callerId) && !preg_match('/^[0-9]{10,15}$/', $callerId)) {
        throw new Exception('Caller ID sadece 10-15 haneli rakamlardan oluşmalıdır!');
    }
    
    // Güncelle
    $stmt = $pdo->prepare("
        UPDATE PROJECTS 
        SET CallerID = ?, Trunk = ?
        WHERE id = ?
    ");
    $result = $stmt->execute([$callerId, $trunk, $projectId]);
    
    if (!$result) {
        throw new Exception('Veritabanı güncelleme başarısız!');
    }
    
    echo json_encode([
        'success' => true,
        'message' => '✅ Proje bilgileri güncellendi!',
        'caller_id' => $callerId,
        'trunk' => $trunk
    ]);
    
} catch (Exception $e) {
    error_log("update_caller_id.php hata: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

