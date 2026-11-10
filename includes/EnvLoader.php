<?php
/**
 * Basit .env Dosya Yükleyici
 * PHP 5.4+ uyumlu
 */

class EnvLoader
{
    private $path;
    
    public function __construct($path)
    {
        $this->path = $path;
    }
    
    /**
     * .env dosyasını yükle
     */
    public function load()
    {
        if (!file_exists($this->path)) {
            throw new Exception('.env dosyası bulunamadı: ' . $this->path);
        }
        
        $lines = file($this->path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Yorumları atla
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            // KEY=VALUE formatını parse et
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                
                $key = trim($key);
                $value = trim($value);
                
                // Tırnak işaretlerini kaldır
                $value = trim($value, '"\'');
                
                // Ortam değişkenine ekle
                if (!array_key_exists($key, $_ENV)) {
                    putenv("$key=$value");
                    $_ENV[$key] = $value;
                    $_SERVER[$key] = $value;
                }
            }
        }
    }
    
    /**
     * Ortam değişkenini al
     */
    public static function get($key, $default = null)
    {
        // $_ENV'den al
        if (isset($_ENV[$key])) {
            return $_ENV[$key];
        }
        
        // $_SERVER'dan al
        if (isset($_SERVER[$key])) {
            return $_SERVER[$key];
        }
        
        // getenv() ile al
        $value = getenv($key);
        if ($value !== false) {
            return $value;
        }
        
        return $default;
    }
}
