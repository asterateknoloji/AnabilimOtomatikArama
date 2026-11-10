<?php
/**
 * Menü Ses Dosyalarını Oluştur
 * Sistem için gerekli sabit ses dosyalarını oluşturur
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/WindowsTTS.php';

echo "=== Menü Ses Dosyaları Oluşturuluyor ===\n\n";

$tts = new WindowsTTS();

try {
    echo "Menü sesleri oluşturuluyor...\n";
    $tts->generateMenuAudios();
    echo "✓ Menü sesleri oluşturuldu\n\n";
    
    echo "Oluşturulan dosyalar:\n";
    $audioPath = __DIR__ . '/asterisk/audio/calls/';
    
    $files = [
        'welcome.wav' => 'Hoş geldiniz mesajı',
        'menu_main.wav' => 'Ana menü',
        'transfer.wav' => 'Operatöre aktarılıyor',
        'goodbye.wav' => 'Hoşça kalın',
        'invalid.wav' => 'Geçersiz tuş'
    ];
    
    foreach ($files as $file => $desc) {
        $fullPath = $audioPath . $file;
        if (file_exists($fullPath)) {
            $size = filesize($fullPath);
            echo "  ✓ {$file} ({$desc}) - " . number_format($size / 1024, 2) . " KB\n";
        } else {
            echo "  ✗ {$file} - OLUŞTURULAMADI\n";
        }
    }
    
    echo "\n=== Tamamlandı ===\n";
    echo "Ses dosyaları Asterisk sounds dizinine kopyalanabilir:\n";
    echo "copy asterisk\\audio\\calls\\*.wav \"C:\\Program Files\\Asterisk\\var\\lib\\asterisk\\sounds\\custom\\\"\n";
    
} catch (Exception $e) {
    echo "HATA: " . $e->getMessage() . "\n";
    exit(1);
}
