@echo off
chcp 65001 > nul
echo ========================================
echo PuTTY Araçları Kurulumu
echo ========================================
echo.

cd /d "%~dp0"

REM PuTTY araçları için tools klasörü oluştur
if not exist "tools" mkdir tools

REM plink.exe var mı kontrol et
where plink.exe >nul 2>&1
if %ERRORLEVEL% EQU 0 (
    echo [OK] plink.exe sistemde zaten yüklü
    goto :check_pscp
)

REM tools klasöründe var mı kontrol et
if exist "tools\plink.exe" (
    echo [OK] plink.exe tools klasöründe mevcut
    goto :check_pscp
)

echo [!] plink.exe bulunamadı, indiriliyor...
powershell -Command "& {[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12; Invoke-WebRequest -Uri 'https://the.earth.li/~sgtatham/putty/latest/w64/plink.exe' -OutFile 'tools\plink.exe'}"

if exist "tools\plink.exe" (
    echo [OK] plink.exe indirildi
) else (
    echo [HATA] plink.exe indirilemedi!
    goto :error
)

:check_pscp
REM pscp.exe var mı kontrol et
where pscp.exe >nul 2>&1
if %ERRORLEVEL% EQU 0 (
    echo [OK] pscp.exe sistemde zaten yüklü
    goto :test_connection
)

REM tools klasöründe var mı kontrol et
if exist "tools\pscp.exe" (
    echo [OK] pscp.exe tools klasöründe mevcut
    goto :test_connection
)

echo [!] pscp.exe bulunamadı, indiriliyor...
powershell -Command "& {[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12; Invoke-WebRequest -Uri 'https://the.earth.li/~sgtatham/putty/latest/w64/pscp.exe' -OutFile 'tools\pscp.exe'}"

if exist "tools\pscp.exe" (
    echo [OK] pscp.exe indirildi
) else (
    echo [HATA] pscp.exe indirilemedi!
    goto :error
)

:test_connection
echo.
echo ========================================
echo SSH Bağlantı Testi
echo ========================================
echo.

REM Test için config.php'den bilgileri oku
echo SSH bağlantısı test ediliyor...
echo Host: 192.168.181.16
echo Port: 45555
echo User: root
echo.

REM plink ile bağlantı testi (host key'i otomatik kabul et)
if exist "tools\plink.exe" (
    tools\plink.exe -P 45555 -pw "KKAstera*2o21@.!" -batch root@192.168.181.16 "echo SSH baglantisi basarili!" 2>nul
) else (
    plink.exe -P 45555 -pw "KKAstera*2o21@.!" -batch root@192.168.181.16 "echo SSH baglantisi basarili!" 2>nul
)

if %ERRORLEVEL% EQU 0 (
    echo [OK] SSH bağlantısı başarılı!
) else (
    echo [UYARI] SSH bağlantısı test edilemedi veya başarısız!
    echo Lütfen config.php'deki SSH ayarlarını kontrol edin.
)

echo.
echo ========================================
echo Kurulum Tamamlandı!
echo ========================================
echo.
echo PuTTY araçları hazır. Otomatik arama sistemi SSH üzerinden
echo call file ve ses dosyalarını Asterisk sunucusuna gönderebilir.
echo.
pause
exit /b 0

:error
echo.
echo ========================================
echo HATA
echo ========================================
echo.
echo PuTTY araçları indirilemedi veya kurulamadı.
echo Manuel kurulum için: https://www.putty.org/
echo.
pause
exit /b 1
