@echo off
REM Hızlı Kurulum - Tek Komut

cd /d "%~dp0"

echo ========================================
echo   Otomatik Arama - Hızlı Kurulum
echo ========================================
echo.

REM PHP yolunu ayarla
set PHP=D:\xampp\php\php.exe

if not exist "%PHP%" (
    set PHP=C:\xampp\php\php.exe
)

if not exist "%PHP%" (
    echo HATA: PHP bulunamadı!
    echo D:\xampp\php\php.exe veya C:\xampp\php\php.exe mevcut değil
    echo.
    echo Lütfen PHP yolunu manuel girin:
    set /p PHP="PHP tam yolu: "
)

echo PHP: %PHP%
echo.

REM 1. Composer indir
echo [1/4] Composer indiriliyor...
"%PHP%" -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
"%PHP%" composer-setup.php --quiet
del composer-setup.php

REM 2. Bağımlılıkları yükle
echo [2/4] Bağımlılıklar yükleniyor...
"%PHP%" composer.phar install --no-interaction --quiet

REM 3. Klasörleri oluştur
echo [3/4] Klasörler oluşturuluyor...
mkdir asterisk\audio\calls 2>nul
mkdir asterisk\callfiles 2>nul
mkdir asterisk\agi 2>nul
mkdir logs 2>nul
mkdir temp 2>nul

REM 4. Test
echo [4/4] Test ediliyor...
"%PHP%" test_autocall.php

echo.
echo ========================================
echo   Kurulum Tamamlandı!
echo ========================================
echo.
echo Web paneli açılıyor...
timeout /t 2 >nul
start http://localhost:8009/AsteraWeb/otomatikarama.php
