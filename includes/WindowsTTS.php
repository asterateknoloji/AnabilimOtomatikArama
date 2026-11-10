<?php
/**
 * Multi-Engine Text-to-Speech Sınıfı
 * 1. Google TTS (Ücretsiz, yüksek kalite) - Öncelikli
 * 2. Windows SAPI (Yedek) - Google başarısız olursa
 * SSH ile Asterisk sunucusuna gönderir
 */

require_once __DIR__ . '/SshHelper.php';
require_once __DIR__ . '/GoogleTTS.php';
require_once __DIR__ . '/AzureTTS.php';
require_once __DIR__ . '/TextOptimizer.php';
require_once __DIR__ . '/TTSSettings.php';
require_once __DIR__ . '/TTSSecrets.php';
require_once __DIR__ . '/EMATTS.php';

class WindowsTTS
{
    private $audioBasePath;
    private $tempPath;
    private $ssh;
    private $remoteSoundsPath;
    private $googleTTS;
    private $azureTTS;
    private $emaTTS;
    private $ttsSettings;
    private $settingsManager;
    
    public function __construct()
    {
        $this->settingsManager = new TTSSettings();
        $this->ttsSettings = $this->settingsManager->getAll();
        $secrets = (new TTSSecrets())->getAll();
        $azureKey = $secrets['azure_key'] ?: (defined('AZURE_TTS_KEY') ? AZURE_TTS_KEY : '');
        $azureRegion = $this->ttsSettings['engines']['azure']['region'] ?? 'westeurope';
        $defaultLanguage = $this->ttsSettings['default_language'] ?? 'tr-TR';
        $googleVoice = $this->ttsSettings['engines']['google']['voices'][$defaultLanguage] ?? '';
        $this->azureTTS = new AzureTTS($azureKey, $azureRegion);
        $this->googleTTS = new GoogleTTS($secrets['google_api_key'], $googleVoice);
        $this->emaTTS = new EMATTS($this->settingsManager);
        
        // Ses dosyalarının saklanacağı klasör (yerel)
        $this->audioBasePath = __DIR__ . '/../asterisk/audio/calls/';
        $this->tempPath = __DIR__ . '/../temp/';
        
        // Asterisk sunucusundaki ses dosyaları yolu (config'den)
        if (defined('ASTERISK_SOUNDS_PATH')) {
            $this->remoteSoundsPath = ASTERISK_SOUNDS_PATH;
        } else {
            $this->remoteSoundsPath = '/var/lib/asterisk/sounds/custom/';
        }
        
        // SSH bağlantısı (config'den)
        if (defined('ASTERISK_SSH_HOST')) {
            $this->ssh = new SshHelper(
                ASTERISK_SSH_HOST,
                ASTERISK_SSH_PORT,
                ASTERISK_SSH_USER,
                ASTERISK_SSH_PASS
            );
        }
        
        // Klasörleri oluştur
        if (!file_exists($this->audioBasePath)) {
            mkdir($this->audioBasePath, 0777, true);
        }
        
        if (!file_exists($this->tempPath)) {
            mkdir($this->tempPath, 0777, true);
        }
    }
    
    /**
     * Arama için ses dosyası oluştur ve SSH ile Asterisk sunucusuna gönder
     */
    public function generateCallAudio($callId, $adSoyad, $tutar, $metin1 = '', $metin2 = '', $metin3 = '', $templateId = null)
    {
        if (!$this->ssh) {
            throw new Exception("SSH bağlantısı yapılandırılmamış");
        }
        
        // Yerel ses dosyası adı
        $localAudioFile = $this->audioBasePath . "call_{$callId}.wav";
        
        // Metin oluştur veya birleştirilmiş ses dosyası al
        if ($templateId) {
            $result = $this->generateFromTemplateId($templateId, $adSoyad, $tutar, $metin1, $metin2, $metin3);
            
            // Eğer result bir dosya yolu ise (audio segmentleri birleştirilmiş)
            if (is_string($result) && file_exists($result)) {
                // Birleştirilmiş dosyayı kopyala
                copy($result, $localAudioFile);
                @unlink($result); // Temp dosyayı sil
            } else {
                // Sadece metin - TTS ile oluştur
                $this->textToSpeech($result, $localAudioFile);
                $this->convertToAsteriskFormat($localAudioFile);
            }
        } else {
            $text = $this->generateCallScript($adSoyad, $tutar, $metin1, $metin2, $metin3);
            $this->textToSpeech($text, $localAudioFile);
            $this->convertToAsteriskFormat($localAudioFile);
        }
        
        // SSH ile Asterisk sunucusuna gönder
        $remoteAudioFile = $this->remoteSoundsPath . "call_{$callId}.wav";
        
        try {
            $this->ssh->sendFile($localAudioFile, $remoteAudioFile);
            
            // Yerel dosyayı geçici olarak sakla (temizlik için)
            // @unlink($localAudioFile); // İsterseniz silebilirsiniz
            
            // Asterisk'te dosya yolu (uzantısız, Asterisk otomatik ekler)
            return str_replace('.wav', '', $remoteAudioFile);
            
        } catch (Exception $e) {
            throw new Exception("Ses dosyası SSH ile gönderilemedi: " . $e->getMessage());
        }
    }
    
    /**
     * Şablon ID'den mesaj oluştur
     */
    private function generateFromTemplateId($templateId, $adSoyad, $tutar, $metin1, $metin2, $metin3)
    {
        require_once __DIR__ . '/TemplateManager.php';
        global $pdo;
        
        $templateManager = new TemplateManager($pdo);
        $template = $templateManager->getTemplate($templateId);
        
        if (!$template) {
            throw new Exception("Şablon bulunamadı");
        }
        
        $content = json_decode($template['TemplateContent'], true);
        
        // Eğer şablonda audio veya silence segmenti varsa, özel işlem yap
        $hasSegments = false;
        foreach ($content as $item) {
            if ($item['type'] === 'audio' || $item['type'] === 'silence') {
                $hasSegments = true;
                break;
            }
        }
        
        if ($hasSegments) {
            // Audio/Silence segmentleri var - ffmpeg ile birleştir
            error_log("🎵 Segmentli ses oluşturma başlatılıyor (Template ID: $templateId)");
            return $this->generateAudioWithSegments($templateId, $content, $adSoyad, $tutar, $metin1, $metin2, $metin3);
        } else {
            // Sadece metin - normal TTS
            $data = [
                'Adi' => $adSoyad,
                'Tutar' => $this->numberToText($tutar),
                'Metin1' => $metin1,
                'Metin2' => $metin2,
                'Metin3' => $metin3
            ];
            
            return $templateManager->generateMessage($templateId, $data);
        }
    }
    
    /**
     * Arama metni oluştur (şablondan)
     */
    private function generateCallScript($adSoyad, $tutar, $metin1, $metin2, $metin3)
    {
        // Şablon dosyasını oku
        $templateFile = __DIR__ . '/../includes/tts_template.json';
        
        if (file_exists($templateFile)) {
            return $this->generateFromTemplate($templateFile, $adSoyad, $tutar, $metin1, $metin2, $metin3);
        }
        
        // Varsayılan şablon (şablon dosyası yoksa)
        $script = "Merhaba";
        
        if (!empty($adSoyad)) {
            $script .= " " . $adSoyad;
        }
        
        $script .= ". ";
        
        // Tutar bilgisi
        $tutarText = $this->numberToText($tutar);
        $script .= "Bugünkü borcunuz {$tutarText} Türk Lirasıdır. ";
        
        // Ek metinler
        if (!empty($metin1)) {
            $script .= $metin1 . " ";
        }
        
        if (!empty($metin2)) {
            $script .= $metin2 . " ";
        }
        
        if (!empty($metin3)) {
            $script .= $metin3 . " ";
        }
        
        // Menü
        $script .= "Tekrar dinlemek için 1, operatöre bağlanmak için 2 tuşuna basınız.";
        
        return $script;
    }
    
    /**
     * Şablondan mesaj oluştur
     */
    private function generateFromTemplate($templateFile, $adSoyad, $tutar, $metin1, $metin2, $metin3)
    {
        $template = json_decode(file_get_contents($templateFile), true);
        
        $data = [
            'Adi' => $adSoyad,
            'Metin' => '', // Excel'den gelen Metin alanı kullanılabilir
            'Tutar' => $this->numberToText($tutar),
            'Metin1' => $metin1,
            'Metin2' => $metin2,
            'Metin3' => $metin3
        ];
        
        $script = '';
        
        foreach ($template as $item) {
            if ($item['type'] === 'text') {
                $script .= $item['value'] . ' ';
            } elseif ($item['type'] === 'field') {
                $value = $data[$item['value']] ?? '';
                if (!empty($value)) {
                    $script .= $value . ' ';
                }
            }
        }
        
        return trim($script);
    }
    
    /**
     * Sayıyı Türkçe metne çevir
     */
    private function numberToText($number)
    {
        // Ondalık ayırıcıyı standartlaştır
        $number = floatval(str_replace(',', '.', $number));
        
        // Tam ve ondalık kısımları ayır
        $intPart = floor($number);
        $decPart = round(($number - $intPart) * 100);
        
        // Tam kısmı Türkçe'ye çevir
        $text = $this->convertIntegerToTurkish($intPart);
        
        // Ondalık kısım varsa ekle
        if ($decPart > 0) {
            $text .= ' virgül ' . $this->convertIntegerToTurkish($decPart);
        }
        
        return $text;
    }
    
    /**
     * Tam sayıyı Türkçe'ye çevir
     */
    private function convertIntegerToTurkish($number)
    {
        if ($number == 0) {
            return 'sıfır';
        }
        
        $ones = ['', 'bir', 'iki', 'üç', 'dört', 'beş', 'altı', 'yedi', 'sekiz', 'dokuz'];
        $tens = ['', 'on', 'yirmi', 'otuz', 'kırk', 'elli', 'altmış', 'yetmiş', 'seksen', 'doksan'];
        $hundreds = ['', 'yüz', 'iki yüz', 'üç yüz', 'dört yüz', 'beş yüz', 'altı yüz', 'yedi yüz', 'sekiz yüz', 'dokuz yüz'];
        
        $result = '';
        
        // Milyarlar
        if ($number >= 1000000000) {
            $billions = floor($number / 1000000000);
            if ($billions == 1) {
                $result .= 'bir milyar ';
            } else {
                $result .= $this->convertIntegerToTurkish($billions) . ' milyar ';
            }
            $number %= 1000000000;
        }
        
        // Milyonlar
        if ($number >= 1000000) {
            $millions = floor($number / 1000000);
            if ($millions == 1) {
                $result .= 'bir milyon ';
            } else {
                $result .= $this->convertIntegerToTurkish($millions) . ' milyon ';
            }
            $number %= 1000000;
        }
        
        // Binler
        if ($number >= 1000) {
            $thousands = floor($number / 1000);
            if ($thousands == 1) {
                $result .= 'bin ';
            } else {
                $result .= $this->convertIntegerToTurkish($thousands) . ' bin ';
            }
            $number %= 1000;
        }
        
        // Yüzler
        if ($number >= 100) {
            $hundredsDigit = floor($number / 100);
            $result .= $hundreds[$hundredsDigit] . ' ';
            $number %= 100;
        }
        
        // Onlar
        if ($number >= 10) {
            $tensDigit = floor($number / 10);
            $result .= $tens[$tensDigit] . ' ';
            $number %= 10;
        }
        
        // Birler
        if ($number > 0) {
            $result .= $ones[$number] . ' ';
        }
        
        return trim($result);
    }
    
    /**
     * Tanımlar ekranındaki sıraya göre metni sese çevirir.
     * Varsayılan sıra: Windows → EMA Lightning → Google → Azure.
     */
    public function textToSpeech($text, $outputFile, $language = null)
    {
        $text = TextOptimizer::optimizeForTTS($text);
        $text = TextOptimizer::naturalizeText($text);
        $language = $language ?: ($this->ttsSettings['default_language'] ?? 'tr-TR');
        $priority = $this->ttsSettings['engine_priority'] ?? ['windows', 'ema', 'google', 'azure'];
        $errors = [];

        foreach ($priority as $engine) {
            if (empty($this->ttsSettings['engines'][$engine]['enabled'])) {
                continue;
            }

            try {
                if ($engine === 'windows') {
                    $voice = $this->ttsSettings['engines']['windows']['voices'][$language] ?? '';
                    $this->textToSpeechWindows($text, $outputFile, $language, $voice);
                } elseif ($engine === 'ema') {
                    $this->emaTTS->textToSpeech($text, $outputFile, $language);
                } elseif ($engine === 'google') {
                    $secrets = (new TTSSecrets())->getAll();
                    $googleVoice = $this->ttsSettings['engines']['google']['voices'][$language] ?? '';
                    $this->googleTTS = new GoogleTTS($secrets['google_api_key'], $googleVoice);
                    $mp3File = preg_replace('/\.wav$/i', '.mp3', $outputFile);
                    $googleLanguage = $language === 'en-US' ? 'en' : 'tr';
                    if (!$this->googleTTS->textToSpeech($text, $mp3File, $googleLanguage)) {
                        throw new RuntimeException('Google TTS ses üretemedi.');
                    }
                    $this->googleTTS->convertToWav($mp3File, $outputFile);
                    @unlink($mp3File);
                } elseif ($engine === 'azure') {
                    $voice = $this->ttsSettings['engines']['azure']['voices'][$language] ?? null;
                    if (!$this->azureTTS->textToSpeech($text, $outputFile, $voice)) {
                        throw new RuntimeException('Azure TTS ses üretemedi.');
                    }
                } else {
                    continue;
                }

                if (is_file($outputFile) && filesize($outputFile) > 0) {
                    error_log("✅ " . strtoupper($engine) . " TTS kullanıldı: " . basename($outputFile));
                    return $outputFile;
                }

                throw new RuntimeException('Çıkış ses dosyası oluşmadı.');
            } catch (Throwable $e) {
                @unlink($outputFile);
                $errors[] = strtoupper($engine) . ': ' . $e->getMessage();
                error_log("⚠️ " . end($errors));
            }
        }

        throw new RuntimeException('Hiçbir TTS motoru çalışmadı: ' . implode(' | ', $errors));
    }
    
    /**
     * Windows SAPI ile metni sese çevir (yedek yöntem)
     */
    private function textToSpeechWindows($text, $outputFile, $language = 'tr-TR', $voiceName = '')
    {
        $psScript = $this->tempPath . "tts_" . uniqid() . ".ps1";
        $textFile = $this->tempPath . "tts_text_" . uniqid() . ".txt";
        file_put_contents($textFile, $text, LOCK_EX);

        $psContent = <<<'POWERSHELL'
param($textFile, $outputPath, $voiceName, $culture)
Add-Type -AssemblyName System.speech
$synthesizer = New-Object System.Speech.Synthesis.SpeechSynthesizer

if ($voiceName) {
    $selected = $synthesizer.GetInstalledVoices() |
        Where-Object { $_.VoiceInfo.Name -eq $voiceName } |
        Select-Object -First 1
    if ($selected) {
        $synthesizer.SelectVoice($selected.VoiceInfo.Name)
    }
} else {
    $selected = $synthesizer.GetInstalledVoices() |
        Where-Object { $_.VoiceInfo.Culture.Name -eq $culture } |
        Select-Object -First 1
    if ($selected) {
        $synthesizer.SelectVoice($selected.VoiceInfo.Name)
    }
}

$synthesizer.Rate = 0
$synthesizer.Volume = 100
$synthesizer.SetOutputToWaveFile($outputPath)
$text = [System.IO.File]::ReadAllText($textFile, [System.Text.Encoding]::UTF8)
$synthesizer.Speak($text)
$synthesizer.Dispose()
POWERSHELL;
        
        file_put_contents($psScript, $psContent);
        $command = 'powershell.exe -NoProfile -ExecutionPolicy Bypass -File ' . escapeshellarg($psScript) .
            ' ' . escapeshellarg($textFile) .
            ' ' . escapeshellarg($outputFile) .
            ' ' . escapeshellarg($voiceName) .
            ' ' . escapeshellarg($language) . ' 2>&1';
        
        exec($command, $output, $returnCode);
        @unlink($psScript);
        @unlink($textFile);
        
        if ($returnCode !== 0 || !is_file($outputFile) || filesize($outputFile) === 0) {
            @unlink($outputFile);
            throw new RuntimeException('Windows SAPI hatası: ' . implode("\n", $output));
        }
        
        return $outputFile;
    }
    
    /**
     * Asterisk formatına çevir (8kHz, 16bit, Mono)
     */
    private function convertToAsteriskFormat($wavFile)
    {
        // FFmpeg kullanarak formata çevir
        $tempFile = $wavFile . ".temp.wav";
        
        // FFmpeg yolunu bul (önce XAMPP, sonra PATH)
        $ffmpegPaths = [
            'D:\xampp\php\ffmpeg.exe',
            'C:\xampp\php\ffmpeg.exe',
            'D:\ffmpeg\bin\ffmpeg.exe',
            'C:\ffmpeg\bin\ffmpeg.exe',
            'ffmpeg' // PATH'te varsa
        ];
        
        $ffmpegPath = null;
        foreach ($ffmpegPaths as $path) {
            if ($path === 'ffmpeg') {
                // PATH'te var mı kontrol et
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
            throw new Exception("FFmpeg bulunamadı. Lütfen D:\\xampp\\php\\ffmpeg.exe yoluna kurun.");
        }
        
        // FFmpeg komutu: 8000 Hz, Mono, 16-bit PCM
        $command = "\"{$ffmpegPath}\" -i \"{$wavFile}\" -ar 8000 -ac 1 -acodec pcm_s16le -y \"{$tempFile}\" 2>&1";
        exec($command, $output, $returnCode);
        
        if (file_exists($tempFile) && filesize($tempFile) > 0) {
            // Orijinal dosyayı değiştir
            @unlink($wavFile);
            rename($tempFile, $wavFile);
        } else {
            // FFmpeg başarısız oldu, hata logla
            error_log("FFmpeg conversion failed: " . implode("\n", $output));
            throw new Exception("FFmpeg dönüştürme hatası: " . implode("\n", $output));
        }
    }
    
    /**
     * Sessizlik (boş ses) oluştur
     * @param float $duration Süre (saniye)
     * @param string $outputFile Çıkış dosyası
     */
    private function generateSilence($duration, $outputFile)
    {
        // Asterisk formatı: 8kHz, 16-bit, Mono
        $sampleRate = 8000;
        $bitsPerSample = 16;
        $numChannels = 1;
        
        $dataSize = intval($duration * $sampleRate * $numChannels * ($bitsPerSample / 8));
        $fileSize = $dataSize + 36;
        
        // WAV header oluştur
        $header = pack(
            'a4Va4a4VvvVVvva4V',
            'RIFF',
            $fileSize,
            'WAVE',
            'fmt ',
            16,
            1, // PCM
            $numChannels,
            $sampleRate,
            $sampleRate * $numChannels * ($bitsPerSample / 8),
            $numChannels * ($bitsPerSample / 8),
            $bitsPerSample,
            'data',
            $dataSize
        );
        
        // Sessiz veri (tümü 0)
        $data = str_repeat("\0", $dataSize);
        
        file_put_contents($outputFile, $header . $data);
    }
    
    /**
     * Yedek ses dosyası oluştur (TTS başarısız olursa)
     */
    private function generateFallbackAudio($outputFile)
    {
        // Basit bir WAV header oluştur (sessiz)
        $duration = 5; // saniye
        $sampleRate = 8000;
        $bitsPerSample = 16;
        $numChannels = 1;
        
        $dataSize = $duration * $sampleRate * $numChannels * ($bitsPerSample / 8);
        $fileSize = $dataSize + 36;
        
        $header = pack(
            'a4Va4a4VvvVVvva4V',
            'RIFF',
            $fileSize,
            'WAVE',
            'fmt ',
            16,
            1,
            $numChannels,
            $sampleRate,
            $sampleRate * $numChannels * ($bitsPerSample / 8),
            $numChannels * ($bitsPerSample / 8),
            $bitsPerSample,
            'data',
            $dataSize
        );
        
        $data = str_repeat("\0", $dataSize);
        
        file_put_contents($outputFile, $header . $data);
    }
    
    /**
     * Ses segmentlerini birleştirerek final ses dosyası oluştur
     */
    private function generateAudioWithSegments($templateId, $content, $adSoyad, $tutar, $metin1, $metin2, $metin3)
    {
        error_log("═══════════════════════════════════════");
        error_log("🎵 generateAudioWithSegments başlatıldı");
        error_log("📋 Template ID: $templateId");
        error_log("📊 Content: " . json_encode($content, JSON_UNESCAPED_UNICODE));
        error_log("═══════════════════════════════════════");
        
        $data = [
            'Adi' => $adSoyad,
            'Tutar' => $this->numberToText($tutar),
            'Metin1' => $metin1,
            'Metin2' => $metin2,
            'Metin3' => $metin3
        ];
        
        $segmentFiles = [];
        $segmentIndex = 0;
        
        foreach ($content as $item) {
            $segmentIndex++;
            $segmentFile = $this->tempPath . "segment_{$templateId}_{$segmentIndex}_" . uniqid() . ".wav";
            
            error_log("🔄 Segment [$segmentIndex]: Type=" . ($item['type'] ?? 'unknown') . " - " . json_encode($item, JSON_UNESCAPED_UNICODE));
            
            if ($item['type'] === 'audio') {
                // Ses dosyası segmenti - Asterisk'ten indir
                $audioFileName = $item['file'];
                if (!preg_match('/\.wav$/i', $audioFileName)) {
                    $audioFileName .= '.wav';
                }
                
                $remoteAudioPath = '/var/lib/asterisk/sounds/otomatik/' . $audioFileName;
                
                try {
                    // SSH ile ses dosyasını indir
                    $this->ssh->getFile($remoteAudioPath, $segmentFile);
                    
                    if (file_exists($segmentFile) && filesize($segmentFile) > 0) {
                        $segmentFiles[] = $segmentFile;
                    } else {
                        error_log("Ses dosyası indirilemedi: $remoteAudioPath");
                    }
                } catch (Exception $e) {
                    error_log("Ses dosyası indirme hatası: " . $e->getMessage());
                }
                
            } elseif ($item['type'] === 'silence') {
                // Sessizlik segmenti - Boş WAV oluştur
                $duration = $item['duration'] ?? 1;
                error_log("⏱️ SESSIZLIK EKLENIYOR: {$duration} saniye - Segment: $segmentFile");
                $this->generateSilence($duration, $segmentFile);
                
                if (file_exists($segmentFile) && filesize($segmentFile) > 0) {
                    $segmentFiles[] = $segmentFile;
                    error_log("✅ Sessizlik dosyası oluşturuldu: " . filesize($segmentFile) . " bytes");
                } else {
                    error_log("❌ Sessizlik dosyası oluşturulamadı!");
                }
                
            } elseif ($item['type'] === 'text') {
                // Sabit metin - TTS ile oluştur
                $text = $item['value'];
                if (!empty($text)) {
                    $this->textToSpeech($text, $segmentFile);
                    $this->convertToAsteriskFormat($segmentFile);
                    $segmentFiles[] = $segmentFile;
                }
                
            } elseif ($item['type'] === 'field') {
                // Dinamik alan - TTS ile oluştur
                $value = $data[$item['value']] ?? '';
                if (!empty($value)) {
                    $this->textToSpeech($value, $segmentFile);
                    $this->convertToAsteriskFormat($segmentFile);
                    $segmentFiles[] = $segmentFile;
                }
            }
        }
        
        if (empty($segmentFiles)) {
            throw new Exception("Hiç ses segmenti oluşturulamadı");
        }
        
        // Tüm segmentleri birleştir
        $mergedFile = $this->tempPath . "merged_{$templateId}_" . uniqid() . ".wav";
        $this->mergeAudioFiles($segmentFiles, $mergedFile);
        
        // Geçici segment dosyalarını sil
        foreach ($segmentFiles as $file) {
            @unlink($file);
        }

        return $mergedFile;
    }
    
    /**
     * Birden fazla WAV dosyasını birleştir (ffmpeg ile)
     */
    private function mergeAudioFiles($files, $outputFile)
    {
        if (empty($files)) {
            throw new Exception("Birleştirilecek dosya yok");
        }
        
        if (count($files) === 1) {
            // Tek dosya varsa, direkt kopyala
            copy($files[0], $outputFile);
            return;
        }
        
        // FFmpeg yolunu .env'den al
        $ffmpegPath = null;
        
        if (class_exists('EnvLoader')) {
            $ffmpegPath = EnvLoader::get('FFMPEG_PATH', '');
        }
        
        // .env'den okuduysa ve boş değilse kullan
        if (empty($ffmpegPath) || !file_exists($ffmpegPath)) {
            // .env'de yok veya hatalı - otomatik bul
            $tryPaths = [
                'd:\xampp\php\ffmpeg.exe',
                'c:\xampp\php\ffmpeg.exe',
                'd:\ffmpeg\bin\ffmpeg.exe',
                'c:\ffmpeg\bin\ffmpeg.exe',
                '/usr/bin/ffmpeg',
                '/usr/local/bin/ffmpeg'
            ];
            
            foreach ($tryPaths as $path) {
                if (file_exists($path)) {
                    $ffmpegPath = $path;
                    break;
                }
            }
            
            // Hala bulunamadıysa PATH'te ara
            if (empty($ffmpegPath)) {
                if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                    exec('where ffmpeg 2>nul', $whereOutput, $whereCode);
                    if ($whereCode === 0 && !empty($whereOutput[0])) {
                        $ffmpegPath = trim($whereOutput[0]);
                    }
                } else {
                    exec('which ffmpeg 2>/dev/null', $whichOutput, $whichCode);
                    if ($whichCode === 0 && !empty($whichOutput[0])) {
                        $ffmpegPath = trim($whichOutput[0]);
                    }
                }
            }
        }
        
        if (empty($ffmpegPath) || !file_exists($ffmpegPath)) {
            throw new Exception("FFmpeg bulunamadı! Lütfen .env dosyasında FFMPEG_PATH ayarlayın veya FFmpeg kurun.");
        }
        
        // Geçici dosya listesi oluştur
        $fileListPath = $this->tempPath . "filelist_" . uniqid() . ".txt";
        $fileListContent = '';
        
        foreach ($files as $file) {
            $fileListContent .= "file '" . str_replace('\\', '/', $file) . "'\n";
        }
        
        file_put_contents($fileListPath, $fileListContent);
        
        // ffmpeg ile birleştir
        $command = "\"{$ffmpegPath}\" -f concat -safe 0 -i \"{$fileListPath}\" -c copy -y \"{$outputFile}\" 2>&1";
        exec($command, $output, $returnCode);
        
        // Geçici liste dosyasını sil
        @unlink($fileListPath);
        
        if (!file_exists($outputFile) || filesize($outputFile) === 0) {
            throw new Exception("Ses dosyaları birleştirilemedi: " . implode("\n", $output));
        }
    }
    
    /**
     * Ses dosyasının hızını artır (FFmpeg atempo filtresi ile)
     * @param string $inputFile Giriş WAV dosyası
     * @param string $outputFile Çıkış WAV dosyası
     * @param float $speed Hız çarpanı (1.0 = normal, 1.1 = %10 hızlı, 1.2 = %20 hızlı)
     */
    private function speedUpAudio($inputFile, $outputFile, $speed = 1.10)
    {
        // FFmpeg yolunu .env'den al (mergeAudioFiles ile aynı mantık)
        $ffmpegPath = null;
        
        if (class_exists('EnvLoader')) {
            $ffmpegPath = EnvLoader::get('FFMPEG_PATH', '');
        }
        
        if (empty($ffmpegPath) || !file_exists($ffmpegPath)) {
            $tryPaths = [
                'd:\xampp\php\ffmpeg.exe',
                'c:\xampp\php\ffmpeg.exe',
                'd:\ffmpeg\bin\ffmpeg.exe',
                'c:\ffmpeg\bin\ffmpeg.exe',
                '/usr/bin/ffmpeg',
                '/usr/local/bin/ffmpeg'
            ];
            
            foreach ($tryPaths as $path) {
                if (file_exists($path)) {
                    $ffmpegPath = $path;
                    break;
                }
            }
        }
        
        if (empty($ffmpegPath) || !file_exists($ffmpegPath)) {
            // FFmpeg bulunamadı - hızlandırma yapma, orijinal dosyayı kullan
            copy($inputFile, $outputFile);
            return;
        }
        
        // atempo filtresi ile hızlandır (pitch değiştirmeden)
        // atempo: 0.5-2.0 arası değer alır
        // 1.10 = %10 daha hızlı ama ses perdesi aynı kalır
        $command = "\"{$ffmpegPath}\" -i \"{$inputFile}\" -filter:a \"atempo={$speed}\" -y \"{$outputFile}\" 2>&1";
        exec($command, $output, $returnCode);
        
        if (!file_exists($outputFile) || filesize($outputFile) === 0) {
            // Hata oldu - orijinal dosyayı kullan
            copy($inputFile, $outputFile);
        }
    }
    
    /**
     * Menü seçenekleri için ses dosyaları oluştur
     */
    public function generateMenuAudios()
    {
        $menus = [
            'welcome' => 'Merhaba, otomatik arama sistemine hoş geldiniz.',
            'menu_main' => 'Tekrar dinlemek için 1, operatöre bağlanmak için 2 tuşuna basınız.',
            'transfer' => 'Operatöre aktarılıyorsunuz, lütfen bekleyiniz.',
            'goodbye' => 'İyi günler dileriz.',
            'invalid' => 'Geçersiz tuş. Lütfen tekrar deneyiniz.'
        ];
        
        foreach ($menus as $name => $text) {
            $audioFile = $this->audioBasePath . $name . '.wav';
            if (!file_exists($audioFile)) {
                $this->textToSpeech($text, $audioFile);
                $this->convertToAsteriskFormat($audioFile);
            }
        }
    }
    
    /**
     * Ses dosyasını sil
     */
    public function deleteAudioFile($callId)
    {
        $audioFile = $this->audioBasePath . "call_{$callId}.wav";
        if (file_exists($audioFile)) {
            @unlink($audioFile);
        }
    }
}
