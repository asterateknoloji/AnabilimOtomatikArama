@echo off
chcp 65001 >nul
echo ========================================
echo OTOMATIK ARAMA TRİGGER KURULUMU
echo ========================================
echo.
echo Bu script MySQL Trigger oluşturur.
echo.
echo TRİGGER İŞLEVİ:
echo - Aktif=0 → Aktif=1 değiştiğinde
echo - CallTime otomatik NOW() olur
echo - LastCallTime NULL olur
echo - Status = 1 (aranacak) olur
echo.
echo SONUÇ:
echo Manuel SQL ile veya web arayüzünden
echo Aktif=1 yaptığınızda OTOMATIK olarak
echo CallTime=NOW() ayarlanır ve görev
echo zamanlayıcı bir sonraki çalışmasında
echo hemen arar!
echo.
pause
echo.

set MYSQL_PATH=D:\xampp\mysql\bin\mysql.exe
set DB_USER=root
set DB_PASS=
set DB_NAME=ANABILIM
set SQL_FILE=%~dp0create_auto_trigger.sql

echo SQL çalıştırılıyor...
echo.

"%MYSQL_PATH%" -u%DB_USER% %DB_NAME% < "%SQL_FILE%"

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ========================================
    echo ✅ Trigger Başarıyla Oluşturuldu!
    echo ========================================
    echo.
    echo Artık:
    echo 1. Manuel SQL ile Aktif=1 yapın
    echo    → CallTime otomatik NOW() olur
    echo.
    echo 2. Görev zamanlayıcı bir sonraki çalışmasında
    echo    → Hemen arar! (max 5 dk)
    echo.
    echo VEYA hemen başlatmak için:
    echo    start_calls_now.bat
    echo.
    echo ========================================
    echo.
) else (
    echo.
    echo ========================================
    echo ❌ Hata Oluştu!
    echo ========================================
    echo.
)

pause
