@echo off
chcp 65001 >nul
echo ========================================
echo   Otomatik Temizlik Task Kurulumu
echo ========================================
echo.

REM Mevcut task'ı sil (varsa)
schtasks /Delete /TN "OtomatikArama_Cleanup" /F >nul 2>&1

REM Yeni task oluştur - Her 1 dakikada bir çalışsın
schtasks /Create ^
    /TN "OtomatikArama_Cleanup" ^
    /TR "D:\xampp\php\php.exe D:\xampp\htdocs\OtomatikArama\cleanup_stuck_calls.php" ^
    /SC MINUTE ^
    /MO 1 ^
    /ST 00:00 ^
    /F ^
    /RU SYSTEM

if %ERRORLEVEL% EQU 0 (
    echo [OK] Task başarıyla oluşturuldu!
    echo.
    echo Task Detayları:
    echo   - Ad: OtomatikArama_Cleanup
    echo   - Sıklık: Her 1 dakika
    echo   - Script: cleanup_stuck_calls.php
    echo   - Log: logs\cleanup_YYYY-MM-DD.log
    echo.
    echo ========================================
    echo   Kurulum Tamamlandı!
    echo ========================================
) else (
    echo [HATA] Task oluşturulamadı!
    echo Lütfen bu batch dosyasını "Yönetici olarak çalıştır" ile açın.
)

pause
