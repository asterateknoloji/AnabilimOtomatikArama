<?php
/**
 * Encryption/Decryption Class
 * AES-256-CBC ile güvenli şifreleme
 * PHP 5.4+ uyumlu
 */

class Encryption
{
    private $cipher = 'AES-256-CBC';
    private $key;
    
    /**
     * Constructor
     * @param string|null $secretKey Şifreleme anahtarı (32 byte)
     */
    public function __construct($secretKey = null)
    {
        // Eğer anahtar verilmemişse, varsayılan anahtar oluştur
        // ÖNEMLİ: Production'da bu anahtarı değiştirin!
        if ($secretKey === null) {
            $secretKey = 'AsteraOtomatikArama2026SecretKey!';
        }
        
        // Anahtarı 32 byte'a getir (SHA-256)
        $this->key = hash('sha256', $secretKey, true);
    }
    
    /**
     * Veriyi şifrele
     * @param string $data Şifrelenecek veri
     * @return string Base64 encoded şifreli veri
     */
    public function encrypt($data)
    {
        if (empty($data)) {
            return '';
        }
        
        // IV (Initialization Vector) oluştur
        $ivLength = openssl_cipher_iv_length($this->cipher);
        $iv = openssl_random_pseudo_bytes($ivLength);
        
        // Şifrele
        $encrypted = openssl_encrypt(
            $data,
            $this->cipher,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv
        );
        
        if ($encrypted === false) {
            throw new Exception('Şifreleme hatası');
        }
        
        // IV + Encrypted data birleştir ve base64 encode et
        return base64_encode($iv . $encrypted);
    }
    
    /**
     * Veriyi çöz
     * @param string $encryptedData Base64 encoded şifreli veri
     * @return string Çözülmüş veri
     */
    public function decrypt($encryptedData)
    {
        if (empty($encryptedData)) {
            return '';
        }
        
        // Base64 decode
        $data = base64_decode($encryptedData);
        
        if ($data === false) {
            throw new Exception('Base64 decode hatası');
        }
        
        // IV ve encrypted data'yı ayır
        $ivLength = openssl_cipher_iv_length($this->cipher);
        $iv = substr($data, 0, $ivLength);
        $encrypted = substr($data, $ivLength);
        
        // Şifreyi çöz
        $decrypted = openssl_decrypt(
            $encrypted,
            $this->cipher,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv
        );
        
        if ($decrypted === false) {
            throw new Exception('Şifre çözme hatası');
        }
        
        return $decrypted;
    }
    
    /**
     * Şifre hash'i oluştur (kullanıcı şifreleri için)
     * @param string $password Şifre
     * @return string Hash
     */
    public function hashPassword($password)
    {
        // PHP 5.5+ için password_hash kullan
        if (function_exists('password_hash')) {
            return password_hash($password, PASSWORD_DEFAULT);
        }
        
        // PHP 5.4 için fallback (SHA-256 + salt)
        $salt = bin2hex(openssl_random_pseudo_bytes(16));
        return hash('sha256', $salt . $password) . ':' . $salt;
    }
    
    /**
     * Şifre doğrulama
     * @param string $password Girilen şifre
     * @param string $hash Kayıtlı hash
     * @return bool
     */
    public function verifyPassword($password, $hash)
    {
        // PHP 5.5+ için password_verify kullan
        if (function_exists('password_verify')) {
            return password_verify($password, $hash);
        }
        
        // PHP 5.4 için fallback
        if (strpos($hash, ':') === false) {
            // Eski MD5 hash'ler için (geriye dönük uyumluluk)
            return md5($password) === $hash;
        }
        
        list($storedHash, $salt) = explode(':', $hash);
        return hash('sha256', $salt . $password) === $storedHash;
    }
    
    /**
     * Güvenli random token oluştur
     * @param int $length Token uzunluğu
     * @return string Hex token
     */
    public function generateToken($length = 32)
    {
        return bin2hex(openssl_random_pseudo_bytes($length));
    }
}
