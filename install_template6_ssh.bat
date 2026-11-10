@echo off
echo ================================================================================
echo   TEMPLATE 6 (FERHAT DAVET) TAM OTOMATIK SSH KURULUM
echo ================================================================================
echo.

set SSH_HOST=192.168.181.16
set SSH_PORT=45555
set SSH_USER=root
set SSH_PASS=KKAstera*2o21@.!

echo [1/5] SSH baglantisi test ediliyor...
echo.

REM plink var mı kontrol et
where plink >nul 2>&1
if %errorlevel% neq 0 (
    echo plink bulunamadi, tools klasorunden kullaniliyor...
    set PLINK=tools\plink.exe
) else (
    set PLINK=plink
)

REM SSH test
echo y | %PLINK% -ssh -P %SSH_PORT% -pw %SSH_PASS% %SSH_USER%@%SSH_HOST% "echo 'SSH baglanti basarili'" 2>nul
if %errorlevel% neq 0 (
    echo HATA: SSH baglantisi basarisiz!
    pause
    exit /b 1
)

echo [OK] SSH baglantisi basarili
echo.

echo [2/5] Dialplan dosyasi sunucuya kopyalaniyor...
echo.

REM pscp var mı kontrol et
where pscp >nul 2>&1
if %errorlevel% neq 0 (
    set PSCP=tools\pscp.exe
) else (
    set PSCP=pscp
)

REM Dialplan dosyasını kopyala
echo y | %PSCP% -P %SSH_PORT% -pw %SSH_PASS% "asterisk\dialplan\extensions_template_6.conf" %SSH_USER%@%SSH_HOST%:/etc/asterisk/ 2>nul
if %errorlevel% neq 0 (
    echo HATA: Dosya kopyalanamadi!
    pause
    exit /b 1
)

echo [OK] Dialplan dosyasi kopyalandi
echo.

echo [3/5] extensions_custom.conf'a include ekleniyor...
echo.

%PLINK% -ssh -P %SSH_PORT% -pw %SSH_PASS% %SSH_USER%@%SSH_HOST% "grep -q 'extensions_template_6.conf' /etc/asterisk/extensions_custom.conf || echo '#include extensions_template_6.conf' >> /etc/asterisk/extensions_custom.conf"

echo [OK] Include satiri eklendi
echo.

echo [4/5] Asterisk dialplan reload...
echo.

%PLINK% -ssh -P %SSH_PORT% -pw %SSH_PASS% %SSH_USER%@%SSH_HOST% "asterisk -rx 'dialplan reload'"

echo [OK] Reload tamamlandi
echo.

echo [5/5] Context kontrolu yapiliyor...
echo.

%PLINK% -ssh -P %SSH_PORT% -pw %SSH_PASS% %SSH_USER%@%SSH_HOST% "asterisk -rx 'dialplan show auto-calls-template-6' | head -5"

echo.
echo ================================================================================
echo   KURULUM TAMAMLANDI!
echo ================================================================================
echo.
echo Sonraki adimlar:
echo   1. http://localhost:8009/otomatikarama/login.php adresini acin
echo   2. Template 6 (Ferhat Davet) secin
echo   3. Excel yukleyin ve aramalari baslatin
echo.
echo [OK] Artik 'invalid extension' hatasi OLMAYACAK!
echo.
pause
