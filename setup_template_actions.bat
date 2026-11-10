@echo off
chcp 65001 > nul
echo ========================================
echo Şablon Tuş Davranışları Kurulumu
echo ========================================

set MYSQL_HOST=192.168.181.16
set MYSQL_USER=astera
set MYSQL_PASS=astera2012
set MYSQL_DB=ANABILIM

echo.
echo [1/2] Veritabanı tablosunu güncelliyoruz...

D:\xampp\mysql\bin\mysql.exe -h %MYSQL_HOST% -u %MYSQL_USER% -p%MYSQL_PASS% %MYSQL_DB% < database\add_template_actions.sql

if %ERRORLEVEL% EQU 0 (
    echo [OK] Veritabanı güncellendi!
) else (
    echo [HATA] Veritabanı güncellenemedi!
    echo.
    echo Manuel kurulum için:
    echo 1. phpMyAdmin'e girin: http://192.168.181.16/phpmyadmin
    echo 2. ANABILIM veritabanını seçin
    echo 3. SQL sekmesine gidin
    echo 4. database\add_template_actions.sql dosyasının içeriğini kopyalayıp çalıştırın
)

echo.
echo [2/2] Test ediliyor...

D:\xampp\mysql\bin\mysql.exe -h %MYSQL_HOST% -u %MYSQL_USER% -p%MYSQL_PASS% -e "SELECT id, TemplateName, Key1Action, Key2TransferNumber, TimeoutSeconds FROM ANABILIM.MESSAGE_TEMPLATES LIMIT 5;"

echo.
echo ========================================
echo Kurulum Tamamlandı!
echo ========================================
echo.
echo Şimdi şablonları düzenleyebilirsiniz:
echo http://localhost:8009/OtomatikArama/sablonlar.php
echo.

pause
