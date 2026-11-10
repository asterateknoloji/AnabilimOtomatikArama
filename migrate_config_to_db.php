<?php
/**
 * Config.php → Database Migration
 * Mevcut config.php'deki bilgileri veritabanına şifreli olarak aktarır
 */

require_once __DIR__ . '/includes/ConfigManager.php';
require_once __DIR__ . '/includes/Encryption.php';

echo "=== CONFIG.PHP → DATABASE MIGRATION ===\n\n";

try {
    // PDO bağlantısı oluştur
    $pdo = ConfigManager::createPDO();
    $configManager = new ConfigManager($pdo);
    
    // Config.php'deki değerler
    $configData = array(
        array(
            'key' => 'db_host',
            'value' => '192.168.181.16',
            'group' => 'database',
            'encrypt' => true,
            'description' => 'Veritabanı sunucu adresi'
        ),
        array(
            'key' => 'db_name',
            'value' => 'ANABILIM',
            'group' => 'database',
            'encrypt' => true,
            'description' => 'Veritabanı adı'
        ),
        array(
            'key' => 'db_user',
            'value' => 'astera',
            'group' => 'database',
            'encrypt' => true,
            'description' => 'Veritabanı kullanıcı adı'
        ),
        array(
            'key' => 'db_pass',
            'value' => 'astera2012',
            'group' => 'database',
            'encrypt' => true,
            'description' => 'Veritabanı şifresi'
        ),
        array(
            'key' => 'ssh_host',
            'value' => '192.168.181.16',
            'group' => 'ssh',
            'encrypt' => true,
            'description' => 'SSH sunucu adresi'
        ),
        array(
            'key' => 'ssh_port',
            'value' => '45555',
            'group' => 'ssh',
            'encrypt' => false,
            'description' => 'SSH port'
        ),
        array(
            'key' => 'ssh_user',
            'value' => 'root',
            'group' => 'ssh',
            'encrypt' => true,
            'description' => 'SSH kullanıcı adı'
        ),
        array(
            'key' => 'ssh_pass',
            'value' => 'KKAstera*2o21@.!',
            'group' => 'ssh',
            'encrypt' => true,
            'description' => 'SSH şifresi'
        ),
        array(
            'key' => 'ami_host',
            'value' => '192.168.181.16',
            'group' => 'asterisk',
            'encrypt' => true,
            'description' => 'AMI sunucu adresi'
        ),
        array(
            'key' => 'ami_port',
            'value' => '5038',
            'group' => 'asterisk',
            'encrypt' => false,
            'description' => 'AMI port'
        ),
        array(
            'key' => 'ami_user',
            'value' => 'astera',
            'group' => 'asterisk',
            'encrypt' => true,
            'description' => 'AMI kullanıcı adı'
        ),
        array(
            'key' => 'ami_pass',
            'value' => 'astera2012',
            'group' => 'asterisk',
            'encrypt' => true,
            'description' => 'AMI şifresi'
        ),
        array(
            'key' => 'trunk_name',
            'value' => '902122130686',
            'group' => 'asterisk',
            'encrypt' => false,
            'description' => 'SIP trunk adı'
        ),
        array(
            'key' => 'caller_id',
            'value' => '02129999999',
            'group' => 'asterisk',
            'encrypt' => false,
            'description' => 'Arayan numara'
        ),
        array(
            'key' => 'spool_path',
            'value' => '/var/spool/asterisk/outgoing/',
            'group' => 'asterisk',
            'encrypt' => false,
            'description' => 'Asterisk spool klasörü'
        ),
        array(
            'key' => 'sounds_path',
            'value' => '/var/lib/asterisk/sounds/custom/',
            'group' => 'asterisk',
            'encrypt' => false,
            'description' => 'Asterisk ses dosyaları klasörü'
        ),
        array(
            'key' => 'ffmpeg_path',
            'value' => 'd:/xampp/php/ffmpeg.exe',
            'group' => 'system',
            'encrypt' => false,
            'description' => 'FFmpeg yolu'
        )
    );
    
    // Veritabanına aktar
    $success = 0;
    $failed = 0;
    
    foreach ($configData as $config) {
        echo "Aktarılıyor: {$config['key']} ({$config['group']})... ";
        
        if ($configManager->set(
            $config['key'],
            $config['value'],
            $config['group'],
            $config['encrypt'],
            $config['description'],
            1 // Superadmin user_id
        )) {
            echo "✅ Başarılı" . ($config['encrypt'] ? " (Şifreli)" : "") . "\n";
            $success++;
        } else {
            echo "❌ HATA!\n";
            $failed++;
        }
    }
    
    echo "\n=== ÖZET ===\n";
    echo "✅ Başarılı: {$success}\n";
    echo "❌ Başarısız: {$failed}\n";
    echo "📊 Toplam: " . ($success + $failed) . "\n\n";
    
    // Test: Verileri geri oku
    echo "=== TEST: ŞİFRELİ VERİ OKUMA ===\n";
    echo "db_pass (şifreli): " . $configManager->get('db_pass') . "\n";
    echo "ssh_pass (şifreli): " . $configManager->get('ssh_pass') . "\n";
    echo "trunk_name (düz metin): " . $configManager->get('trunk_name') . "\n";
    
    echo "\n✅ Migration tamamlandı!\n";
    
} catch (Exception $e) {
    echo "❌ Kritik Hata: " . $e->getMessage() . "\n";
    exit(1);
}
