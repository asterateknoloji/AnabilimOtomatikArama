@echo off
chcp 65001 > nul
color 0A
title Veritabanı Kontrolü

echo.
echo ╔══════════════════════════════════════════════════════╗
echo ║          🔍 Veritabanı Kontrolü                     ║
echo ╚══════════════════════════════════════════════════════╝
echo.

REM Değişkenler
set MYSQL_PATH=D:\xampp\mysql\bin\mysql.exe
set DB_HOST=192.168.181.16
set DB_USER=astera
set DB_PASS=astera2012

echo [1/7] Bağlantı test ediliyor...
"%MYSQL_PATH%" -u %DB_USER% -p%DB_PASS% -h %DB_HOST% -e "SELECT 1" 2>nul
if errorlevel 1 (
    echo ❌ Veritabanı bağlantı hatası!
    pause
    exit /b 1
)
echo ✅ Bağlantı başarılı
echo.

echo [2/7] Veritabanları kontrol ediliyor...
echo.
"%MYSQL_PATH%" -u %DB_USER% -p%DB_PASS% -h %DB_HOST% -e "SHOW DATABASES LIKE 'asteriskcdrdb';" 2>nul | findstr "asteriskcdrdb" >nul
if errorlevel 1 (
    echo ❌ asteriskcdrdb veritabanı bulunamadı!
) else (
    echo ✅ asteriskcdrdb veritabanı var
)

"%MYSQL_PATH%" -u %DB_USER% -p%DB_PASS% -h %DB_HOST% -e "SHOW DATABASES LIKE 'asterisk';" 2>nul | findstr "asterisk" >nul
if errorlevel 1 (
    echo ⚠️  asterisk veritabanı bulunamadı (opsiyonel)
) else (
    echo ✅ asterisk veritabanı var
)
echo.

echo [3/7] Tablolar kontrol ediliyor...
echo.
echo asteriskcdrdb tabloları:
"%MYSQL_PATH%" -u %DB_USER% -p%DB_PASS% -h %DB_HOST% -D asteriskcdrdb -e "SHOW TABLES;" 2>nul
echo.

echo [4/7] Kayıt sayıları...
echo.
echo ═════════════════════════════════════════════════
"%MYSQL_PATH%" -u %DB_USER% -p%DB_PASS% -h %DB_HOST% -e "SELECT 'CALLS' AS Tablo, COUNT(*) AS Kayit FROM asteriskcdrdb.CALLS UNION SELECT 'IMPORT_HISTORY', COUNT(*) FROM asteriskcdrdb.IMPORT_HISTORY UNION SELECT 'MESSAGE_TEMPLATES', COUNT(*) FROM asteriskcdrdb.MESSAGE_TEMPLATES UNION SELECT 'PROJECTS', COUNT(*) FROM asteriskcdrdb.PROJECTS;" 2>nul
echo.

echo [5/7] Durum dağılımı...
echo.
echo Arama Durumları:
"%MYSQL_PATH%" -u %DB_USER% -p%DB_PASS% -h %DB_HOST% -e "SELECT CASE WHEN Status = 1 THEN 'Bekliyor' WHEN Status = 2 THEN 'Arandi' ELSE 'Diger' END AS Durum, COUNT(*) AS Adet FROM asteriskcdrdb.CALLS GROUP BY Status;" 2>nul
echo.

echo [6/7] Veritabanı boyutu...
echo.
"%MYSQL_PATH%" -u %DB_USER% -p%DB_PASS% -h %DB_HOST% -e "SELECT table_schema AS Veritabani, ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'Boyut_MB', ROUND(SUM(data_length + index_length) / 1024 / 1024 / 1024, 2) AS 'Boyut_GB' FROM information_schema.tables WHERE table_schema IN ('asteriskcdrdb', 'asterisk') GROUP BY table_schema;" 2>nul
echo.

echo Tablo bazında boyutlar:
"%MYSQL_PATH%" -u %DB_USER% -p%DB_PASS% -h %DB_HOST% -e "SELECT table_name AS Tablo, ROUND((data_length + index_length) / 1024 / 1024, 2) AS 'Boyut_MB', table_rows AS 'Satir_Sayisi' FROM information_schema.tables WHERE table_schema = 'asteriskcdrdb' ORDER BY (data_length + index_length) DESC;" 2>nul
echo.

echo [7/7] Veri bütünlüğü kontrolleri...
echo.

REM Orphan kayıtlar
"%MYSQL_PATH%" -u %DB_USER% -p%DB_PASS% -h %DB_HOST% -e "SELECT COUNT(*) AS Orphan_Calls FROM asteriskcdrdb.CALLS WHERE ProjectId NOT IN (SELECT id FROM asteriskcdrdb.PROJECTS);" 2>nul | findstr /V "Orphan_Calls" > temp_orphan.txt
set /p ORPHAN_COUNT=<temp_orphan.txt
del temp_orphan.txt

if "%ORPHAN_COUNT%"=="0" (
    echo ✅ Orphan kayıt yok
) else (
    echo ⚠️  %ORPHAN_COUNT% orphan kayıt bulundu
)

REM Stuck calls
"%MYSQL_PATH%" -u %DB_USER% -p%DB_PASS% -h %DB_HOST% -e "SELECT COUNT(*) AS Stuck_Calls FROM asteriskcdrdb.CALLS WHERE Locked = 1 AND TIMESTAMPDIFF(MINUTE, LastCallTime, NOW()) > 10;" 2>nul | findstr /V "Stuck_Calls" > temp_stuck.txt
set /p STUCK_COUNT=<temp_stuck.txt
del temp_stuck.txt

if "%STUCK_COUNT%"=="0" (
    echo ✅ Stuck call yok
) else (
    echo ⚠️  %STUCK_COUNT% stuck call bulundu
)

echo.
echo ╔══════════════════════════════════════════════════════╗
echo ║           ✅ KONTROL TAMAMLANDI!                    ║
echo ╚══════════════════════════════════════════════════════╝
echo.
echo Detaylı bilgi için:
echo    VERITABANI_BILGILERI.md
echo.
echo Yedek almak için:
echo    backup_database.bat
echo.

pause
