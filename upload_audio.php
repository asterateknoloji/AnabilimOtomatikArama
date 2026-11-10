<?php
require_once 'includes/check_auth.php';
require_once 'config.php';
require_once 'includes/SshHelper.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if (!isset($_FILES['audio_file'])) {
        throw new Exception('Dosya yüklenmedi');
    }
    
    $file = $_FILES['audio_file'];
    
    // Dosya kontrolü
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Dosya yükleme hatası');
    }
    
    $allowedExtensions = ['wav', 'mp3'];
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($fileExtension, $allowedExtensions)) {
        throw new Exception('Sadece WAV ve MP3 dosyaları kabul edilir');
    }
    
    // Dosya adını temizle ve orijinal adı sakla
    $originalFileName = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($file['name'], PATHINFO_FILENAME));
    $fileName = $originalFileName . '.' . $fileExtension;
    
    // Geçici klasöre kaydet
    $tempDir = __DIR__ . '/temp/audio/';
    if (!file_exists($tempDir)) {
        mkdir($tempDir, 0777, true);
    }
    
    $tempFile = $tempDir . $fileName;
    
    if (!move_uploaded_file($file['tmp_name'], $tempFile)) {
        throw new Exception('Dosya kaydedilemedi');
    }
    
    // FFmpeg yolunu belirle
    $ffmpegPath = defined('FFMPEG_PATH') ? FFMPEG_PATH : 'ffmpeg';
    
    // FFmpeg var mı kontrol et
    exec($ffmpegPath . " -version 2>&1", $versionOutput, $versionCode);
    $hasFfmpeg = ($versionCode === 0);
    
    if (!$hasFfmpeg) {
        // FFmpeg yoksa, sadece WAV kabul et
        if ($fileExtension !== 'wav') {
            throw new Exception('FFmpeg bulunamadı. Lütfen sadece WAV dosyası yükleyin veya sistem yöneticisine başvurun.');
        }
        
        // WAV dosyasını olduğu gibi kullan
        $asteriskFile = $tempFile;
    } else {
        // MP3 ise WAV'a çevir
        if ($fileExtension === 'mp3') {
            $wavFile = $tempDir . pathinfo($fileName, PATHINFO_FILENAME) . '.wav';
            
            // ffmpeg ile dönüştür
            $cmd = $ffmpegPath . " -y -i " . escapeshellarg($tempFile) . " -acodec pcm_s16le -ar 8000 -ac 1 " . escapeshellarg($wavFile) . " 2>&1";
            exec($cmd, $output, $returnCode);
            
            if ($returnCode !== 0) {
                throw new Exception('MP3 WAV\'a dönüştürülemedi: ' . implode("\n", $output));
            }
            
            @unlink($tempFile);
            $tempFile = $wavFile;
            $fileName = basename($wavFile);
        }
        
        // WAV formatını kontrol et ve Asterisk formatına çevir (8kHz, 16bit, Mono)
        $convertedFile = $tempDir . 'converted_' . $fileName;
        $cmd = $ffmpegPath . " -y -i " . escapeshellarg($tempFile) . " -acodec pcm_s16le -ar 8000 -ac 1 " . escapeshellarg($convertedFile) . " 2>&1";
        exec($cmd, $output2, $returnCode2);
        
        if ($returnCode2 !== 0) {
            // Dönüştürme başarısız olursa, orijinal dosyayı kullan
            error_log('FFmpeg dönüştürme hatası: ' . implode("\n", $output2));
            $asteriskFile = $tempFile;
        } else {
            @unlink($tempFile);
            $asteriskFile = $convertedFile;
        }
    }
    
    $tempFile = $asteriskFile;
    
    // Orijinal dosya adını kullan (converted_ veya asterisk_ öneki olmadan)
    if ($fileExtension === 'mp3') {
        $finalFileName = $originalFileName . '.wav'; // MP3'ten dönüştürüldüyse .wav olarak kaydet
    } else {
        $finalFileName = $fileName; // Zaten WAV ise orijinal adı kullan
    }
    
    // SSH ile Asterisk sunucusuna gönder
    $ssh = new SshHelper(
        ASTERISK_SSH_HOST,
        ASTERISK_SSH_PORT,
        ASTERISK_SSH_USER,
        ASTERISK_SSH_PASS
    );
    
    $remotePath = '/var/lib/asterisk/sounds/otomatik/' . $finalFileName;
    $ssh->sendFile($tempFile, $remotePath);
    
    // Yerel dosyayı sil
    @unlink($tempFile);
    
    echo json_encode([
        'success' => true,
        'message' => 'Ses dosyası başarıyla yüklendi',
        'fileName' => $finalFileName,
        'remotePath' => $remotePath
    ]);
    
} catch (Exception $e) {
    // Hata logla
    error_log('Upload Audio Error: ' . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => [
            'ffmpeg_path' => defined('FFMPEG_PATH') ? FFMPEG_PATH : 'not defined',
            'temp_dir' => isset($tempDir) ? $tempDir : 'not set',
            'php_version' => PHP_VERSION
        ]
    ]);
}
