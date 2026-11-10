@echo off
chcp 65001 > nul
echo ========================================
echo PhpSpreadsheet Kurulumu
echo ========================================
echo.

cd /d "%~dp0"

echo [1/3] PHP zip extension kontrolü...
D:\xampp\php\php.exe -r "if(!extension_loaded('zip')) { echo 'ZIP extension KAPALI'; exit(1); } echo 'ZIP extension AÇIK';"

if %ERRORLEVEL% NEQ 0 (
    echo.
    echo [UYARI] ZIP extension kapalı!
    echo.
    echo Manuel kurulum:
    echo 1. D:\xampp\php\php.ini dosyasını açın
    echo 2. ";extension=zip" satırını bulun
    echo 3. Başındaki ; işaretini kaldırın: "extension=zip"
    echo 4. XAMPP'ı yeniden başlatın
    echo 5. Bu scripti tekrar çalıştırın
    echo.
    pause
    exit /b 1
)

echo [OK] ZIP extension aktif!
echo.

echo [2/3] Composer ile PhpSpreadsheet kuruluyor...
D:\xampp\php\php.exe D:\xampp\php\composer.phar require phpoffice/phpspreadsheet --no-interaction 2>&1

if %ERRORLEVEL% EQU 0 (
    echo [OK] PhpSpreadsheet kuruldu!
) else (
    echo [HATA] Kurulum başarısız!
    echo.
    echo Alternatif: SimpleXLSX kullanılacak (bağımlılık yok)
)

echo.
echo [3/3] Test ediliyor...
D:\xampp\php\php.exe -r "if(file_exists('vendor/autoload.php')) { echo 'vendor/autoload.php MEVCUT'; } else { echo 'BULUNAMADI'; }"

echo.
echo ========================================
echo Kurulum Tamamlandı!
echo ========================================
pause
