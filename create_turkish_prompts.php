<?php
/**
 * Türkçe Sistem Seslerini Oluştur
 * Windows TTS ile Türkçe prompt'lar oluşturur
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/WindowsTTS.php';

echo "========================================\n";
echo "Türkçe Sistem Sesleri Oluşturuluyor\n";
echo "========================================\n\n";

$tts = new WindowsTTS($pdo);

// Türkçe mesajlar
$prompts = [
    'transfer' => 'Lütfen bekleyiniz, operatöre aktarılıyorsunuz.',
    'goodbye' => 'İyi günler dileriz.',
    'invalid' => 'Geçersiz tuşlama yaptınız. Lütfen tekrar deneyiniz.',
    'queue-thankyou' => 'Bizi aradığınız için teşekkür ederiz.',
    'queue-holdtime' => 'Tahmini bekleme süreniz, 2 dakikadır.',
    'agent-connecting' => 'Bağlanıyorsunuz, lütfen bekleyiniz.',
];

$successCount = 0;
$failCount = 0;

foreach ($prompts as $filename => $text) {
    echo "İşleniyor: {$filename}.wav\n";
    echo "Metin: {$text}\n";
    
    try {
        // TTS ile ses oluştur
        $wavFile = __DIR__ . "/temp/{$filename}.wav";
        
        // Windows TTS komutu
        $vbsScript = __DIR__ . "/temp/tts_{$filename}.vbs";
        $vbsContent = <<<VBS
Dim oFileSystem, oFile
Dim oVoice

Set oFileSystem = CreateObject("Scripting.FileSystemObject")
Set oVoice = CreateObject("SAPI.SpVoice")

' Türkçe ses seç
For Each voice In oVoice.GetVoices()
    If InStr(voice.GetDescription(), "Turkish") > 0 Or InStr(voice.GetDescription(), "Tolga") > 0 Then
        Set oVoice.Voice = voice
        Exit For
    End If
Next

' Ses ayarları
oVoice.Rate = 0
oVoice.Volume = 100

' Dosya stream oluştur
Dim oStream
Set oStream = CreateObject("SAPI.SpFileStream")
oStream.Format.Type = 39 ' SAFT22kHz16BitMono
oStream.Open "{$wavFile}", 3 ' SSFMCreateForWrite

Set oVoice.AudioOutputStream = oStream
oVoice.Speak "{$text}", 0
oStream.Close

Set oStream = Nothing
Set oVoice = Nothing
Set oFileSystem = Nothing
VBS;
        file_put_contents($vbsScript, $vbsContent);
        
        // VBS çalıştır
        exec("cscript //Nologo \"{$vbsScript}\"", $output, $returnVar);
        
        if (!file_exists($wavFile)) {
            throw new Exception("WAV dosyası oluşturulamadı");
        }
        
        // GSM'e dönüştür (FFmpeg)
        $gsmFile = __DIR__ . "/temp/{$filename}.gsm";
        $ffmpegPath = "D:\\xampp\\php\\ffmpeg.exe";
        $convertCmd = "\"{$ffmpegPath}\" -i \"{$wavFile}\" -ar 8000 -ac 1 -ab 13k -f gsm -y \"{$gsmFile}\" 2>&1";
        
        exec($convertCmd, $convertOutput, $convertReturn);
        
        if (!file_exists($gsmFile)) {
            throw new Exception("GSM dönüştürme hatası: " . implode("\n", $convertOutput));
        }
        
        // Geçici dosyaları temizle
        @unlink($vbsScript);
        @unlink($wavFile);
        
        echo "  ✅ Oluşturuldu: {$gsmFile}\n";
        $successCount++;
        
    } catch (Exception $e) {
        echo "  ❌ Hata: " . $e->getMessage() . "\n";
        $failCount++;
    }
    
    echo "\n";
}

echo "========================================\n";
echo "Özet:\n";
echo "  ✅ Başarılı: {$successCount}\n";
echo "  ❌ Başarısız: {$failCount}\n";
echo "========================================\n\n";

if ($successCount > 0) {
    echo "Sonraki adım:\n";
    echo "upload_turkish_prompts.bat dosyasını çalıştırarak\n";
    echo "bu ses dosyalarını Asterisk sunucusuna yükleyin.\n";
}
