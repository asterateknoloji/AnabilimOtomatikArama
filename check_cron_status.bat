@echo off
REM Cron Job Durum Kontrolü

echo ============================================
echo Cron Job Durum Kontrolu
echo ============================================
echo.

echo Gorev Listesi:
echo.
schtasks /query /tn "AutoCallProcessor" /fo LIST /v

echo.
echo ============================================
echo Log Dosyasi Kontrolu:
echo ============================================
echo.

if exist "logs\autocall_%date:~-4,4%-%date:~-7,2%-%date:~-10,2%.log" (
    echo Son 10 satir:
    powershell -command "Get-Content 'logs\autocall_*.log' -Tail 10"
) else (
    echo [UYARI] Log dosyasi bulunamadi!
    echo Gorev henuz calismamis olabilir.
)

echo.
pause
