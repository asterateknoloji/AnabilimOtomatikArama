@echo off
REM Otomatik Arama Cron Job'u Kaldır

echo ============================================
echo Otomatik Arama Cron Job Kaldirma
echo ============================================
echo.

echo Mevcut gorev siliniyor...

schtasks /delete /tn "AutoCallProcessor" /f

if %errorlevel% equ 0 (
    echo.
    echo [BASARILI] Gorev kaldirildi!
    echo.
    echo Artik otomatik arama kontrolu yapilmiyor.
    echo Sadece "Aktif" butonu ile manuel baslatabilirsiniz.
) else (
    echo.
    echo [HATA] Gorev bulunamadi veya zaten silinmis.
)

echo.
pause
