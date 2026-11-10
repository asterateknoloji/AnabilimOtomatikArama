@echo off
REM Otomatik Arama Sistemi - XAMPP Windows Kurulum Scripti

REM Script'in bulunduğu dizine geç
cd /d "%~dp0"

echo ========================================
echo   Otomatik Arama Sistemi Kurulumu
echo   (XAMPP Optimized)
echo ========================================
echo.
echo Çalışma dizini: %CD%
echo.

REM XAMPP PHP yolunu bul
set PHP_PATH=D:\xampp\php\php.exe

REM Önce standart XAMPP yolunu kontrol et
if exist "%PHP_PATH%" (
    echo PHP bulundu: %PHP_PATH%
) else (
    REM Alternatif C: yolu
    set PHP_PATH=C:\xampp\php\php.exe
    if exist "%PHP_PATH%" (
        echo PHP bulundu: %PHP_PATH%
    ) else (
        echo HATA: PHP bulunamadı!
        echo.
        echo Lütfen PHP yolunu manuel olarak belirtin:
        echo Örnek: set PHP_PATH=D:\xampp\php\php.exe
        echo.
        set /p PHP_PATH="PHP yolunu girin (örn: D:\xampp\php\php.exe): "
        
        if not exist "%PHP_PATH%" (
            echo HATA: Belirtilen yolda PHP bulunamadı!
            pause
            exit /b 1
        )
    )
)

echo.
echo [1/6] PHP kontrolü...
"%PHP_PATH%" -v
if errorlevel 1 (
    echo HATA: PHP çalıştırılamadı!
    pause
    exit /b 1
)
echo PHP: OK
echo.

REM Composer kurulumu
echo [2/6] Composer bağımlılıkları yükleniyor...

REM composer.json varlığını kontrol et
if not exist composer.json (
    echo HATA: composer.json bulunamadı!
    echo Çalışma dizini: %CD%
    pause
    exit /b 1
)

REM Composer'ı indir ve kullan
if not exist composer.phar (
    echo Composer indiriliyor...
    "%PHP_PATH%" -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    "%PHP_PATH%" composer-setup.php
    "%PHP_PATH%" -r "unlink('composer-setup.php');"
)

echo Composer ile bağımlılıklar yükleniyor...
"%PHP_PATH%" composer.phar install --no-interaction

if errorlevel 1 (
    echo UYARI: Composer kurulumunda sorun oldu
    echo Manuel deneme: %PHP_PATH% composer.phar install
    echo.
)
echo Composer: OK
echo.

REM Klasör yapısını oluştur
echo [3/6] Klasör yapısı oluşturuluyor...
if not exist asterisk\audio\calls mkdir asterisk\audio\calls
if not exist asterisk\callfiles mkdir asterisk\callfiles
if not exist asterisk\agi mkdir asterisk\agi
if not exist logs mkdir logs
if not exist temp mkdir temp
echo Klasörler: OK
echo.

REM FFmpeg kontrolü
echo [4/6] FFmpeg kontrolü...
where ffmpeg >nul 2>&1
if errorlevel 1 (
    echo UYARI: FFmpeg bulunamadı!
    echo FFmpeg ses dönüşümü için gereklidir.
    echo Indirme: https://www.gyan.dev/ffmpeg/builds/
    echo PATH'e eklemeyi unutmayın: C:\ffmpeg\bin
    echo.
) else (
    ffmpeg -version 2>nul | findstr "version"
    echo FFmpeg: OK
    echo.
)

REM Excel şablonu oluştur
echo [5/6] Excel şablonu oluşturuluyor...
if exist create_excel_template.php (
    "%PHP_PATH%" create_excel_template.php 2>nul
    if errorlevel 1 (
        echo UYARI: Excel şablonu oluşturulamadı
        echo PhpSpreadsheet yüklendiğinden emin olun
        echo Manuel oluşturma: %PHP_PATH% create_excel_template.php
        echo.
    ) else (
        echo Excel şablon: OK
        echo.
    )
) else (
    echo UYARI: create_excel_template.php bulunamadı
    echo.
)

REM Windows TTS testi
echo [6/6] Windows TTS testi...
echo Test ediliyor...
powershell -Command "Add-Type -AssemblyName System.speech; $s = New-Object System.Speech.Synthesis.SpeechSynthesizer; $voices = $s.GetInstalledVoices() | Where-Object { $_.VoiceInfo.Culture.Name -eq 'tr-TR' }; if ($voices.Count -gt 0) { Write-Host 'Türkçe TTS: OK' -ForegroundColor Green } else { Write-Host 'UYARI: Türkçe TTS sesi bulunamadı!' -ForegroundColor Yellow }"
echo.

REM Özet
echo ========================================
echo   Kurulum Tamamlandı!
echo ========================================
echo.
echo PHP Yolu: %PHP_PATH%
echo.
echo Sonraki Adımlar:
echo.
echo 1. Test çalıştırın:
echo    %PHP_PATH% test_autocall.php
echo.
echo 2. Web paneline gidin:
echo    http://localhost:8009/AsteraWeb/otomatikarama.php
echo.
echo 3. Excel şablonu indirin ve doldurun
echo.
echo 4. Asterisk ayarlarını yapın (isteğe bağlı):
echo    - extensions_autocall.conf dosyasını Asterisk dizinine kopyalayın
echo    - AGI scriptlerini ayarlayın
echo.
echo 5. Zamanlanmış görev ekleyin (isteğe bağlı):
echo    Program: %PHP_PATH%
echo    Argüman: -f "d:\xampp\htdocs\AsteraWeb\cron\process_calls.php"
echo.
echo Detaylı bilgi: OTOMATIK_ARAMA_README.md
echo.
echo Web panelini açmak için bir tuşa basın...
pause >nul
start http://localhost:8009/AsteraWeb/otomatikarama.php
