<?php
/**
 * Config Manager Class
 * Veritabanında şifreli konfigürasyon yönetimi
 */

require_once __DIR__ . '/Encryption.php';

class ConfigManager
{
    private $pdo;
    private $encryption;
    private $cache = array(); // Performans için cache
    
    /**
     * Constructor
     * @param PDO $pdo Database bağlantısı
     * @param string|null $encryptionKey Şifreleme anahtarı
     */
    public function __construct($pdo, $encryptionKey = null)
    {
        $this->pdo = $pdo;
        $this->encryption = new Encryption($encryptionKey);
    }
    
    /**
     * Konfigürasyon değeri al
     * @param string $key Konfigürasyon anahtarı
     * @param mixed $default Varsayılan değer
     * @return string|null
     */
    public function get($key, $default = null)
    {
        // Cache'de var mı?
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }
        
        try {
            $stmt = $this->pdo->prepare(
                "SELECT config_value, is_encrypted FROM SYSTEM_CONFIG WHERE config_key = ?"
            );
            $stmt->execute(array($key));
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            
            if (!$row) {
                return $default;
            }
            
            // Şifreli mi?
            $value = $row['config_value'];
            if ($row['is_encrypted'] == 1) {
                $value = $this->encryption->decrypt($value);
            }
            
            // Cache'e ekle
            $this->cache[$key] = $value;
            
            return $value;
            
        } catch (Exception $e) {
            error_log("ConfigManager::get error: " . $e->getMessage());
            return $default;
        }
    }
    
    /**
     * Konfigürasyon değeri ayarla
     * @param string $key Konfigürasyon anahtarı
     * @param string $value Değer
     * @param string $group Grup (database, ssh, asterisk, system)
     * @param bool $encrypt Şifrelensin mi?
     * @param string|null $description Açıklama
     * @param int|null $userId Güncelleyen kullanıcı ID
     * @return bool
     */
    public function set($key, $value, $group = 'system', $encrypt = false, $description = null, $userId = null)
    {
        try {
            // Şifrelenecek mi?
            $storedValue = $value;
            if ($encrypt) {
                $storedValue = $this->encryption->encrypt($value);
            }
            
            // Mevcut kayıt var mı?
            $checkStmt = $this->pdo->prepare("SELECT id FROM SYSTEM_CONFIG WHERE config_key = ?");
            $checkStmt->execute(array($key));
            $exists = $checkStmt->fetch();
            $checkStmt->closeCursor();
            
            if ($exists) {
                // Güncelle
                $stmt = $this->pdo->prepare(
                    "UPDATE SYSTEM_CONFIG 
                     SET config_value = ?, 
                         config_group = ?, 
                         is_encrypted = ?, 
                         description = ?, 
                         updated_at = NOW(), 
                         updated_by = ? 
                     WHERE config_key = ?"
                );
                $stmt->execute(array($storedValue, $group, $encrypt ? 1 : 0, $description, $userId, $key));
            } else {
                // Yeni kayıt
                $stmt = $this->pdo->prepare(
                    "INSERT INTO SYSTEM_CONFIG 
                     (config_key, config_value, config_group, is_encrypted, description, created_at, updated_at, updated_by) 
                     VALUES (?, ?, ?, ?, ?, NOW(), NOW(), ?)"
                );
                $stmt->execute(array($key, $storedValue, $group, $encrypt ? 1 : 0, $description, $userId));
            }
            
            // Cache'i güncelle
            $this->cache[$key] = $value;
            
            return true;
            
        } catch (Exception $e) {
            error_log("ConfigManager::set error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Gruba göre tüm konfigürasyonları al
     * @param string $group Grup adı
     * @return array Anahtar-değer çifti
     */
    public function getGroup($group)
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT config_key, config_value, is_encrypted FROM SYSTEM_CONFIG WHERE config_group = ?"
            );
            $stmt->execute(array($group));
            
            $configs = array();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $value = $row['config_value'];
                if ($row['is_encrypted'] == 1) {
                    $value = $this->encryption->decrypt($value);
                }
                $configs[$row['config_key']] = $value;
            }
            
            return $configs;
            
        } catch (Exception $e) {
            error_log("ConfigManager::getGroup error: " . $e->getMessage());
            return array();
        }
    }
    
    /**
     * Config.php'den veritabanına migrate et
     * @param array $configArray Config.php'deki değerler
     * @param int|null $userId Güncelleyen kullanıcı ID
     * @return bool
     */
    public function migrateFromArray($configArray, $userId = null)
    {
        try {
            $this->pdo->beginTransaction();
            
            foreach ($configArray as $item) {
                $this->set(
                    $item['key'],
                    $item['value'],
                    $item['group'],
                    $item['encrypt'],
                    $item['description'],
                    $userId
                );
            }
            
            $this->pdo->commit();
            return true;
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("ConfigManager::migrateFromArray error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Cache'i temizle
     */
    public function clearCache()
    {
        $this->cache = array();
    }
    
    /**
     * PDO bağlantısı al (helper method)
     * @return PDO
     */
    public static function createPDO()
    {
        // İlk kez bağlantı için geçici olarak config.php kullan
        // Daha sonra database'den alınacak
        $host = '192.168.181.16';
        $dbname = 'ANABILIM';
        $user = 'astera';
        $pass = 'astera2012';
        
        return new PDO(
            "mysql:host=$host;dbname=$dbname;charset=utf8",
            $user,
            $pass,
            array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
            )
        );
    }
}
