<?php
/**
 * Azure Cognitive Services - Neural TTS
 * Microsoft'un Neural Text-to-Speech servisi
 * 
 * ÜCRETSİZ: 5 milyon karakter/ay
 * Türkçe Sesler: Emel (Kadın), Ahmet (Erkek)
 * Kalite: ÇOOK DOĞAL (Neural AI)
 */

class AzureTTS
{
    private $subscriptionKey;
    private $region;
    private $tempPath;
    private $defaultVoice = 'tr-TR-EmelNeural'; // Emel (Kadın) - Varsayılan
    private $voiceGender = 'Female';
    
    // Kullanılabilir Türkçe sesler
    private $turkishVoices = [
        'Female' => [
            'tr-TR-EmelNeural' => 'Emel (Kadın, Genç, Enerjik)',
        ],
        'Male' => [
            'tr-TR-AhmetNeural' => 'Ahmet (Erkek, Orta Yaş, Profesyonel)',
        ]
    ];
    
    public function __construct($subscriptionKey = null, $region = null)
    {
        // Config'den al veya parametre kullan
        if ($subscriptionKey) {
            $this->subscriptionKey = $subscriptionKey;
        } elseif (defined('AZURE_TTS_KEY')) {
            $this->subscriptionKey = AZURE_TTS_KEY;
        }
        
        if ($region) {
            $this->region = $region;
        } elseif (defined('AZURE_TTS_REGION')) {
            $this->region = AZURE_TTS_REGION;
        } else {
            $this->region = 'westeurope'; // Varsayılan: West Europe
        }
        
        $this->tempPath = __DIR__ . '/../temp/';
        
        if (!file_exists($this->tempPath)) {
            mkdir($this->tempPath, 0777, true);
        }
    }
    
    /**
     * Metni Azure Neural TTS ile sese çevir
     * @param string $text Metin
     * @param string $outputFile Çıkış dosyası (.wav)
     * @param string $voice Ses adı (opsiyonel)
     * @return bool Başarılı ise true
     */
    public function textToSpeech($text, $outputFile, $voice = null)
    {
        if (empty($this->subscriptionKey)) {
            throw new Exception("Azure TTS Subscription Key tanımlı değil. Lütfen config.secure.php'de AZURE_TTS_KEY tanımlayın.");
        }
        
        // Ses seçimi
        if (!$voice) {
            $voice = $this->defaultVoice;
        }
        
        // SSML oluştur (Ses kalitesi için önemli!)
        $ssml = $this->generateSSML($text, $voice);
        
        // Azure TTS API endpoint
        $endpoint = "https://{$this->region}.tts.speech.microsoft.com/cognitiveservices/v1";
        
        // HTTP isteği için cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $ssml);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        // Headers
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Ocp-Apim-Subscription-Key: ' . $this->subscriptionKey,
            'Content-Type: application/ssml+xml',
            'X-Microsoft-OutputFormat: audio-16khz-32kbitrate-mono-mp3', // MP3 çıkış
            'User-Agent: OtomatikAramaSystemi/1.0'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($httpCode === 200 && !empty($response)) {
            // MP3 dosyasını kaydet
            $mp3File = str_replace('.wav', '.mp3', $outputFile);
            file_put_contents($mp3File, $response);
            
            // MP3 → WAV dönüştür (Asterisk formatı)
            $this->convertToWav($mp3File, $outputFile);
            
            // MP3'ü sil
            @unlink($mp3File);
            
            error_log("✅ Azure TTS kullanıldı: " . basename($outputFile) . " (Neural Voice)");
            return true;
        } else {
            $errorMsg = "Azure TTS API error: HTTP $httpCode";
            if ($httpCode === 401) {
                $errorMsg .= " - Subscription Key geçersiz veya expire olmuş!";
            } elseif ($httpCode === 403) {
                $errorMsg .= " - Subscription Key yetkilendirme hatası!";
            } elseif ($httpCode === 429) {
                $errorMsg .= " - Rate limit aşıldı, biraz bekleyin!";
            }
            
            error_log($errorMsg);
            throw new Exception($errorMsg);
        }
    }
    
    /**
     * SSML (Speech Synthesis Markup Language) oluştur
     * Ses kalitesini artırır (duraklamalar, vurgu, hız vb.)
     */
    private function generateSSML($text, $voice)
    {
        // XML special karakterleri encode et
        $text = htmlspecialchars($text, ENT_XML1, 'UTF-8');
        
        // SSML template
        $ssml = <<<SSML
<speak version='1.0' xmlns='http://www.w3.org/2001/10/synthesis' xml:lang='tr-TR'>
    <voice name='{$voice}'>
        <prosody rate='0%' pitch='0%' volume='100'>
            {$text}
        </prosody>
    </voice>
</speak>
SSML;
        
        return $ssml;
    }
    
    /**
     * MP3'ü WAV'a çevir (Asterisk formatı: 8kHz, 16-bit, Mono)
     */
    private function convertToWav($mp3File, $wavFile)
    {
        // FFmpeg yolunu bul
        $ffmpegPaths = [
            'd:\xampp\php\ffmpeg.exe',
            'c:\xampp\php\ffmpeg.exe',
            'd:\ffmpeg\bin\ffmpeg.exe',
            'c:\ffmpeg\bin\ffmpeg.exe',
            'ffmpeg'
        ];
        
        $ffmpegPath = null;
        foreach ($ffmpegPaths as $path) {
            if ($path === 'ffmpeg') {
                exec('where ffmpeg 2>nul', $whereOutput, $whereCode);
                if ($whereCode === 0) {
                    $ffmpegPath = 'ffmpeg';
                    break;
                }
            } elseif (file_exists($path)) {
                $ffmpegPath = $path;
                break;
            }
        }
        
        if (!$ffmpegPath) {
            throw new Exception("FFmpeg bulunamadı");
        }
        
        // MP3 → WAV (Asterisk formatı)
        $command = "\"{$ffmpegPath}\" -i \"{$mp3File}\" -ar 8000 -ac 1 -acodec pcm_s16le -y \"{$wavFile}\" 2>&1";
        exec($command, $output, $returnCode);
        
        if (!file_exists($wavFile) || filesize($wavFile) === 0) {
            throw new Exception("MP3 → WAV dönüştürme hatası: " . implode("\n", $output));
        }
        
        return true;
    }
    
    /**
     * Kullanılabilir Türkçe sesleri listele
     */
    public function getAvailableVoices()
    {
        return $this->turkishVoices;
    }
    
    /**
     * Varsayılan sesi değiştir
     */
    public function setDefaultVoice($voice)
    {
        $this->defaultVoice = $voice;
    }
    
    /**
     * Test fonksiyonu
     */
    public function test()
    {
        echo "Azure TTS Test\n";
        echo "==============\n\n";
        
        if (empty($this->subscriptionKey)) {
            echo "❌ HATA: Azure Subscription Key tanımlı değil!\n\n";
            echo "Lütfen config.secure.php dosyasına ekleyin:\n";
            echo "define('AZURE_TTS_KEY', 'your-subscription-key');\n";
            echo "define('AZURE_TTS_REGION', 'westeurope');\n\n";
            return false;
        }
        
        echo "✅ Subscription Key: " . substr($this->subscriptionKey, 0, 8) . "***\n";
        echo "✅ Region: {$this->region}\n";
        echo "✅ Varsayılan Ses: {$this->defaultVoice}\n\n";
        
        echo "Kullanılabilir Türkçe Sesler:\n";
        foreach ($this->turkishVoices as $gender => $voices) {
            echo "  {$gender}:\n";
            foreach ($voices as $voiceId => $description) {
                echo "    - {$voiceId}: {$description}\n";
            }
        }
        
        echo "\nTest metni seslendiriliyor...\n";
        
        $testText = "Merhaba, ben Azure Neural TTS. Microsoft'un yapay zeka teknolojisi ile çok doğal Türkçe konuşabiliyorum. Bu ses kalitesi Google TTS'den çok daha iyi!";
        $outputFile = $this->tempPath . "azure_test.wav";
        
        try {
            if ($this->textToSpeech($testText, $outputFile)) {
                echo "✅ BAŞARILI!\n";
                echo "Dosya: $outputFile\n";
                echo "Boyut: " . filesize($outputFile) . " bytes\n";
                return true;
            }
        } catch (Exception $e) {
            echo "❌ HATA: " . $e->getMessage() . "\n";
            return false;
        }
    }
}
