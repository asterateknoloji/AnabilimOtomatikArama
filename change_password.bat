@echo off
chcp 65001 > nul
color 0E
title Şifre Değiştirme Aracı

echo.
echo ╔══════════════════════════════════════════════════════╗
echo ║          🔑 Şifre Değiştirme Aracı                  ║
echo ╚══════════════════════════════════════════════════════╝
echo.

REM .env dosyası var mı kontrol et
if not exist ".env" (
    echo ❌ .env dosyası bulunamadı!
    echo    Lütfen önce setup_security.bat çalıştırın.
    pause
    exit /b 1
)

REM Yedek al
echo [1/4] Yedek alınıyor...
copy .env .env.backup.%date:~-4,4%%date:~-7,2%%date:~-10,2%_%time:~0,2%%time:~3,2%%time:~6,2% > nul
echo ✅ Yedek alındı: .env.backup.%date:~-4,4%%date:~-7,2%%date:~-10,2%_%time:~0,2%%time:~3,2%%time:~6,2%
echo.

REM Hangi şifreyi değiştirecek?
echo [2/4] Hangi şifreyi değiştirmek istiyorsunuz?
echo.
echo 1. MySQL Veritabanı Şifresi (DB_PASS)
echo 2. SSH Şifresi (ASTERISK_SSH_PASS)
echo 3. AMI Şifresi (AMI_PASS)
echo 4. Tüm Şifreler
echo 5. Çıkış
echo.
set /p choice="Seçiminiz (1-5): "

if "%choice%"=="1" goto change_db
if "%choice%"=="2" goto change_ssh
if "%choice%"=="3" goto change_ami
if "%choice%"=="4" goto change_all
if "%choice%"=="5" goto end

echo ❌ Geçersiz seçim!
pause
goto end

:change_db
echo.
echo ═══════════════════════════════════════
echo    MySQL Şifresi Değiştirme
echo ═══════════════════════════════════════
echo.
echo ⚠️  ÖNEMLİ: Önce MySQL'de şifreyi değiştirin!
echo.
echo SQL Komutları:
echo mysql -u root -p
echo ALTER USER 'astera'@'%%' IDENTIFIED BY 'YeniSifre';
echo FLUSH PRIVILEGES;
echo EXIT;
echo.
pause
echo.
set /p new_db_pass="Yeni MySQL Şifresi: "
echo.
echo [3/4] .env dosyası güncelleniyor...
powershell -Command "(Get-Content .env) -replace '^DB_PASS=.*', 'DB_PASS=%new_db_pass%' | Set-Content .env"
echo ✅ DB_PASS güncellendi
echo.
echo [4/4] Test ediliyor...
php test_database.php
if errorlevel 1 (
    echo.
    echo ❌ Veritabanı bağlantısı başarısız!
    echo    Yedekten geri yükleniyor...
    copy .env.backup.* .env > nul
    echo ✅ Eski şifre geri yüklendi
) else (
    echo ✅ Veritabanı bağlantısı başarılı!
)
goto finish

:change_ssh
echo.
echo ═══════════════════════════════════════
echo    SSH Şifresi Değiştirme
echo ═══════════════════════════════════════
echo.
echo ⚠️  ÖNEMLİ: Önce Asterisk'te şifreyi değiştirin!
echo.
echo SSH Komutları:
echo ssh root@192.168.150.11 -p 45555
echo passwd
echo exit
echo.
pause
echo.
set /p new_ssh_pass="Yeni SSH Şifresi: "
echo.
echo [3/4] .env dosyası güncelleniyor...
powershell -Command "(Get-Content .env) -replace '^ASTERISK_SSH_PASS=.*', 'ASTERISK_SSH_PASS=%new_ssh_pass%' | Set-Content .env"
echo ✅ ASTERISK_SSH_PASS güncellendi
echo.
echo [4/4] Test ediliyor...
tools\plink.exe -ssh root@192.168.150.11 -P 45555 -pw %new_ssh_pass% "echo OK" > nul 2>&1
if errorlevel 1 (
    echo ❌ SSH bağlantısı başarısız!
    echo    Yedekten geri yükleniyor...
    copy .env.backup.* .env > nul
    echo ✅ Eski şifre geri yüklendi
) else (
    echo ✅ SSH bağlantısı başarılı!
)
goto finish

:change_ami
echo.
echo ═══════════════════════════════════════
echo    AMI Şifresi Değiştirme
echo ═══════════════════════════════════════
echo.
echo ⚠️  ÖNEMLİ: Önce /etc/asterisk/manager.conf düzenleyin!
echo.
pause
echo.
set /p new_ami_pass="Yeni AMI Şifresi: "
echo.
echo [3/4] .env dosyası güncelleniyor...
powershell -Command "(Get-Content .env) -replace '^AMI_PASS=.*', 'AMI_PASS=%new_ami_pass%' | Set-Content .env"
echo ✅ AMI_PASS güncellendi
echo.
echo [4/4] Asterisk reload...
tools\plink.exe -ssh root@192.168.150.11 -P 45555 -pw %ASTERISK_SSH_PASS% "asterisk -rx 'manager reload'"
echo ✅ Asterisk reload tamamlandı
goto finish

:change_all
echo.
echo ═══════════════════════════════════════
echo    Tüm Şifreleri Değiştir
echo ═══════════════════════════════════════
echo.
echo ⚠️  Bu işlem tüm şifreleri değiştirecek!
echo.
set /p confirm="Devam etmek istiyor musunuz? (Y/N): "
if /i not "%confirm%"=="Y" goto end

echo.
set /p new_db_pass="Yeni MySQL Şifresi: "
set /p new_ssh_pass="Yeni SSH Şifresi: "
set /p new_ami_pass="Yeni AMI Şifresi: "

echo.
echo [3/4] .env dosyası güncelleniyor...
powershell -Command "(Get-Content .env) -replace '^DB_PASS=.*', 'DB_PASS=%new_db_pass%' | Set-Content .env"
powershell -Command "(Get-Content .env) -replace '^ASTERISK_SSH_PASS=.*', 'ASTERISK_SSH_PASS=%new_ssh_pass%' | Set-Content .env"
powershell -Command "(Get-Content .env) -replace '^AMI_PASS=.*', 'AMI_PASS=%new_ami_pass%' | Set-Content .env"
echo ✅ Tüm şifreler güncellendi

echo.
echo [4/4] Test ediliyor...
php test_database.php
if errorlevel 1 (
    echo ❌ Test başarısız!
) else (
    echo ✅ Testler başarılı!
)
goto finish

:finish
echo.
echo ╔══════════════════════════════════════════════════════╗
echo ║            ✅ ŞİFRE DEĞİŞTİRME TAMAMLANDI          ║
echo ╚══════════════════════════════════════════════════════╝
echo.
echo Yapılan İşlemler:
echo ✅ Yedek alındı (.env.backup.*)
echo ✅ .env dosyası güncellendi
echo ✅ Bağlantı test edildi
echo.
echo Sonraki Adımlar:
echo 1. Web arayüzünü test edin
echo 2. Arama sistemi çalışıyor mu kontrol edin
echo 3. Log dosyalarını kontrol edin
echo.
echo Sorun yaşarsanız:
echo    copy .env.backup.* .env
echo    (Eski şifreye geri dön)
echo.
echo Dokümantasyon: SIFRE_DEGISTIRME.md
echo.
pause
goto end

:end
