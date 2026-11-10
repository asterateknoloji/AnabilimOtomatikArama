@echo off
chcp 65001 >nul
echo ========================================
echo Türkçe Sistem Seslerini Yükleme
echo ========================================
echo.

set SSH_HOST=192.168.181.16
set SSH_PORT=45555
set SSH_USER=root
set SSH_PASS=KKAstera*2o21@.!
set SOUNDS_PATH=/var/lib/asterisk/sounds/tr/

echo [1/2] Türkçe ses klasörünü oluşturuyor...
tools\plink.exe -ssh %SSH_USER%@%SSH_HOST% -P %SSH_PORT% -pw "%SSH_PASS%" "mkdir -p %SOUNDS_PATH%"

echo.
echo [2/2] Ses dosyalarını yüklüyor...
echo.

for %%f in (temp\transfer.gsm temp\goodbye.gsm temp\invalid.gsm temp\queue-thankyou.gsm temp\queue-holdtime.gsm temp\agent-connecting.gsm) do (
    if exist "%%f" (
        echo Yükleniyor: %%~nxf
        tools\pscp.exe -P %SSH_PORT% -pw "%SSH_PASS%" "%%f" %SSH_USER%@%SSH_HOST%:%SOUNDS_PATH%%%~nxf
        if %ERRORLEVEL% EQU 0 (
            echo   ✅ Başarılı
        ) else (
            echo   ❌ Başarısız
        )
    )
)

echo.
echo [3/3] İzinleri ayarlıyor...
tools\plink.exe -ssh %SSH_USER%@%SSH_HOST% -P %SSH_PORT% -pw "%SSH_PASS%" "chmod 644 %SOUNDS_PATH%*.gsm && chown asterisk:asterisk %SOUNDS_PATH%*.gsm"

echo.
echo ========================================
echo Tamamlandı!
echo ========================================
echo.
echo Türkçe sistem sesleri yüklendi:
echo   %SOUNDS_PATH%
echo.
echo Artık dialplan'da şu şekilde kullanabilirsiniz:
echo   Playback(tr/transfer)
echo   Playback(tr/goodbye)
echo   Playback(tr/invalid)
echo.
pause
