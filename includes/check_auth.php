<?php
/**
 * Authentication Check Helper
 * Tüm sayfalara dahil edilir
 */

if (!defined('AUTH_INCLUDED')) {
    define('AUTH_INCLUDED', true);
    
    require_once __DIR__ . '/../config.php';
    require_once __DIR__ . '/Auth.php';
    
    // Auth instance oluştur
    $auth = new Auth($pdo);
    
    // Login kontrolü
    if (!$auth->isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
    
    // Mevcut kullanıcı bilgilerini al
    $currentUser = $auth->getCurrentUser();
    
    /**
     * İzin kontrolü helper fonksiyonu
     * @param string $permission İzin kodu (örn: 'import.create')
     * @param bool $redirect true ise yetkisizse hata sayfasına yönlendir
     * @return bool
     */
    function requirePermission($permission, $redirect = true) {
        global $auth;
        
        if (!$auth->hasPermission($permission)) {
            if ($redirect) {
                header('Location: unauthorized.php');
                exit;
            }
            return false;
        }
        return true;
    }
    
    /**
     * Rol kontrolü helper fonksiyonu
     * @param string $role Rol kodu (örn: 'superadmin')
     * @return bool
     */
    function hasRole($role) {
        global $auth;
        return $auth->hasRole($role);
    }
    
    /**
     * İzin kontrolü helper fonksiyonu
     * @param string $permission İzin kodu
     * @return bool
     */
    function hasPermission($permission) {
        global $auth;
        return $auth->hasPermission($permission);
    }
    
    /**
     * Aktivite logla helper fonksiyonu
     * @param string $actionType İşlem tipi
     * @param string $actionModule Modül
     * @param string $description Açıklama
     * @param int|null $targetId Hedef ID
     */
    function logActivity($actionType, $actionModule, $description, $targetId = null) {
        global $auth, $currentUser;
        $auth->logActivity($currentUser['id'], $actionType, $actionModule, $description, $targetId);
    }
}
