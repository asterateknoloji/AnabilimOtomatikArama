<?php
/**
 * Authentication & Authorization Class
 * Kullanıcı girişi, oturum yönetimi ve yetkilendirme
 */

require_once __DIR__ . '/Encryption.php';

class Auth
{
    private $pdo;
    private $encryption;
    private $sessionTimeout = 3600; // 1 saat
    
    /**
     * Constructor
     * @param PDO $pdo Database bağlantısı
     */
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->encryption = new Encryption();
        
        // Session başlat
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    /**
     * Kullanıcı girişi
     * @param string $username Kullanıcı adı
     * @param string $password Şifre
     * @return array|false Başarılıysa kullanıcı bilgileri, değilse false
     */
    public function login($username, $password)
    {
        try {
            // Kullanıcıyı bul
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute(array($username));
            $user = $stmt->fetch();
            
            if (!$user) {
                $this->logActivity(null, 'login', 'auth', 'Başarısız giriş: Kullanıcı bulunamadı - ' . $username);
                return false;
            }
            
            // Şifre kontrolü
            if (!$this->encryption->verifyPassword($password, $user['password'])) {
                $this->logActivity($user['id'], 'login', 'auth', 'Başarısız giriş: Hatalı şifre');
                return false;
            }
            
            // Session oluştur
            $sessionToken = $this->encryption->generateToken(32);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['session_token'] = $sessionToken;
            $_SESSION['login_time'] = time();
            
            // Veritabanına session kaydet
            $this->saveSession($user['id'], $sessionToken);
            
            // Log
            $this->logActivity($user['id'], 'login', 'auth', 'Başarılı giriş');
            
            return $user;
            
        } catch (Exception $e) {
            error_log("Auth::login error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Kullanıcı çıkışı
     * @return bool
     */
    public function logout()
    {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        $userId = $_SESSION['user_id'];
        
        // Session'ı veritabanından sil
        if (isset($_SESSION['session_token'])) {
            $this->deleteSession($_SESSION['session_token']);
        }
        
        // Log
        $this->logActivity($userId, 'logout', 'auth', 'Çıkış yapıldı');
        
        // Session'ı temizle
        session_destroy();
        
        return true;
    }
    
    /**
     * Kullanıcı oturumu aktif mi?
     * @return bool
     */
    public function isLoggedIn()
    {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['session_token'])) {
            return false;
        }
        
        // Timeout kontrolü
        if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > $this->sessionTimeout) {
            $this->logout();
            return false;
        }
        
        // Veritabanında session var mı? (Tablo varsa kontrol et)
        try {
            // USER_SESSIONS tablosu varsa kontrol et
            $stmt = $this->pdo->query("SHOW TABLES LIKE 'USER_SESSIONS'");
            if ($stmt->rowCount() > 0) {
                if (!$this->checkSession($_SESSION['session_token'])) {
                    $this->logout();
                    return false;
                }
                $this->updateSessionActivity($_SESSION['session_token']);
            }
        } catch (Exception $e) {
            // Tablo yoksa sadece session kontrolü yap (eski sistem)
            error_log("Auth::isLoggedIn: USER_SESSIONS tablosu yok, basit session kontrolü yapılıyor");
        }
        
        // Son aktiviteyi güncelle
        $_SESSION['login_time'] = time();
        
        return true;
    }
    
    /**
     * Mevcut kullanıcı bilgileri
     * @return array|null
     */
    public function getCurrentUser()
    {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute(array($_SESSION['user_id']));
            return $stmt->fetch();
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Kullanıcının rolleri
     * @param int $userId Kullanıcı ID
     * @return array Rol kodları dizisi
     */
    public function getUserRoles($userId)
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT r.role_code 
                 FROM USER_ROLES ur 
                 JOIN ROLES r ON ur.role_id = r.id 
                 WHERE ur.user_id = ? AND r.is_active = 1"
            );
            $stmt->execute(array($userId));
            
            $roles = array();
            while ($row = $stmt->fetch()) {
                $roles[] = $row['role_code'];
            }
            
            return $roles;
            
        } catch (Exception $e) {
            return array();
        }
    }
    
    /**
     * Kullanıcının izinleri
     * @param int $userId Kullanıcı ID
     * @return array İzin kodları dizisi
     */
    public function getUserPermissions($userId)
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT DISTINCT p.permission_code 
                 FROM USER_ROLES ur 
                 JOIN ROLE_PERMISSIONS rp ON ur.role_id = rp.role_id 
                 JOIN PERMISSIONS p ON rp.permission_id = p.id 
                 WHERE ur.user_id = ?"
            );
            $stmt->execute(array($userId));
            
            $permissions = array();
            while ($row = $stmt->fetch()) {
                $permissions[] = $row['permission_code'];
            }
            
            return $permissions;
            
        } catch (Exception $e) {
            return array();
        }
    }
    
    /**
     * Kullanıcı belirli role sahip mi?
     * @param string $roleCode Rol kodu
     * @param int|null $userId Kullanıcı ID (null ise mevcut kullanıcı)
     * @return bool
     */
    public function hasRole($roleCode, $userId = null)
    {
        if ($userId === null) {
            if (!$this->isLoggedIn()) {
                return false;
            }
            $userId = $_SESSION['user_id'];
        }
        
        $roles = $this->getUserRoles($userId);
        return in_array($roleCode, $roles);
    }
    
    /**
     * Kullanıcı belirli izne sahip mi?
     * @param string $permissionCode İzin kodu
     * @param int|null $userId Kullanıcı ID (null ise mevcut kullanıcı)
     * @return bool
     */
    public function hasPermission($permissionCode, $userId = null)
    {
        if ($userId === null) {
            if (!$this->isLoggedIn()) {
                return false;
            }
            $userId = $_SESSION['user_id'];
        }
        
        // Superadmin her şeyi yapabilir
        if ($this->hasRole('superadmin', $userId)) {
            return true;
        }
        
        $permissions = $this->getUserPermissions($userId);
        return in_array($permissionCode, $permissions);
    }
    
    /**
     * Kullanıcı aktivitesini logla
     * @param int|null $userId Kullanıcı ID
     * @param string $actionType İşlem tipi
     * @param string $actionModule Modül
     * @param string $description Açıklama
     * @param int|null $targetId Hedef ID
     * @param array|null $requestData Request verileri
     * @return bool
     */
    /**
     * Gerçek IP adresini al (Proxy arkasında bile)
     */
    private function getRealIpAddress()
    {
        // Proxy başlıklarını kontrol et
        $headers = array(
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        );
        
        foreach ($headers as $header) {
            if (isset($_SERVER[$header]) && !empty($_SERVER[$header])) {
                // Birden fazla IP varsa (virgülle ayrılmış), ilkini al
                $ip = $_SERVER[$header];
                if (strpos($ip, ',') !== false) {
                    $ips = explode(',', $ip);
                    $ip = trim($ips[0]);
                }
                
                // IP adresini validate et
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
                // Private IP'leri de kabul et (local network için)
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return 'unknown';
    }
    
    public function logActivity($userId, $actionType, $actionModule, $description, $targetId = null, $requestData = null)
    {
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO USER_ACTIVITY_LOG 
                 (user_id, action_type, action_module, action_description, target_id, ip_address, user_agent, request_data, created_at) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())"
            );
            
            $stmt->execute(array(
                $userId,
                $actionType,
                $actionModule,
                $description,
                $targetId,
                $this->getRealIpAddress(),
                isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null,
                $requestData ? json_encode($requestData) : null
            ));
            
            return true;
            
        } catch (Exception $e) {
            error_log("Auth::logActivity error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Session'ı veritabanına kaydet
     */
    private function saveSession($userId, $sessionToken)
    {
        try {
            // USER_SESSIONS tablosu varsa session kaydet
            $stmt = $this->pdo->query("SHOW TABLES LIKE 'USER_SESSIONS'");
            if ($stmt->rowCount() == 0) {
                // Tablo yok, session PHP'de tutulacak
                return;
            }
            
            $expiresAt = date('Y-m-d H:i:s', time() + $this->sessionTimeout);
            
            $stmt = $this->pdo->prepare(
                "INSERT INTO USER_SESSIONS 
                 (user_id, session_token, ip_address, user_agent, last_activity, created_at, expires_at, is_active) 
                 VALUES (?, ?, ?, ?, NOW(), NOW(), ?, 1)"
            );
            
            $stmt->execute(array(
                $userId,
                hash('sha256', $sessionToken),
                $this->getRealIpAddress(),
                isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null,
                $expiresAt
            ));
            
        } catch (Exception $e) {
            error_log("Auth::saveSession error: " . $e->getMessage());
        }
    }
    
    /**
     * Session kontrolü
     */
    private function checkSession($sessionToken)
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT id FROM USER_SESSIONS 
                 WHERE session_token = ? AND is_active = 1 AND expires_at > NOW()"
            );
            $stmt->execute(array(hash('sha256', $sessionToken)));
            return $stmt->fetch() !== false;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Session aktivitesini güncelle
     */
    private function updateSessionActivity($sessionToken)
    {
        try {
            // USER_SESSIONS tablosu varsa güncelle
            $stmt = $this->pdo->query("SHOW TABLES LIKE 'USER_SESSIONS'");
            if ($stmt->rowCount() == 0) {
                // Tablo yok, işlem gerekmez
                return;
            }
            
            $stmt = $this->pdo->prepare(
                "UPDATE USER_SESSIONS SET last_activity = NOW() WHERE session_token = ?"
            );
            $stmt->execute(array(hash('sha256', $sessionToken)));
        } catch (Exception $e) {
            error_log("Auth::updateSessionActivity error: " . $e->getMessage());
        }
    }
    
    /**
     * Session'ı sil
     */
    private function deleteSession($sessionToken)
    {
        try {
            // USER_SESSIONS tablosu varsa session sil
            $stmt = $this->pdo->query("SHOW TABLES LIKE 'USER_SESSIONS'");
            if ($stmt->rowCount() == 0) {
                // Tablo yok, işlem gerekmez
                return;
            }
            
            $stmt = $this->pdo->prepare(
                "UPDATE USER_SESSIONS SET is_active = 0 WHERE session_token = ?"
            );
            $stmt->execute(array(hash('sha256', $sessionToken)));
        } catch (Exception $e) {
            error_log("Auth::deleteSession error: " . $e->getMessage());
        }
    }
}
