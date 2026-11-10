<?php
/**
 * Proje Silme
 */

ob_start();

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once 'includes/check_auth.php';
requirePermission('project.delete');
require_once 'config.php';
require_once 'includes/ProjectManager.php';

ob_end_clean();
header('Content-Type: application/json');

try {
    if (!isset($_POST['project_id'])) {
        throw new Exception('project_id parametresi eksik!');
    }
    
    $projectId = (int)$_POST['project_id'];
    
    // Varsayılan projeyi koruma (id=1)
    if ($projectId === 1) {
        throw new Exception('❌ Varsayılan Proje silinemez!');
    }
    
    $projectManager = new ProjectManager($pdo);
    
    // Proje bilgisini al
    $projectInfo = $projectManager->getProject($projectId);
    
    if (!$projectInfo) {
        throw new Exception('Proje bulunamadı!');
    }
    
    // İsim kontrolü (ek güvenlik)
    if ($projectInfo['ProjectName'] === 'Varsayılan Proje') {
        throw new Exception('❌ Varsayılan Proje silinemez!');
    }
    
    // Sil
    $result = $projectManager->deleteProject($projectId);
    
    if (!$result) {
        throw new Exception('Silme işlemi başarısız!');
    }
    
    // Log
    logActivity('delete', 'project', "Proje silindi: {$projectInfo['ProjectName']}", $projectId);
    
    echo json_encode([
        'success' => true,
        'message' => '✅ Proje "' . $projectInfo['ProjectName'] . '" silindi!'
    ]);
    
} catch (Exception $e) {
    error_log("delete_project.php hata: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
