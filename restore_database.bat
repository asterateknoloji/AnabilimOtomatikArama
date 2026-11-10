@echo off
chcp 65001 > nul
color 0C
title Veritabanı Geri Yükleme

echo.
echo ╔══════════════════════════════════════════════════════╗
echo ║       ⚠️  Veritabanı Geri Yükleme                   ║
echo ╚══════════════════════════════════════════════════════╝
echo.
echo ⚠️  UYARI: Bu işlem mevcut verileri SİLECEK!
echo.
set /p confirm="Devam etmek istediğinizden emin misiniz? (Y/N): "
if /i not "%confirm%"=="Y" (
    echo.
    echo İşlem iptal edildi.
    pause
    exit /b 0
)

REM Değişkenler
set MYSQL_PATH=D:\xampp\mysql\bin
set BACKUP_DIR=D:\Backup\OtomatikArama\Database
set DB_HOST=192.168.181.16
set DB_USER=astera
set DB_PASS=astera2012

echo.
echo [1/4] Yedek dosyası seçiliyor...
echo.
echo Yedek Klasörü: %BACKUP_DIR%
echo.

REM Yedek dosyalarını listele
if exist "%BACKUP_DIR%\" (
    echo Mevcut Yedekler:
    echo ═════════════════════════════════════════════════
    dir /B /O-D "%BACKUP_DIR%\asteriskcdrdb_*.sql" 2>nul
    dir /B /O-D "%BACKUP_DIR%\database_backup_*.zip" 2>nul
    echo.
) else (
    echo ❌ Yedek klasörü bulunamadı: %BACKUP_DIR%
    pause
    exit /b 1
)

REM Dosya adı sor
set /p BACKUP_FILE="Geri yüklenecek dosya adı (tam yol): "

if not exist "%BACKUP_FILE%" (
    echo ❌ Dosya bulunamadı: %BACKUP_FILE%
    pause
    exit /b 1
)

echo ✅ Dosya bulundu: %BACKUP_FILE%
echo.

REM ZIP kontrolü
if /i "%BACKUP_FILE:~-4%"==".zip" (
    echo [2/4] ZIP dosyası açılıyor...
    
    if not exist "C:\Program Files\7-Zip\7z.exe" (
        echo ❌ 7-Zip bulunamadı!
        pause
        exit /b 1
    )
    
    REM Temp klasöre aç
    set TEMP_DIR=%TEMP%\db_restore_%RANDOM%
    mkdir "%TEMP_DIR%"
    
    "C:\Program Files\7-Zip\7z.exe" x "%BACKUP_FILE%" -o"%TEMP_DIR%" -y > nul
    
    REM İlk .sql dosyasını bul
    for %%F in ("%TEMP_DIR%\asteriskcdrdb_*.sql") do (
        set SQL_FILE=%%F
        goto :found
    )
    
    echo ❌ ZIP içinde .sql dosyası bulunamadı!
    rmdir /S /Q "%TEMP_DIR%"
    pause
    exit /b 1
    
    :found
    echo ✅ SQL dosyası bulundu
) else (
    set SQL_FILE=%BACKUP_FILE%
)

echo.
echo [3/4] Veritabanı geri yükleniyor...
echo.
echo ⚠️  SON UYARI: Mevcut veriler silinecek!
echo    Dosya: %SQL_FILE%
echo.
set /p final_confirm="Geri yüklemeyi başlat? (Y/N): "
if /i not "%final_confirm%"=="Y" (
    echo.
    echo İşlem iptal edildi.
    if defined TEMP_DIR rmdir /S /Q "%TEMP_DIR%"
    pause
    exit /b 0
)

echo.
echo Geri yükleme başlıyor...
"%MYSQL_PATH%\mysql.exe" -u %DB_USER% -p%DB_PASS% -h %DB_HOST% < "%SQL_FILE%"

if errorlevel 1 (
    echo ❌ Geri yükleme başarısız!
    if defined TEMP_DIR rmdir /S /Q "%TEMP_DIR%"
    pause
    exit /b 1
)

echo ✅ Geri yükleme tamamlandı!

REM Temp klasörü temizle
if defined TEMP_DIR (
    rmdir /S /Q "%TEMP_DIR%"
)

echo.
echo [4/4] Kontrol ediliyor...
echo.

REM Tablo kontrolü
echo Tablo Sayıları:
"%MYSQL_PATH%\mysql.exe" -u %DB_USER% -p%DB_PASS% -h %DB_HOST% -e "SELECT COUNT(*) AS CallCount FROM asteriskcdrdb.CALLS; SELECT COUNT(*) AS ImportCount FROM asteriskcdrdb.IMPORT_HISTORY; SELECT COUNT(*) AS ProjectCount FROM asteriskcdrdb.PROJECTS; SELECT COUNT(*) AS TemplateCount FROM asteriskcdrdb.MESSAGE_TEMPLATES;" 2>nul

echo.
echo ╔══════════════════════════════════════════════════════╗
echo ║        ✅ GERİ YÜKLEME TAMAMLANDI!                  ║
echo ╚══════════════════════════════════════════════════════╝
echo.
echo Sonraki Adımlar:
echo 1. Web arayüzünü test edin
echo 2. Arama sistemi çalışıyor mu kontrol edin
echo 3. Log dosyalarını kontrol edin
echo.

pause
