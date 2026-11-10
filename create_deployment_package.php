<?php
/**
 * TAŞINABİLİR PROJE PAKETİ OLUŞTURUCU
 * 
 * Bu script:
 * 1. Tüm proje dosyalarını OtomatikAramason klasörüne kopyalar
 * 2. ANABILIM veritabanı dump'ını oluşturur
 * 3. Kurulum scriptlerini hazırlar
 * 4. Dokümantasyon ekler
 */

header('Content-Type: text/html; charset=utf-8');

$sourceDir = __DIR__;
$targetDir = 'D:\xampp\htdocs\OtomatikAramason';
$mysqlPath = 'D:\xampp\mysql\bin\mysqldump.exe';

echo "<!DOCTYPE html>
<html lang='tr'>
<head>
    <meta charset='UTF-8'>
    <title>Taşınabilir Paket Oluşturucu</title>
    <style>
        body { font-family: 'Courier New', monospace; background: #1e1e1e; color: #d4d4d4; padding: 20px; line-height: 1.8; }
        .container { max-width: 1400px; margin: 0 auto; background: #252526; padding: 40px; border-radius: 10px; }
        h1 { color: #4ec9b0; border-bottom: 3px solid #4ec9b0; padding-bottom: 15px; font-size: 28px; }
        h2 { color: #569cd6; margin-top: 30px; font-size: 22px; }
        .success { color: #4ec9b0; font-weight: bold; }
        .error { color: #f48771; font-weight: bold; }
        .warning { color: #dcdcaa; }
        .info { color: #9cdcfe; }
        pre { background: #1e1e1e; padding: 20px; border-radius: 8px; overflow-x: auto; border-left: 4px solid #4ec9b0; font-size: 13px; }
        .box { background: #2d2d30; padding: 25px; margin: 20px 0; border-radius: 8px; border-left: 5px solid #569cd6; }
        .progress { background: #3e3e42; height: 30px; border-radius: 15px; overflow: hidden; margin: 10px 0; }
        .progress-bar { background: linear-gradient(90deg, #4ec9b0, #569cd6); height: 100%; line-height: 30px; text-align: center; color: white; font-weight: bold; transition: width 0.3s; }
        .file-list { max-height: 400px; overflow-y: auto; background: #1e1e1e; padding: 15px; border-radius: 5px; font-size: 12px; }
        ul { line-height: 2; }
        .btn { display: inline-block; background: #0e639c; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; margin: 10px 5px; font-weight: bold; }
        .btn:hover { background: #1177bb; }
    </style>
</head>
<body>
<div class='container'>";

echo "<h1>📦 TAŞINABİLİR PROJE PAKETİ OLUŞTURUCU</h1>";
echo "<p class='info'>⏰ " . date('Y-m-d H:i:s') . "</p>";
echo "<hr>";

// Kopyalanmayacak dosyalar/klasörler
$excludeFiles = [
    'create_deployment_package.php',
    'fix_users_management_encoding.php',
    'fix_user_names_encoding.php',
    'fix_user_names_direct.php',
    'fix_users_final.php',
    'check_users_table.php',
    'fix_database_encoding.php',
    'debug_ip.php',
    '.git',
    '.gitignore',
    'node_modules',
    'vendor/.git'
];

$excludeDirs = [
    'uploads',
    'audio_files'
];

try {
    // 1. Kaynak klasörü kontrol
    echo "<div class='box'>";
    echo "<h2>📁 1. KAYNAK KLASÖR KONTROLÜ</h2>";
    echo "<p><strong>Kaynak:</strong> <span class='info'>$sourceDir</span></p>";
    echo "<p><strong>Hedef:</strong> <span class='info'>$targetDir</span></p>";
    
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
        echo "<p class='success'>✅ Hedef klasör oluşturuldu</p>";
    } else {
        echo "<p class='warning'>⚠️ Hedef klasör zaten mevcut</p>";
    }
    echo "</div>";
    
    // 2. Veritabanı Dump
    echo "<div class='box'>";
    echo "<h2>💾 2. VERİTABANI DUMP OLUŞTURULUYOR</h2>";
    
    require_once $sourceDir . '/config.php';
    
    $dumpFile = $targetDir . '/database_backup.sql';
    $dumpCmd = sprintf(
        '"%s" -h %s -u %s -p%s --default-character-set=utf8mb4 --single-transaction --routines --triggers %s > "%s" 2>&1',
        $mysqlPath,
        DB_HOST,
        DB_USER,
        DB_PASS,
        DB_NAME,
        $dumpFile
    );
    
    echo "<pre class='info'>mysqldump -h " . DB_HOST . " -u " . DB_USER . " " . DB_NAME . " > database_backup.sql</pre>";
    
    exec($dumpCmd, $output, $returnVar);
    
    if ($returnVar === 0 && file_exists($dumpFile)) {
        $size = filesize($dumpFile);
        $sizeFormatted = number_format($size / 1024, 2) . ' KB';
        echo "<p class='success'>✅ Database dump oluşturuldu: $sizeFormatted</p>";
    } else {
        echo "<p class='error'>❌ Database dump oluşturulamadı</p>";
        echo "<pre>" . implode("\n", $output) . "</pre>";
    }
    echo "</div>";
    
    // 3. Dosyaları kopyala
    echo "<div class='box'>";
    echo "<h2>📋 3. DOSYALAR KOPYALANIYOR</h2>";
    
    function copyDirectory($src, $dst, $exclude = [], $excludeDirs = []) {
        $copied = 0;
        $skipped = 0;
        
        if (!is_dir($dst)) {
            mkdir($dst, 0777, true);
        }
        
        $dir = opendir($src);
        while (($file = readdir($dir)) !== false) {
            if ($file == '.' || $file == '..') continue;
            
            $srcPath = $src . '/' . $file;
            $dstPath = $dst . '/' . $file;
            
            // Hariç tutulacakları kontrol et
            if (in_array($file, $exclude)) {
                $skipped++;
                continue;
            }
            
            if (is_dir($srcPath)) {
                if (in_array($file, $excludeDirs)) {
                    // Boş klasör oluştur
                    mkdir($dstPath, 0777, true);
                    $skipped++;
                } else {
                    list($c, $s) = copyDirectory($srcPath, $dstPath, $exclude, $excludeDirs);
                    $copied += $c;
                    $skipped += $s;
                }
            } else {
                copy($srcPath, $dstPath);
                $copied++;
            }
        }
        closedir($dir);
        
        return [$copied, $skipped];
    }
    
    list($copiedCount, $skippedCount) = copyDirectory($sourceDir, $targetDir, $excludeFiles, $excludeDirs);
    
    echo "<p class='success'>✅ Kopyalanan dosya: <strong>$copiedCount</strong></p>";
    echo "<p class='warning'>⚠️ Atlanan dosya: <strong>$skippedCount</strong></p>";
    echo "</div>";
    
    // 4. Kurulum scriptleri oluştur
    echo "<div class='box'>";
    echo "<h2>🔧 4. KURULUM SCRİPTLERİ OLUŞTURULUYOR</h2>";
    
    $scriptsCreated = 0;
    
    // KURULUM_README.txt
    $readmeContent = "═══════════════════════════════════════════════════════════════
    OTOMATİK ARAMA SİSTEMİ - KURULUM REHBERİ
═══════════════════════════════════════════════════════════════

📦 Bu paket içeriği:
   • Tüm proje dosyaları
   • ANABILIM veritabanı dump'ı (database_backup.sql)
   • Kurulum scriptleri
   • Dokümantasyon

🚀 HIZLI KURULUM:

1. Bu klasörü istediğiniz sunucuya kopyalayın:
   Örnek: /var/www/html/OtomatikArama veya C:\\xampp\\htdocs\\OtomatikArama

2. .env dosyasını düzenleyin:
   - Veritabanı bilgilerini girin
   - Asterisk SSH bilgilerini girin
   - Encryption key oluşturun

3. Veritabanını import edin:
   mysql -u root -p ANABILIM < database_backup.sql

4. Web sunucusunu başlatın:
   - Apache/Nginx
   - PHP 7.4+
   - MySQL 5.5+

5. Tarayıcıda açın:
   http://localhost/OtomatikArama/

6. İlk giriş:
   Kullanıcı: superadmin
   Şifre: database_backup.sql'den kontrol edin

═══════════════════════════════════════════════════════════════
    DETAYLI KURULUM DOKÜMANTASYONU
═══════════════════════════════════════════════════════════════

📋 GEREKSİNİMLER:
• PHP 7.4 veya üzeri
• MySQL 5.5 veya üzeri (MariaDB de olur)
• Apache veya Nginx
• Asterisk PBX (opsiyonel - sadece arama fonksiyonu için)
• Composer (vendor klasörü dahil, ama güncelleme için gerekebilir)

🔧 ADIM ADIM KURULUM:

1. DOSYALARI KOPYALA:
   Windows (XAMPP):
   xcopy /E /I /Y \"OtomatikAramason\" \"C:\\xampp\\htdocs\\OtomatikArama\"
   
   Linux:
   cp -r OtomatikAramason /var/www/html/OtomatikArama
   chmod -R 755 /var/www/html/OtomatikArama
   chown -R www-data:www-data /var/www/html/OtomatikArama

2. VERİTABANI OLUŞTUR:
   mysql -u root -p
   CREATE DATABASE ANABILIM CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   exit;
   
   mysql -u root -p ANABILIM < database_backup.sql

3. .env DOSYASI OLUŞTUR:
   .env.example dosyasını .env olarak kopyala
   nano .env (veya notepad .env)
   
   Düzenle:
   DB_HOST=localhost
   DB_NAME=ANABILIM
   DB_USER=root
   DB_PASS=
   
   ENCRYPTION_KEY=<32 karakterlik rastgele string>
   
   ASTERISK_SSH_HOST=<asterisk sunucu IP>
   ASTERISK_SSH_PORT=22
   ASTERISK_SSH_USER=root
   ASTERISK_SSH_PASS=<şifre>

4. KLASÖR İZİNLERİ (Linux):
   chmod 755 /var/www/html/OtomatikArama
   chmod 777 /var/www/html/OtomatikArama/uploads
   chmod 777 /var/www/html/OtomatikArama/audio_files
   chmod 600 /var/www/html/OtomatikArama/.env

5. APACHE YAPILANDIRMASI (Opsiyonel):
   <VirtualHost *:80>
       ServerName otomatikarama.local
       DocumentRoot /var/www/html/OtomatikArama
       
       <Directory /var/www/html/OtomatikArama>
           AllowOverride All
           Require all granted
       </Directory>
   </VirtualHost>

6. TEST:
   http://localhost/OtomatikArama/login.php
   
   İlk giriş için veritabanında ki kullanıcı bilgilerini kullanın.

═══════════════════════════════════════════════════════════════
    SORUN GİDERME
═══════════════════════════════════════════════════════════════

❌ \"Database connection failed\":
   → config.php veya .env dosyasındaki DB bilgilerini kontrol edin
   → MySQL servisinin çalıştığından emin olun

❌ \"Permission denied\":
   → uploads ve audio_files klasörlerine yazma izni verin
   → Linux: chmod 777 uploads audio_files

❌ \"Composer autoload not found\":
   → composer install komutunu çalıştırın
   → vendor klasörü eksikse

❌ Türkçe karakterler bozuk:
   → Veritabanı charset: utf8mb4
   → PHP: header('Content-Type: text/html; charset=utf-8');
   → MySQL: SET NAMES utf8mb4

❌ Asterisk bağlantı hatası:
   → SSH bilgilerini kontrol edin
   → Asterisk sunucusunun erişilebilir olduğundan emin olun
   → phpseclib kütüphanesinin kurulu olduğunu kontrol edin

═══════════════════════════════════════════════════════════════
    ÖNEMLİ NOTLAR
═══════════════════════════════════════════════════════════════

🔐 GÜVENLİK:
• .env dosyasını mutlaka düzenleyin
• ENCRYPTION_KEY'i değiştirin
• Production'da display_errors=Off yapın
• Güçlü şifreler kullanın

📁 YEDEKLEDurum:
• Düzenli database backup alın
• audio_files klasörünü yedekleyin
• .env dosyasını güvenli yerde saklayın

🔄 GÜNCELLEME:
• Yeni sürüme geçerken önce backup alın
• composer update çalıştırın
• Veritabanı migration'ları kontrol edin

═══════════════════════════════════════════════════════════════

Destek: [İletişim bilgileriniz]
Tarih: " . date('Y-m-d H:i:s') . "
Versiyon: 1.0

═══════════════════════════════════════════════════════════════";
    
    file_put_contents($targetDir . '/KURULUM_README.txt', $readmeContent);
    $scriptsCreated++;
    echo "<p class='success'>✅ KURULUM_README.txt oluşturuldu</p>";
    
    // Windows kurulum scripti
    $windowsSetup = "@echo off
chcp 65001 >nul
cls
echo ═══════════════════════════════════════════════════════════════
echo     OTOMATİK ARAMA SİSTEMİ - WINDOWS KURULUM
echo ═══════════════════════════════════════════════════════════════
echo.

REM Veritabanını import et
echo 📋 Veritabanı import ediliyor...
cd /d \"%~dp0\"

set MYSQL_PATH=\"C:\\xampp\\mysql\\bin\\mysql.exe\"
if not exist %MYSQL_PATH% (
    echo ❌ MySQL bulunamadı: %MYSQL_PATH%
    pause
    exit /b 1
)

echo ⚠️ MySQL root şifresini girin:
%MYSQL_PATH% -u root -p < database_backup.sql

if %errorlevel% equ 0 (
    echo ✅ Veritabanı başarıyla import edildi!
) else (
    echo ❌ Veritabanı import edilemedi!
    pause
    exit /b 1
)

echo.
echo ═══════════════════════════════════════════════════════════════
echo ✅ KURULUM TAMAMLANDI!
echo ═══════════════════════════════════════════════════════════════
echo.
echo Sıradaki adımlar:
echo 1. .env dosyasını düzenleyin
echo 2. Apache'yi başlatın
echo 3. http://localhost/OtomatikArama/ adresini açın
echo.
pause";
    
    file_put_contents($targetDir . '/SETUP_WINDOWS.bat', $windowsSetup);
    $scriptsCreated++;
    echo "<p class='success'>✅ SETUP_WINDOWS.bat oluşturuldu</p>";
    
    // Linux kurulum scripti  
    $linuxSetup = "#!/bin/bash
echo \"═══════════════════════════════════════════════════════════════\"
echo \"    OTOMATİK ARAMA SİSTEMİ - LINUX KURULUM\"
echo \"═══════════════════════════════════════════════════════════════\"
echo \"\"

# Klasör izinleri
echo \"📁 Klasör izinleri ayarlanıyor...\"
chmod -R 755 .
chmod 777 uploads
chmod 777 audio_files
chmod 600 .env

# Veritabanını import et
echo \"📋 Veritabanı import ediliyor...\"
echo \"MySQL root şifresini girin:\"
mysql -u root -p ANABILIM < database_backup.sql

if [ \$? -eq 0 ]; then
    echo \"✅ Veritabanı başarıyla import edildi!\"
else
    echo \"❌ Veritabanı import edilemedi!\"
    exit 1
fi

echo \"\"
echo \"═══════════════════════════════════════════════════════════════\"
echo \"✅ KURULUM TAMAMLANDI!\"
echo \"═══════════════════════════════════════════════════════════════\"
echo \"\"
echo \"Sıradaki adımlar:\"
echo \"1. .env dosyasını düzenleyin: nano .env\"
echo \"2. Apache/Nginx'i yeniden başlatın\"
echo \"3. http://localhost/OtomatikArama/ adresini açın\"
echo \"\"";
    
    file_put_contents($targetDir . '/setup.sh', $linuxSetup);
    chmod($targetDir . '/setup.sh', 0755);
    $scriptsCreated++;
    echo "<p class='success'>✅ setup.sh oluşturuldu</p>";
    
    echo "<p class='info'>Toplam $scriptsCreated script oluşturuldu</p>";
    echo "</div>";
    
    // 5. Özel klasörler oluştur
    echo "<div class='box'>";
    echo "<h2>📁 5. ÖZEL KLASÖRLER OLUŞTURULUYOR</h2>";
    
    $specialDirs = [
        '/uploads' => 'Yüklenen dosyalar için',
        '/audio_files' => 'Ses dosyaları için',
        '/asterisk/agi' => 'AGI scriptleri için'
    ];
    
    foreach ($specialDirs as $dir => $desc) {
        $fullPath = $targetDir . $dir;
        if (!is_dir($fullPath)) {
            mkdir($fullPath, 0777, true);
            echo "<p class='success'>✅ $dir oluşturuldu ($desc)</p>";
        } else {
            echo "<p class='info'>ℹ️ $dir zaten mevcut</p>";
        }
        
        // .gitkeep ekle
        file_put_contents($fullPath . '/.gitkeep', '');
    }
    echo "</div>";
    
    // 6. Özet
    echo "<div class='box' style='border-left-color: #4ec9b0;'>";
    echo "<h2 class='success'>✅ PAKET OLUŞTURMA TAMAMLANDI!</h2>";
    echo "<hr>";
    
    echo "<h3>📦 Paket İçeriği:</h3>";
    echo "<ul>";
    echo "<li class='success'>✅ Tüm proje dosyaları kopyalandı ($copiedCount dosya)</li>";
    echo "<li class='success'>✅ Veritabanı dump'ı: database_backup.sql</li>";
    echo "<li class='success'>✅ Kurulum scriptleri ($scriptsCreated adet)</li>";
    echo "<li class='success'>✅ Dokümantasyon: KURULUM_README.txt</li>";
    echo "<li class='success'>✅ Özel klasörler oluşturuldu</li>";
    echo "</ul>";
    
    echo "<h3>📍 Paket Konumu:</h3>";
    echo "<pre class='success' style='font-size: 16px;'>$targetDir</pre>";
    
    echo "<h3>🚀 Sonraki Adımlar:</h3>";
    echo "<ol style='line-height: 2;'>";
    echo "<li><strong>Klasörü kopyala:</strong> OtomatikAramason'u hedef sunucuya taşı</li>";
    echo "<li><strong>Kurulum scriptini çalıştır:</strong> SETUP_WINDOWS.bat veya setup.sh</li>";
    echo "<li><strong>.env dosyasını düzenle:</strong> Veritabanı ve Asterisk bilgilerini gir</li>";
    echo "<li><strong>Test et:</strong> http://localhost/OtomatikArama/</li>";
    echo "</ol>";
    
    echo "<div style='margin-top: 30px;'>";
    echo "<a href='file:///$targetDir' class='btn'>📂 Klasörü Aç</a>";
    echo "<a href='$targetDir/KURULUM_README.txt' class='btn' target='_blank'>📄 README'yi Oku</a>";
    echo "</div>";
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='box' style='border-left-color: #f48771;'>";
    echo "<h2 class='error'>❌ HATA OLUŞTU!</h2>";
    echo "<p class='error'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}

echo "</div>
</body>
</html>";
?>
