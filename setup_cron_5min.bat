@echo off
REM Otomatik Arama Cron Job - Her 5 Dakikada
REM Daha yavaş ve stabil çalışma için

echo ============================================
echo Otomatik Arama Cron Job (5 Dakika)
echo ============================================
echo.

REM Mevcut görevi sil (varsa)
schtasks /delete /tn "AutoCallProcessor" /f >nul 2>&1

echo Yeni gorev olusturuluyor...
echo Siklik: Her 5 dakikada bir
echo.

REM Her 5 dakikada bir çalışacak görev oluştur
schtasks /create /tn "AutoCallProcessor" ^
    /tr "C:\xampp\php\php.exe -f \"%~dp0cron\process_calls.php\"" ^
    /sc minute /mo 5 ^
    /ru SYSTEM ^
    /f

if %errorlevel% equ 0 (
    echo [BASARILI] Gorev kuruldu - Her 5 dakikada calisiyor
) else (
    echo [HATA] Gorev olusturulamadi! Yonetici olarak calistirin.
)

pause
