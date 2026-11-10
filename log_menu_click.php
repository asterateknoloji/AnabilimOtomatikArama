<?php
/**
 * Menü tıklama logu
 */

require_once 'includes/check_auth.php';

header('Content-Type: application/json');

try {
    if (!isset($_POST['menu_name']) || !isset($_POST['menu_url'])) {
        throw new Exception('Eksik parametre!');
    }
    
    $menuName = $_POST['menu_name'];
    $menuUrl = $_POST['menu_url'];
    
    // Log kaydı oluştur
    logActivity('navigation', 'menu', "Menüye tıklandı: {$menuName}", null);
    
    echo json_encode([
        'success' => true,
        'message' => 'Log kaydedildi'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
