@echo off
chcp 65001 > nul
echo ========================================
echo Asterisk Dialplan Kurulumu
echo ========================================
echo.

cd /d "%~dp0"

REM extensions_autocall.conf dosyasını Asterisk sunucusuna kopyala
echo [1/3] extensions_autocall.conf dosyası Asterisk sunucusuna gönderiliyor...

tools\pscp.exe -P 45555 -pw "KKAstera*2o21@.!" -batch "asterisk\extensions_autocall.conf" "root@192.168.181.16:/etc/asterisk/extensions_autocall.conf"

if %ERRORLEVEL% NEQ 0 (
    echo [HATA] Dosya gönderilemedi!
    pause
    exit /b 1
)

echo [OK] extensions_autocall.conf gönderildi

REM extensions.conf'a include ekle (eğer yoksa)
echo.
echo [2/3] extensions.conf'a include ekleniyor...

tools\plink.exe -P 45555 -pw "KKAstera*2o21@.!" -batch root@192.168.181.16 "grep -q 'extensions_autocall.conf' /etc/asterisk/extensions.conf || echo '#include extensions_autocall.conf' >> /etc/asterisk/extensions.conf"

if %ERRORLEVEL% EQU 0 (
    echo [OK] Include eklendi
) else (
    echo [UYARI] Include eklenirken hata oluştu (zaten mevcut olabilir)
)

REM AGI scriptlerini kopyala
echo.
echo [3/3] AGI scriptleri gönderiliyor...

tools\pscp.exe -P 45555 -pw "KKAstera*2o21@.!" -batch "asterisk\agi\update_call_status.php" "root@192.168.181.16:/var/lib/asterisk/agi-bin/update_call_status.php"
tools\pscp.exe -P 45555 -pw "KKAstera*2o21@.!" -batch "asterisk\agi\update_call_dtmf.php" "root@192.168.181.16:/var/lib/asterisk/agi-bin/update_call_dtmf.php"
tools\pscp.exe -P 45555 -pw "KKAstera*2o21@.!" -batch "asterisk\agi\update_call_end.php" "root@192.168.181.16:/var/lib/asterisk/agi-bin/update_call_end.php"

if %ERRORLEVEL% EQU 0 (
    echo [OK] AGI scriptleri gönderildi
) else (
    echo [HATA] AGI scriptleri gönderilemedi!
)

REM AGI scriptlerine execute izni ver
echo.
echo [4/4] AGI scriptlerine execute izni veriliyor...

tools\plink.exe -P 45555 -pw "KKAstera*2o21@.!" -batch root@192.168.181.16 "chmod +x /var/lib/asterisk/agi-bin/update_call_*.php"

if %ERRORLEVEL% EQU 0 (
    echo [OK] Execute izinleri verildi
)

REM Asterisk'i reload et
echo.
echo [5/5] Asterisk dialplan reload ediliyor...

tools\plink.exe -P 45555 -pw "KKAstera*2o21@.!" -batch root@192.168.181.16 "asterisk -rx 'dialplan reload'"

if %ERRORLEVEL% EQU 0 (
    echo [OK] Asterisk dialplan reload edildi
) else (
    echo [HATA] Asterisk reload edilemedi!
)

echo.
echo ========================================
echo Kurulum Tamamlandı!
echo ========================================
echo.
echo Asterisk dialplan ve AGI scriptleri başarıyla yüklendi.
echo Artık otomatik arama sistemi tam olarak çalışmaya hazır.
echo.
echo Test etmek için: http://localhost:8009/OtomatikArama/otomatikarama.php
echo.
pause
