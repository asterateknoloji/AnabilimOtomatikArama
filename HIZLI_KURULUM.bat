@echo off
chcp 65001 > nul
color 0A
title Otomatik Arama Sistemi - Hızlı Kurulum

echo.
echo ╔══════════════════════════════════════════════════════╗
echo ║    Otomatik Arama Sistemi - Hızlı Kurulum          ║
echo ║    Versiyon: 1.0.0                                  ║
echo ╚══════════════════════════════════════════════════════╝
echo.

REM Değişkenler
set PHP_PATH=D:\xampp\php\php.exe
set MYSQL_PATH=D:\xampp\mysql\bin\mysql.exe
set DB_HOST=192.168.181.16
set DB_USER=astera
set DB_PASS=astera2012
set DB_NAME=asteriskcdrdb

echo [1/7] Kontroller yapılıyor...
echo.

REM PHP kontrolü
if not exist "%PHP_PATH%" (
    echo ❌ PHP bulunamadı: %PHP_PATH%
    echo    Lütfen PHP_PATH değişkenini düzenleyin
    pause
    exit /b 1
)
echo ✅ PHP bulundu: %PHP_PATH%

REM MySQL kontrolü
if not exist "%MYSQL_PATH%" (
    echo ❌ MySQL bulunamadı: %MYSQL_PATH%
    echo    Lütfen MYSQL_PATH değişkenini düzenleyin
    pause
    exit /b 1
)
echo ✅ MySQL bulundu: %MYSQL_PATH%

REM Klasör kontrolü
if not exist "logs\" mkdir logs
if not exist "temp\" mkdir temp
if not exist "asterisk\" mkdir asterisk
echo ✅ Klasörler hazır

echo.
echo [2/7] Veritabanı bağlantısı test ediliyor...
echo.

"%MYSQL_PATH%" -u %DB_USER% -p%DB_PASS% -h %DB_HOST% -e "SELECT 1" 2>nul
if errorlevel 1 (
    echo ❌ Veritabanı bağlantı hatası!
    echo    Host: %DB_HOST%
    echo    User: %DB_USER%
    echo    Lütfen config.php ayarlarını kontrol edin
    pause
    exit /b 1
)
echo ✅ Veritabanı bağlantısı başarılı

echo.
echo [3/7] Composer bağımlılıkları kuruluyor...
echo.

if not exist "composer.phar" (
    echo Composer indiriliyor...
    "%PHP_PATH%" -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    "%PHP_PATH%" composer-setup.php
    "%PHP_PATH%" -r "unlink('composer-setup.php');"
)

if exist "composer.phar" (
    echo Composer bağımlılıkları kuruluyor...
    "%PHP_PATH%" composer.phar install --no-dev --optimize-autoloader
    echo ✅ Composer bağımlılıkları kuruldu
) else (
    echo ⚠️  Composer bulunamadı, manuel kurulum gerekli
)

echo.
echo [4/7] Veritabanı tabloları oluşturuluyor...
echo.

REM SQL scriptlerini çalıştır
for %%f in (database\*.sql) do (
    echo Çalıştırılıyor: %%f
    powershell -Command "Get-Content '%%f' | %MYSQL_PATH% -u %DB_USER% -p%DB_PASS% -h %DB_HOST% %DB_NAME%"
)
echo ✅ Veritabanı tabloları oluşturuldu

echo.
echo [5/7] Test ediliyor...
echo.

"%PHP_PATH%" test_database.php
if errorlevel 1 (
    echo ⚠️  Test başarısız, log dosyalarını kontrol edin
) else (
    echo ✅ Testler başarılı
)

echo.
echo [6/7] Task Scheduler ayarlanıyor...
echo.

echo Task Scheduler için Administrator yetkisi gerekli
echo Aşağıdaki komutları Administrator olarak çalıştırın:
echo.
echo schtasks /Create /TN "OtomatikArama_Retry" /TR "%PHP_PATH% %CD%\retry_scheduler.php" /SC MINUTE /MO 5 /F
echo schtasks /Create /TN "OtomatikArama_Cleanup" /TR "%PHP_PATH% %CD%\cleanup_stuck_calls.php" /SC MINUTE /MO 10 /F
echo.

choice /C YN /M "Task Scheduler'ı şimdi kurmak ister misiniz? (Administrator gerekli)"
if errorlevel 2 goto skip_scheduler
if errorlevel 1 goto install_scheduler

:install_scheduler
schtasks /Create /TN "OtomatikArama_Retry" /TR "%PHP_PATH% %CD%\retry_scheduler.php" /SC MINUTE /MO 5 /F
schtasks /Create /TN "OtomatikArama_Cleanup" /TR "%PHP_PATH% %CD%\cleanup_stuck_calls.php" /SC MINUTE /MO 10 /F
if errorlevel 1 (
    echo ⚠️  Task Scheduler kurulumu başarısız (Administrator yetkisi gerekli)
) else (
    echo ✅ Task Scheduler kuruldu
)
goto after_scheduler

:skip_scheduler
echo ⚠️  Task Scheduler atlandı, manuel kurulum yapın
goto after_scheduler

:after_scheduler

echo.
echo [7/7] Son kontroller...
echo.

if exist "vendor\autoload.php" (
    echo ✅ Composer vendor klasörü var
) else (
    echo ⚠️  Vendor klasörü yok, composer install çalıştırın
)

if exist "tools\plink.exe" (
    echo ✅ PuTTY araçları var
) else (
    echo ⚠️  PuTTY araçları yok, tools\ klasörüne ekleyin
)

if exist "SimpleXLSX.php" (
    echo ✅ SimpleXLSX kütüphanesi var
) else (
    echo ⚠️  SimpleXLSX yok, import çalışmayabilir
)

echo.
echo ╔══════════════════════════════════════════════════════╗
echo ║             ✅ KURULUM TAMAMLANDI!                  ║
echo ╚══════════════════════════════════════════════════════╝
echo.
echo Sonraki Adımlar:
echo.
echo 1. config.php dosyasını kontrol edin
echo 2. Web tarayıcıdan sistemi açın
echo 3. Login: superadmin / Astera2020
echo 4. İlk projeyi oluşturun
echo 5. İlk şablonu oluşturun
echo 6. Test araması yapın
echo.
echo Dokümantasyon:
echo - KURULUM_KILAVUZU.md
echo - KURULUM_CHECKLIST.md
echo - KULLANIM_KILAVUZU.md
echo.
echo Sorun mu var?
echo - logs\ klasörünü kontrol edin
echo - test_database.php çalıştırın
echo - Asterisk loglarını kontrol edin
echo.

pause
