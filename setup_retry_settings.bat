@echo off
chcp 65001 >nul
echo ========================================
echo Tekrar Arama Ayarları Kurulumu
echo ========================================
echo.

set MYSQL_HOST=192.168.181.16
set MYSQL_USER=astera
set MYSQL_PASS=KKAstera*2o21@.!
set MYSQL_DB=ANABILIM

echo [1/1] Veritabanı şeması güncelleniyor...
echo.

mysql -h %MYSQL_HOST% -u %MYSQL_USER% -p%MYSQL_PASS% %MYSQL_DB% < database\add_retry_settings.sql

if %ERRORLEVEL% EQU 0 (
    echo.
    echo [OK] Tekrar arama ayarları başarıyla eklendi!
    echo.
    echo Eklenen alanlar:
    echo - IMPORT_HISTORY.RetryCount (Tekrar sayısı)
    echo - IMPORT_HISTORY.RetryIntervalHours (Saat cinsinden aralık)
    echo - CALLS.LastCallTime (Son arama zamanı)
) else (
    echo.
    echo [HATA] Veritabanı güncellenemedi!
)

echo.
echo ========================================
pause
