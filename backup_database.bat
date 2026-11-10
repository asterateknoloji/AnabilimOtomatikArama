@echo off
chcp 65001 > nul
color 0B
title Veritabanı Yedekleme

echo.
echo ╔══════════════════════════════════════════════════════╗
echo ║          💾 Veritabanı Yedekleme                    ║
echo ╚══════════════════════════════════════════════════════╝
echo.

REM Değişkenler
set MYSQL_PATH=D:\xampp\mysql\bin
set BACKUP_DIR=D:\Backup\OtomatikArama\Database
set DB_HOST=192.168.181.16
set DB_USER=astera
set DB_PASS=astera2012
set DATE=%date:~-4,4%%date:~-7,2%%date:~-10,2%
set TIME=%time:~0,2%%time:~3,2%%time:~6,2%
set TIME=%TIME: =0%

REM Yedek klasörü oluştur
if not exist "%BACKUP_DIR%" mkdir "%BACKUP_DIR%"

echo [1/5] Yedekleme hazırlığı yapılıyor...
echo.
echo Yedek Klasörü: %BACKUP_DIR%
echo Tarih: %DATE%
echo Saat: %TIME%
echo.

REM MySQL kontrolü
if not exist "%MYSQL_PATH%\mysqldump.exe" (
    echo ❌ MySQL bulunamadı: %MYSQL_PATH%
    echo    Lütfen MYSQL_PATH değişkenini düzenleyin
    pause
    exit /b 1
)

echo ✅ MySQL bulundu
echo.

REM Veritabanı bağlantı testi
echo [2/5] Veritabanı bağlantısı test ediliyor...
"%MYSQL_PATH%\mysql.exe" -u %DB_USER% -p%DB_PASS% -h %DB_HOST% -e "SELECT 1" 2>nul
if errorlevel 1 (
    echo ❌ Veritabanı bağlantı hatası!
    echo    Host: %DB_HOST%
    echo    User: %DB_USER%
    pause
    exit /b 1
)
echo ✅ Bağlantı başarılı
echo.

REM asteriskcdrdb yedeği
echo [3/5] asteriskcdrdb veritabanı yedekleniyor...
set BACKUP_FILE=%BACKUP_DIR%\asteriskcdrdb_%DATE%_%TIME%.sql

"%MYSQL_PATH%\mysqldump.exe" -u %DB_USER% -p%DB_PASS% -h %DB_HOST% --databases asteriskcdrdb --routines --triggers --events > "%BACKUP_FILE%" 2>nul

if errorlevel 1 (
    echo ❌ Yedekleme başarısız!
    pause
    exit /b 1
)

echo ✅ asteriskcdrdb yedeklendi
echo    Dosya: %BACKUP_FILE%
echo    Boyut: 
for %%A in ("%BACKUP_FILE%") do echo    %%~zA bytes
echo.

REM asterisk veritabanı (opsiyonel)
echo [4/5] asterisk veritabanı yedekleniyor (opsiyonel)...
set BACKUP_FILE_AST=%BACKUP_DIR%\asterisk_%DATE%_%TIME%.sql

"%MYSQL_PATH%\mysqldump.exe" -u %DB_USER% -p%DB_PASS% -h %DB_HOST% --databases asterisk --routines --triggers --events > "%BACKUP_FILE_AST%" 2>nul

if errorlevel 1 (
    echo ⚠️  asterisk veritabanı yedeklenemedi (normal, opsiyonel)
) else (
    echo ✅ asterisk yedeklendi
    echo    Dosya: %BACKUP_FILE_AST%
    echo    Boyut: 
    for %%A in ("%BACKUP_FILE_AST%") do echo    %%~zA bytes
)
echo.

REM Sıkıştırma (opsiyonel)
echo [5/5] Yedek sıkıştırılıyor...
if exist "C:\Program Files\7-Zip\7z.exe" (
    set ZIP_FILE=%BACKUP_DIR%\database_backup_%DATE%_%TIME%.zip
    "C:\Program Files\7-Zip\7z.exe" a -tzip "%ZIP_FILE%" "%BACKUP_FILE%" "%BACKUP_FILE_AST%" > nul 2>&1
    
    if errorlevel 0 (
        echo ✅ Yedek sıkıştırıldı
        echo    Dosya: %ZIP_FILE%
        for %%A in ("%ZIP_FILE%") do echo    Boyut: %%~zA bytes
        echo.
        
        REM SQL dosyalarını sil (ZIP var artık)
        del "%BACKUP_FILE%" > nul 2>&1
        del "%BACKUP_FILE_AST%" > nul 2>&1
    ) else (
        echo ⚠️  Sıkıştırma başarısız, SQL dosyaları korundu
    )
) else (
    echo ⚠️  7-Zip bulunamadı, sıkıştırma atlandı
)

echo.
echo ╔══════════════════════════════════════════════════════╗
echo ║           ✅ YEDEKLEME TAMAMLANDI!                  ║
echo ╚══════════════════════════════════════════════════════╝
echo.
echo Yedek Dosyaları:
dir /B "%BACKUP_DIR%\*%DATE%_%TIME%*"
echo.
echo Yedek Klasörü: %BACKUP_DIR%
echo.

REM İstatistikler
echo Yedekleme İstatistikleri:
echo ═════════════════════════════════════════════════
echo.

REM Tablo sayıları
echo Tablo Sayıları:
"%MYSQL_PATH%\mysql.exe" -u %DB_USER% -p%DB_PASS% -h %DB_HOST% -e "SELECT COUNT(*) AS CallCount FROM asteriskcdrdb.CALLS; SELECT COUNT(*) AS ImportCount FROM asteriskcdrdb.IMPORT_HISTORY; SELECT COUNT(*) AS ProjectCount FROM asteriskcdrdb.PROJECTS; SELECT COUNT(*) AS TemplateCount FROM asteriskcdrdb.MESSAGE_TEMPLATES;" 2>nul

echo.
echo Veritabanı Boyutu:
"%MYSQL_PATH%\mysql.exe" -u %DB_USER% -p%DB_PASS% -h %DB_HOST% -e "SELECT table_schema AS 'Database', ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'Size_MB' FROM information_schema.tables WHERE table_schema = 'asteriskcdrdb' GROUP BY table_schema;" 2>nul

echo.
echo Sonraki Adımlar:
echo 1. Yedek dosyasını test edin
echo 2. Güvenli bir yere kopyalayın
echo 3. Eski yedekleri temizleyin
echo 4. Düzenli yedekleme planlayın
echo.
echo Test için:
echo    restore_database.bat
echo.

pause
