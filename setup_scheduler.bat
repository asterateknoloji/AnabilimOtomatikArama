@echo off
chcp 65001 > nul
echo ========================================
echo Zamanlanmış Arama Servisi Kurulumu
echo ========================================
echo.

cd /d "%~dp0"

REM PHP yolu
set PHP_PATH=D:\xampp\php\php.exe

REM Script yolu
set SCRIPT_PATH=%~dp0cron\scheduled_calls.php

echo PHP Path: %PHP_PATH%
echo Script Path: %SCRIPT_PATH%
echo.

REM Task Scheduler'a görev ekle (her dakika çalışsın)
echo Task Scheduler'a görev ekleniyor...

schtasks /Create /F /SC MINUTE /MO 1 /TN "OtomatikAramaServisi" /TR "\"%PHP_PATH%\" \"%SCRIPT_PATH%\"" /RU SYSTEM

if %ERRORLEVEL% EQU 0 (
    echo [OK] Görev başarıyla eklendi!
    echo.
    echo Servis her dakika otomatik çalışacak ve zamanı gelmiş
    echo aramaları başlatacak.
    echo.
    echo Görevi kontrol etmek için:
    echo   schtasks /Query /TN "OtomatikAramaServisi"
    echo.
    echo Görevi silmek için:
    echo   schtasks /Delete /TN "OtomatikAramaServisi" /F
) else (
    echo [HATA] Görev eklenemedi!
    echo Lütfen bu scripti Yönetici olarak çalıştırın.
)

echo.
pause
