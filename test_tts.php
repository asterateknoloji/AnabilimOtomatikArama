<?php
/**
 * Windows TTS Test
 */

echo "=== WINDOWS TTS TEST ===\n\n";

// 1. TTS Seslerini Listele
echo "1️⃣ TTS SESLERİ:\n";
$psCommand = 'Add-Type -AssemblyName System.Speech; $synth = New-Object System.Speech.Synthesis.SpeechSynthesizer; $synth.GetInstalledVoices() | ForEach-Object { $_.VoiceInfo.Name }';
$output = shell_exec("powershell -Command \"{$psCommand}\"");
echo $output . "\n";

// 2. Test Mesajı Oluştur
echo "2️⃣ TEST SES DOSYASI OLUŞTURULUYOR...\n";

$testText = "Merhaba, bu bir test mesajıdır. Windows TTS çalışıyor.";
$outputFile = __DIR__ . '/tmp/tts_test_' . time() . '.wav';

// Klasör yoksa oluştur
if (!is_dir(__DIR__ . '/tmp')) {
    mkdir(__DIR__ . '/tmp', 0777, true);
}

$psScript = <<<'PS'
param($text, $output)
Add-Type -AssemblyName System.Speech
$synth = New-Object System.Speech.Synthesis.SpeechSynthesizer
$synth.Rate = 1
$synth.Volume = 100
$synth.SetOutputToWaveFile($output)
$synth.Speak($text)
$synth.Dispose()
PS;

$tempPs1 = __DIR__ . '/tmp/tts_test.ps1';
file_put_contents($tempPs1, $psScript);

$command = "powershell -ExecutionPolicy Bypass -File \"{$tempPs1}\" -text \"{$testText}\" -output \"{$outputFile}\"";
exec($command, $output, $returnCode);

if (file_exists($outputFile)) {
    $fileSize = filesize($outputFile);
    echo "   ✅ Ses dosyası oluşturuldu!\n";
    echo "   📁 Dosya: {$outputFile}\n";
    echo "   📊 Boyut: " . round($fileSize / 1024, 2) . " KB\n\n";
    
    echo "3️⃣ Dosyayı oynatmak için:\n";
    echo "   powershell -Command \"(New-Object Media.SoundPlayer '{$outputFile}').PlaySync()\"\n\n";
} else {
    echo "   ❌ Ses dosyası oluşturulamadı!\n";
    echo "   Hata kodu: {$returnCode}\n\n";
}

// Temizlik
if (file_exists($tempPs1)) {
    unlink($tempPs1);
}

echo "=== TEST TAMAMLANDI ===\n";
