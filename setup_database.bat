@echo off
chcp 65001 > nul
echo ========================================
echo Veritabanı Kurulumu (MySQL 5.5)
echo ========================================
echo.

cd /d "%~dp0"

set MYSQL_PATH=D:\xampp\mysql\bin\mysql.exe

echo MySQL Server: 192.168.150.11
echo Database: ANABILIMNEWNEW
echo.

echo [1/2] Bağlantı test ediliyor...
"%MYSQL_PATH%" -h 192.168.150.11 -u astera -pastera2012 -e "SELECT VERSION();" 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo [HATA] MySQL bağlantısı başarısız!
    echo Lütfen config.php'deki veritabanı ayarlarını kontrol edin.
    pause
    exit /b 1
)
echo [OK] MySQL bağlantısı başarılı!

echo.
echo [2/2] Veritabanı tablolarını oluşturuyor...
"%MYSQL_PATH%" -h 192.168.150.11 -u astera -pastera2012 ANABILIMNEW < database\schema.sql 2>&1

set DB_ERROR=%ERRORLEVEL%

if %DB_ERROR% NEQ 0 (
    echo [HATA] Veritabanı script'i hata verdi! (Exit Code: %DB_ERROR%^)
    echo.
    echo Manuel kurulum için:
    echo 1. phpMyAdmin'e girin: http://192.168.150.11/phpmyadmin
    echo 2. ANABILIMNEW veritabanını seçin
    echo 3. SQL sekmesine gidin
    echo 4. database\schema.sql dosyasının içeriğini kopyalayıp çalıştırın
    echo.
    pause
    exit /b 1
)

echo [OK] SQL script çalıştırıldı!
echo.

echo [3/3] Kurulum doğrulanıyor...
D:\xampp\php\php.exe test_database.php

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ========================================
    echo KURULUM BAŞARILI!
    echo ========================================
) else (
    echo.
    echo [UYARI] Bazı hatalar oluşmuş olabilir.
    echo Lütfen yukarıdaki çıktıyı kontrol edin.
)


echo.
echo Yeni özellikler:
echo  + Proje bazlı arama yönetimi
echo  + Esnek mesaj şablonları
echo  + Zamanlanmış aramalar
echo  + Detaylı raporlama ve Excel export
echo  + Dosya bazlı takip sistemi
echo.
echo Sisteme erişmek için:
echo   http://localhost/OtomatikArama/otomatikarama.php
echo.
echo Sonraki adım (Opsiyonel):
echo   setup_scheduler.bat ile zamanlanmış arama servisi
echo.
pause
