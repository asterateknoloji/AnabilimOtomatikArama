@echo off
REM Otomatik Arama Sistemi - Windows Kurulum Scripti

REM Script'in bulunduğu dizine geç
cd /d "%~dp0"

echo ========================================
echo   Otomatik Arama Sistemi Kurulumu
echo ========================================
echo.
echo Çalışma dizini: %CD%
echo.

REM PHP kontrolü
echo [1/6] PHP kontrolü...
php -v >nul 2>&1
if errorlevel 1 (
    echo HATA: PHP bulunamadı!
    echo Lütfen PHP'yi PATH'e ekleyin.
    pause
    exit /b 1
)
echo PHP bulundu: OK
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

REM Composer'ı kontrol et
where composer >nul 2>&1
if errorlevel 1 (
    REM Global composer yoksa, lokal composer.phar kullan
    if not exist composer.phar (
        echo Composer indiriliyor...
        php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
        php composer-setup.php
        php -r "unlink('composer-setup.php');"
    )
    echo Composer ile bağımlılıklar yükleniyor...
    php composer.phar install --no-interaction
) else (
    echo Composer ile bağımlılıklar yükleniyor...
    composer install --no-interaction
)

if errorlevel 1 (
    echo HATA: Composer kurulumu başarısız!
    echo.
    echo Lütfen manuel olarak çalıştırın:
    echo composer install
    echo.
    echo veya
    echo.
    echo php composer.phar install
    pause
    exit /b 1
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
ffmpeg -version >nul 2>&1
if errorlevel 1 (
    echo UYARI: FFmpeg bulunamadı!
    echo FFmpeg ses dönüşümü için gereklidir.
    echo Indirme: https://www.gyan.dev/ffmpeg/builds/
    echo PATH'e eklemeyi unutmayın: C:\ffmpeg\bin
    echo.
) else (
    echo FFmpeg: OK
    echo.
)

REM Excel şablonu oluştur
echo [5/6] Excel şablonu oluşturuluyor...
if exist create_excel_template.php (
    php create_excel_template.php 2>nul
    if errorlevel 1 (
        echo UYARI: Excel şablonu oluşturulamadı
        echo PhpSpreadsheet yüklendiğinden emin olun
        echo Manuel oluşturma: php create_excel_template.php
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
powershell -Command "Add-Type -AssemblyName System.speech; $s = New-Object System.Speech.Synthesis.SpeechSynthesizer; $voices = $s.GetInstalledVoices() | Where-Object { $_.VoiceInfo.Culture.Name -eq 'tr-TR' }; if ($voices.Count -gt 0) { Write-Host 'Türkçe TTS: OK' } else { Write-Host 'UYARI: Türkçe TTS sesi bulunamadı!' }"
echo.

REM Özet
echo ========================================
echo   Kurulum Tamamlandı!
echo ========================================
echo.
echo Sonraki Adımlar:
echo.
echo 1. Asterisk dialplan ayarlarını yapın
echo    - extensions_autocall.conf dosyasını /etc/asterisk/ dizinine kopyalayın
echo    - extensions.conf içine #include extensions_autocall.conf ekleyin
echo.
echo 2. AGI scriptlerini ayarlayın
echo    - asterisk\agi\*.php dosyalarını Asterisk AGI dizinine kopyalayın
echo    - Veya FastAGI sunucusu başlatın
echo.
echo 3. Zamanlanmış görev ekleyin
echo    - Görev Zamanlayıcısı'nda yeni görev oluşturun
echo    - Program: C:\xampp\php\php.exe
echo    - Argüman: -f "d:\xampp\htdocs\AsteraWeb\cron\process_calls.php"
echo    - Tetikleyici: Her 5 dakika
echo.
echo 4. Web paneline erişin
echo    http://localhost:8009/AsteraWeb/otomatikarama.php
echo.
echo Detaylı bilgi: OTOMATIK_ARAMA_README.md
echo.
pause
