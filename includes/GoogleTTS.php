<?php
/**
 * Google Text-to-Speech API Wrapper
 * Python gerektirmeden doğrudan Google Translate TTS API'yi kullanır
 * 100% ÜCRETSIZ - Sınırsız kullanım
 */

require_once __DIR__ . '/TTSCache.php';

class GoogleTTS
{
    private $tempPath;
    private $audioBasePath;
    private $cache;
    private $useCache = true;
    private $requestDelay = 100000; // 100ms (mikrosaniye) - Rate limiting
    private static $lastRequestTime = 0;
    private $apiKey;
    private $voice;
    
    public function __construct($apiKey = '', $voice = '')
    {
        $this->apiKey = trim((string)$apiKey);
        $this->voice = trim((string)$voice);
        $this->useCache = $this->apiKey === '';
        $this->tempPath = __DIR__ . '/../temp/';
        $this->audioBasePath = __DIR__ . '/../asterisk/audio/calls/';
        $this->cache = new TTSCache();
        
        if (!file_exists($this->tempPath)) {
            mkdir($this->tempPath, 0777, true);
        }
        
        if (!file_exists($this->audioBasePath)) {
            mkdir($this->audioBasePath, 0777, true);
        }
    }
    
    /**
     * Metni Google TTS ile sese çevir (Cache destekli)
     * @param string $text Metin (maksimum 200 karakter per istek)
     * @param string $outputFile Çıkış dosyası (.mp3)
     * @param string $lang Dil kodu (varsayılan: tr)
     * @return bool Başarılı ise true
     */
    public function textToSpeech($text, $outputFile, $lang = 'tr')
    {
        // Cache kontrolü
        if ($this->useCache) {
            $cachedWav = $this->cache->get($text, $lang);
            
            if ($cachedWav && file_exists($cachedWav)) {
                // Cache HIT! Direkt kopyala
                copy($cachedWav, $outputFile);
                error_log("✅ TTS Cache HIT: " . mb_substr($text, 0, 50) . "...");
                return true;
            }
        }
        
        // Cache MISS - Google'dan indir
        error_log("⬇️ TTS Cache MISS, Google'dan indiriliyor: " . mb_substr($text, 0, 50) . "...");
        
        // Rate limiting (çok hızlı istek yapma)
        $this->rateLimit();
        
        // Google Translate TTS API endpoint
        // Bu API ücretsiz ve limit yok (Google Translate web sayfasının kullandığı API)
        
        // Uzun metinleri parçalara böl (Google TTS max 200 karakter)
        $chunks = $this->splitText($text, 200);
        $audioChunks = [];
        
        foreach ($chunks as $index => $chunk) {
            // Her chunk için rate limit
            if ($index > 0) {
                $this->rateLimit();
            }
            
            $chunkFile = $this->tempPath . "chunk_{$index}_" . uniqid() . ".mp3";
            
            if ($this->downloadGoogleTTS($chunk, $chunkFile, $lang)) {
                $audioChunks[] = $chunkFile;
            } else {
                // Hata durumunda
                error_log("Google TTS chunk download failed: " . $chunk);
            }
        }
        
        if (empty($audioChunks)) {
            return false;
        }
        
        // Tek chunk ise direkt kopyala
        if (count($audioChunks) === 1) {
            rename($audioChunks[0], $outputFile);
        } else {
            // Birden fazla chunk varsa birleştir
            $this->mergeMP3Files($audioChunks, $outputFile);
            
            // Geçici chunk dosyalarını sil
            foreach ($audioChunks as $chunk) {
                @unlink($chunk);
            }
        }
        
        // Cache'e kaydet
        if ($this->useCache && file_exists($outputFile)) {
            $this->cache->set($text, $outputFile, $lang);
        }
        
        return file_exists($outputFile);
    }
    
    /**
     * Rate limiting - API'ye çok hızlı istek yapma
     */
    private function rateLimit()
    {
        $now = microtime(true);
        $elapsed = ($now - self::$lastRequestTime) * 1000000; // mikrosaniye
        
        if ($elapsed < $this->requestDelay) {
            $sleep = $this->requestDelay - $elapsed;
            usleep($sleep);
        }
        
        self::$lastRequestTime = microtime(true);
    }
    
    /**
     * Google TTS API'den ses dosyasını indir
     */
    private function downloadGoogleTTS($text, $outputFile, $lang = 'tr')
    {
        if ($this->apiKey !== '') {
            return $this->downloadGoogleCloudTTS($text, $outputFile, $lang);
        }

        // Google Translate TTS endpoint
        $url = 'https://translate.google.com/translate_tts';
        
        $params = [
            'ie' => 'UTF-8',
            'q' => $text,
            'tl' => $lang,
            'client' => 'tw-ob',
            'ttsspeed' => '1.30' // Konuşma hızı: 1.30 (%30 daha hızlı, çok canlı!)
        ];
        
        $url .= '?' . http_build_query($params);
        
        // HTTP isteği için cURL kullan
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        // User-Agent ekle (Google bot kontrolü için)
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        
        // Header ekle
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: */*',
            'Accept-Language: tr-TR,tr;q=0.9,en;q=0.8',
            'Referer: https://translate.google.com/'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 && !empty($response)) {
            file_put_contents($outputFile, $response);
            return true;
        }
        
        error_log("Google TTS API error: HTTP $httpCode for text: $text");
        return false;
    }

    /**
     * API anahtarı girilmişse resmi Google Cloud Text-to-Speech servisini kullanır.
     */
    private function downloadGoogleCloudTTS($text, $outputFile, $lang)
    {
        $languageCode = $lang === 'en' ? 'en-US' : 'tr-TR';
        $voice = $this->voice !== ''
            ? $this->voice
            : ($languageCode === 'en-US' ? 'en-US-Wavenet-F' : 'tr-TR-Wavenet-A');

        $payload = json_encode([
            'input' => ['text' => $text],
            'voice' => [
                'languageCode' => $languageCode,
                'name' => $voice,
            ],
            'audioConfig' => [
                'audioEncoding' => 'MP3',
                'speakingRate' => 1.0,
            ],
        ], JSON_UNESCAPED_UNICODE);

        $ch = curl_init('https://texttospeech.googleapis.com/v1/text:synthesize?key=' . rawurlencode($this->apiKey));
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        $data = json_decode((string)$response, true);
        if ($httpCode === 200 && !empty($data['audioContent'])) {
            return file_put_contents($outputFile, base64_decode($data['audioContent'], true)) !== false;
        }

        $message = $data['error']['message'] ?? $curlError ?: 'Bilinmeyen Google Cloud TTS hatası';
        throw new RuntimeException("Google Cloud TTS API hatası (HTTP {$httpCode}): {$message}");
    }
    
    /**
     * Metni 200 karakterlik parçalara böl (cümle sınırlarına dikkat ederek)
     * İYİLEŞTİRME: Doğal duraklamalar için cümle sonlarını koru
     */
    private function splitText($text, $maxLength = 150) // 200'den 150'ye düşürüldü - daha kısa parçalar
    {
        $text = trim($text);
        
        if (mb_strlen($text) <= $maxLength) {
            return [$text];
        }
        
        $chunks = [];
        
        // Önce nokta, ünlem ve soru işaretine göre böl
        $sentences = preg_split('/([.!?]\s+)/', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        
        $currentChunk = '';
        
        foreach ($sentences as $sentence) {
            $sentence = trim($sentence);
            if (empty($sentence)) continue;
            
            if (mb_strlen($currentChunk . $sentence) <= $maxLength) {
                $currentChunk .= $sentence . ' ';
            } else {
                if (!empty($currentChunk)) {
                    $chunks[] = trim($currentChunk);
                }
                
                // Eğer tek cümle çok uzunsa, virgüllerden böl
                if (mb_strlen($sentence) > $maxLength) {
                    $subParts = preg_split('/(,\s+)/', $sentence, -1, PREG_SPLIT_DELIM_CAPTURE);
                    $subChunk = '';
                    
                    foreach ($subParts as $part) {
                        if (mb_strlen($subChunk . $part) <= $maxLength) {
                            $subChunk .= $part;
                        } else {
                            if (!empty($subChunk)) {
                                $chunks[] = trim($subChunk);
                            }
                            $subChunk = $part;
                        }
                    }
                    
                    if (!empty($subChunk)) {
                        $currentChunk = $subChunk;
                    }
                } else {
                    $currentChunk = $sentence . ' ';
                }
            }
        }
        
        if (!empty($currentChunk)) {
            $chunks[] = trim($currentChunk);
        }
        
        return empty($chunks) ? [$text] : $chunks;
    }
    
    /**
     * MP3 dosyalarını birleştir
     */
    private function mergeMP3Files($files, $outputFile)
    {
        // Basit binary concatenation (MP3 için çalışır)
        $outputHandle = fopen($outputFile, 'wb');
        
        foreach ($files as $file) {
            $content = file_get_contents($file);
            fwrite($outputHandle, $content);
        }
        
        fclose($outputHandle);
    }
    
    /**
     * MP3'ü WAV'a çevir (Asterisk için)
     */
    public function convertToWav($mp3File, $wavFile)
    {
        // FFmpeg yolunu .env'den al (config.secure.php yüklenmiş olmalı)
        $ffmpegPath = null;
        
        if (defined('EnvLoader')) {
            // EnvLoader varsa .env'den oku
            require_once __DIR__ . '/EnvLoader.php';
            $ffmpegPath = EnvLoader::get('FFMPEG_PATH', '');
        }
        
        // .env'den okuduysa ve boş değilse kullan
        if (!empty($ffmpegPath) && file_exists($ffmpegPath)) {
            // .env'deki yol kullanılacak
        } else {
            // .env'de yok veya boş - klasik yolları dene
            $ffmpegPaths = [
                'd:\xampp\php\ffmpeg.exe',
                'c:\xampp\php\ffmpeg.exe',
                'd:\ffmpeg\bin\ffmpeg.exe',
                'c:\ffmpeg\bin\ffmpeg.exe',
                '/usr/bin/ffmpeg',
                '/usr/local/bin/ffmpeg',
                'ffmpeg' // PATH'te varsa
            ];
            
            $ffmpegPath = null;
            foreach ($ffmpegPaths as $path) {
                if ($path === 'ffmpeg') {
                    // Windows
                    exec('where ffmpeg 2>nul', $whereOutput, $whereCode);
                    if ($whereCode === 0 && !empty($whereOutput[0])) {
                        $ffmpegPath = trim($whereOutput[0]);
                        break;
                    }
                    // Linux
                    exec('which ffmpeg 2>/dev/null', $whichOutput, $whichCode);
                    if ($whichCode === 0 && !empty($whichOutput[0])) {
                        $ffmpegPath = trim($whichOutput[0]);
                        break;
                    }
                } elseif (file_exists($path)) {
                    $ffmpegPath = $path;
                    break;
                }
            }
        }
        
        if (!$ffmpegPath) {
            throw new Exception("FFmpeg bulunamadı! Lütfen FFmpeg yükleyin veya .env dosyasında FFMPEG_PATH ayarlayın. Windows SAPI'ye düşülecek.");
        }
        
        // MP3 → WAV (Asterisk formatı: 8kHz, 16-bit, Mono)
        $command = "\"{$ffmpegPath}\" -i \"{$mp3File}\" -ar 8000 -ac 1 -acodec pcm_s16le -y \"{$wavFile}\" 2>&1";
        exec($command, $output, $returnCode);
        
        if (!file_exists($wavFile) || filesize($wavFile) === 0) {
            throw new Exception("MP3 → WAV dönüştürme hatası: " . implode("\n", $output));
        }
        
        return true;
    }
    
    /**
     * Test fonksiyonu
     */
    public function test()
    {
        $testText = "Merhaba, bu bir Google Text to Speech test mesajıdır. Türkçe seslendirme kalitesini kontrol ediyoruz.";
        $outputFile = $this->tempPath . "test_google_tts.mp3";
        
        echo "Google TTS Test\n";
        echo "===============\n\n";
        echo "Metin: $testText\n\n";
        echo "İndiriliyor...\n";
        
        if ($this->textToSpeech($testText, $outputFile)) {
            echo "✅ BAŞARILI!\n";
            echo "Dosya: $outputFile\n";
            echo "Boyut: " . filesize($outputFile) . " bytes\n\n";
            
            // WAV'a çevir
            $wavFile = str_replace('.mp3', '.wav', $outputFile);
            echo "WAV'a çevriliyor...\n";
            
            if ($this->convertToWav($outputFile, $wavFile)) {
                echo "✅ WAV dönüştürme başarılı!\n";
                echo "WAV Dosya: $wavFile\n";
                echo "WAV Boyut: " . filesize($wavFile) . " bytes\n";
            }
            
            return true;
        } else {
            echo "❌ BAŞARISIZ!\n";
            return false;
        }
    }
}
