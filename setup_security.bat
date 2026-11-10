@echo off
chcp 65001 > nul
color 0A
title Güvenlik Kurulumu

echo.
echo ╔══════════════════════════════════════════════════════╗
echo ║         🔒 Güvenlik Kurulumu                        ║
echo ╚══════════════════════════════════════════════════════╝
echo.

REM 1. .env dosyası kontrolü
echo [1/5] .env dosyası kontrol ediliyor...
if exist ".env" (
    echo ✅ .env dosyası var
) else (
    if exist ".env.example" (
        echo ⚠️  .env dosyası yok, .env.example kopyalanıyor...
        copy .env.example .env
        echo.
        echo ✅ .env dosyası oluşturuldu
        echo ⚠️  ÖNEMLİ: .env dosyasını düzenleyin ve şifreleri değiştirin!
        echo.
        pause
        notepad .env
    ) else (
        echo ❌ .env.example dosyası bulunamadı!
        pause
        exit /b 1
    )
)

REM 2. .htaccess kontrolü
echo.
echo [2/5] .htaccess dosyası kontrol ediliyor...
if exist ".htaccess" (
    echo ✅ .htaccess dosyası var
) else (
    echo ❌ .htaccess dosyası yok!
    echo    Bu dosya web güvenliği için gerekli
    pause
)

REM 3. .gitignore kontrolü
echo.
echo [3/5] .gitignore dosyası kontrol ediliyor...
if exist ".gitignore" (
    echo ✅ .gitignore dosyası var
) else (
    echo ⚠️  .gitignore dosyası yok!
    echo    .env dosyası GIT'e eklenebilir (GÜVENLİK RİSKİ!)
)

REM 4. Dosya izinleri (Windows)
echo.
echo [4/5] Dosya izinleri ayarlanıyor...
echo.

REM .env dosyası için
if exist ".env" (
    echo .env dosyası için izinler ayarlanıyor...
    icacls .env /inheritance:r /grant:r "%USERNAME%:(R,W)" > nul 2>&1
    if errorlevel 1 (
        echo ⚠️  .env izinleri ayarlanamadı (Administrator gerekebilir)
    ) else (
        echo ✅ .env izinleri ayarlandı
    )
)

REM config.php için
if exist "config.php" (
    echo config.php için izinler ayarlanıyor...
    icacls config.php /inheritance:r /grant:r "%USERNAME%:(R,W)" > nul 2>&1
    if errorlevel 1 (
        echo ⚠️  config.php izinleri ayarlanamadı
    ) else (
        echo ✅ config.php izinleri ayarlandı
    )
)

REM config.secure.php için
if exist "config.secure.php" (
    echo config.secure.php için izinler ayarlanıyor...
    icacls config.secure.php /inheritance:r /grant:r "%USERNAME%:(R,W)" > nul 2>&1
    if errorlevel 1 (
        echo ⚠️  config.secure.php izinleri ayarlanamadı
    ) else (
        echo ✅ config.secure.php izinleri ayarlandı
    )
)

REM 5. Güvenlik testi
echo.
echo [5/5] Güvenlik kontrolleri yapılıyor...
echo.

REM PHP dosyalarında config.php kullanımını kontrol et
findstr /S /M "require.*config\.php" *.php > temp_config_usage.txt 2>nul
if exist temp_config_usage.txt (
    set /p choice="⚠️  Bazı dosyalar hala config.php kullanıyor. config.secure.php'ye geçilsin mi? (Y/N): "
    if /i "%choice%"=="Y" (
        echo.
        echo Manuel olarak değiştirmeniz gereken dosyalar:
        type temp_config_usage.txt
        echo.
        echo Değiştirmek için:
        echo require_once 'config.php'; → require_once 'config.secure.php';
    )
    del temp_config_usage.txt
)

REM Şifre güvenlik kontrolü
echo.
echo Varsayılan şifreleri kontrol ediliyor...
if exist ".env" (
    findstr /C:"astera2012" .env > nul
    if not errorlevel 1 (
        echo ❌ UYARI: .env dosyasında varsayılan şifre kullanılıyor!
        echo    Lütfen şifreyi değiştirin: notepad .env
    ) else (
        echo ✅ Varsayılan şifre kullanılmıyor
    )
)

echo.
echo ╔══════════════════════════════════════════════════════╗
echo ║           ✅ GÜVENLİK KURULUMU TAMAMLANDI           ║
echo ╚══════════════════════════════════════════════════════╝
echo.
echo Yapılan İşlemler:
echo ✅ .env dosyası oluşturuldu/kontrol edildi
echo ✅ .htaccess dosyası kontrol edildi
echo ✅ .gitignore dosyası kontrol edildi
echo ✅ Dosya izinleri ayarlandı
echo ✅ Güvenlik kontrolleri yapıldı
echo.
echo Sonraki Adımlar:
echo 1. .env dosyasını düzenle ve şifreleri değiştir
echo 2. config.secure.php kullanmaya geç
echo 3. GUVENLIK_KILAVUZU.md dosyasını oku
echo 4. SSL sertifikası kur (HTTPS)
echo 5. Firewall kurallarını ayarla
echo.
echo Dokümantasyon: GUVENLIK_KILAVUZU.md
echo.

pause
