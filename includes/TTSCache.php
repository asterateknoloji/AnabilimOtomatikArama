<?php
/**
 * TTS Cache Manager
 * Aynı metinlerin tekrar TTS ile oluşturulmasını önler
 * Toplu aramalarda BÜYÜK performans artışı sağlar
 */

class TTSCache
{
    private $cacheDir;
    private $maxCacheSize = 500; // MB
    private $cacheExpiry = 2592000; // 30 gün (saniye)
    
    public function __construct()
    {
        $this->cacheDir = __DIR__ . '/../cache/tts/';
        
        if (!file_exists($this->cacheDir)) {
            mkdir($this->cacheDir, 0777, true);
        }
    }
    
    /**
     * Cache'den ses dosyası al (varsa)
     */
    public function get($text, $lang = 'tr')
    {
        $cacheKey = $this->getCacheKey($text, $lang);
        $cacheFile = $this->cacheDir . $cacheKey . '.wav';
        
        if (file_exists($cacheFile)) {
            // Cache expired mı kontrol et
            $fileAge = time() - filemtime($cacheFile);
            
            if ($fileAge < $this->cacheExpiry) {
                // Cache hit!
                return $cacheFile;
            } else {
                // Cache expired - sil
                @unlink($cacheFile);
            }
        }
        
        return null;
    }
    
    /**
     * Ses dosyasını cache'e kaydet
     */
    public function set($text, $audioFile, $lang = 'tr')
    {
        $cacheKey = $this->getCacheKey($text, $lang);
        $cacheFile = $this->cacheDir . $cacheKey . '.wav';
        
        // Cache boyutunu kontrol et
        $this->checkCacheSize();
        
        // Dosyayı cache'e kopyala
        copy($audioFile, $cacheFile);
        
        return $cacheFile;
    }
    
    /**
     * Cache key oluştur (text hash)
     */
    private function getCacheKey($text, $lang)
    {
        // Metni normalize et (büyük/küçük harf, boşluk)
        $normalized = mb_strtolower(trim($text));
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        
        // Hash oluştur
        return $lang . '_' . md5($normalized);
    }
    
    /**
     * Cache boyutunu kontrol et, gerekirse temizle
     */
    private function checkCacheSize()
    {
        $totalSize = 0;
        $files = [];
        
        // Tüm cache dosyalarını listele
        foreach (glob($this->cacheDir . '*.wav') as $file) {
            $size = filesize($file);
            $mtime = filemtime($file);
            
            $totalSize += $size;
            $files[] = [
                'file' => $file,
                'size' => $size,
                'mtime' => $mtime
            ];
        }
        
        // Cache limiti aşıldı mı?
        $maxBytes = $this->maxCacheSize * 1024 * 1024;
        
        if ($totalSize > $maxBytes) {
            // En eski dosyalardan sil
            usort($files, function($a, $b) {
                return $a['mtime'] - $b['mtime'];
            });
            
            foreach ($files as $fileInfo) {
                if ($totalSize <= $maxBytes * 0.8) { // %80'e düşür
                    break;
                }
                
                @unlink($fileInfo['file']);
                $totalSize -= $fileInfo['size'];
            }
            
            error_log("TTS Cache temizlendi. Yeni boyut: " . round($totalSize / 1024 / 1024, 2) . " MB");
        }
    }
    
    /**
     * Cache istatistikleri
     */
    public function getStats()
    {
        $files = glob($this->cacheDir . '*.wav');
        $totalSize = 0;
        
        foreach ($files as $file) {
            $totalSize += filesize($file);
        }
        
        return [
            'count' => count($files),
            'size_mb' => round($totalSize / 1024 / 1024, 2),
            'size_bytes' => $totalSize,
            'limit_mb' => $this->maxCacheSize,
            'usage_percent' => round(($totalSize / ($this->maxCacheSize * 1024 * 1024)) * 100, 2)
        ];
    }
    
    /**
     * Cache'i temizle
     */
    public function clear()
    {
        $files = glob($this->cacheDir . '*.wav');
        $count = 0;
        
        foreach ($files as $file) {
            if (@unlink($file)) {
                $count++;
            }
        }
        
        return $count;
    }
    
    /**
     * Eski cache dosyalarını temizle
     */
    public function cleanup()
    {
        $files = glob($this->cacheDir . '*.wav');
        $count = 0;
        $now = time();
        
        foreach ($files as $file) {
            $age = $now - filemtime($file);
            
            if ($age > $this->cacheExpiry) {
                if (@unlink($file)) {
                    $count++;
                }
            }
        }
        
        return $count;
    }
}
