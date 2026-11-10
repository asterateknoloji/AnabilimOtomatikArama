@echo off
REM Otomatik Arama Cron Job Kurulum
REM Bu script her 1 dakikada process_calls.php'yi çalıştırır

echo ============================================
echo Otomatik Arama Cron Job Kurulumu
echo ============================================
echo.

REM Mevcut görevi sil (varsa)
schtasks /delete /tn "AutoCallProcessor" /f >nul 2>&1

echo Yeni görev olusturuluyor...
echo Siklik: Her 1 dakikada bir
echo Dosya: %~dp0cron\process_calls.php
echo.

REM Her 1 dakikada bir çalışacak görev oluştur
schtasks /create /tn "AutoCallProcessor" ^
    /tr "C:\xampp\php\php.exe -f \"%~dp0cron\process_calls.php\"" ^
    /sc minute /mo 1 ^
    /ru SYSTEM ^
    /f

if %errorlevel% equ 0 (
    echo.
    echo ============================================
    echo [BASARILI] Gorev basariyla kuruldu!
    echo ============================================
    echo.
    echo Gorev Adi: AutoCallProcessor
    echo Siklik: Her 1 dakikada bir
    echo Durum: Aktif
    echo.
    echo Gorevi kontrol etmek icin:
    echo   schtasks /query /tn "AutoCallProcessor"
    echo.
    echo Gorevi silmek icin:
    echo   schtasks /delete /tn "AutoCallProcessor" /f
    echo.
    echo ============================================
    echo.
    echo DIKKAT: Artik sistem her 1 dakikada otomatik
    echo         bekleyen aramalari baslatiyor!
    echo ============================================
) else (
    echo.
    echo [HATA] Gorev olusturulamadi!
    echo Lutfen bu dosyayi "Yonetici olarak calistir"
)

echo.
pause
