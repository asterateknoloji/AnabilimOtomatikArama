@echo off
chcp 65001 >nul
echo ========================================
echo HEMEN ARAMA BAŞLAT - MANUEL TETİKLEME
echo ========================================
echo.
echo Bu script retry_scheduler.php dosyasını
echo HEMEN çalıştırır ve bekleyen aramaları başlatır.
echo.
echo Görev zamanlayıcıyı beklemek yerine
echo ANINDA başlatmak için kullanın!
echo.
pause
echo.

set PHP_PATH=D:\xampp\php\php.exe
set SCRIPT_PATH=%~dp0retry_scheduler.php

echo PHP Script çalıştırılıyor...
echo.
echo ========================================
echo.

"%PHP_PATH%" "%SCRIPT_PATH%"

echo.
echo ========================================
echo.

if %ERRORLEVEL% EQU 0 (
    echo ✅ Script başarıyla çalıştı!
    echo.
    echo Aramalar başlatıldı. Log'ları kontrol edin.
    echo.
) else (
    echo ❌ Script hatası! Loglara bakın.
    echo.
)

echo ========================================
echo.
echo 💡 İPUCU:
echo.
echo Bu script'i her çalıştırdığınızda:
echo - Aktif=1, CallTime<=NOW() kayıtlar aranır
echo - Görev zamanlayıcıyı beklemeden ANINDA başlar
echo.

pause
