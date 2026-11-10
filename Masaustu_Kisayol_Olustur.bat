@echo off
chcp 65001 >nul
echo ═══════════════════════════════════════════════════════════════
echo     📍 OTOMATIK ARAMA SİSTEMİ - MASAÜSTÜ KISAYOLU OLUŞTUR
echo ═══════════════════════════════════════════════════════════════
echo.

REM Chrome yolunu bul
set CHROME_PATH=""
if exist "C:\Program Files\Google\Chrome\Application\chrome.exe" (
    set CHROME_PATH="C:\Program Files\Google\Chrome\Application\chrome.exe"
) else if exist "C:\Program Files (x86)\Google\Chrome\Application\chrome.exe" (
    set CHROME_PATH="C:\Program Files (x86)\Google\Chrome\Application\chrome.exe"
) else (
    echo ❌ Chrome bulunamadı!
    echo.
    echo Edge ile kısayol oluşturmak için 2'ye basın:
    echo 1 - Farklı bir tarayıcı yolu gir
    echo 2 - Edge kullan
    echo 3 - Çıkış
    echo.
    choice /C 123 /N /M "Seçiminiz: "
    if errorlevel 3 goto :EOF
    if errorlevel 2 goto :USE_EDGE
    if errorlevel 1 goto :CUSTOM_BROWSER
)

:CREATE_SHORTCUT_CHROME
echo.
echo ✅ Chrome bulundu: %CHROME_PATH%
echo.
echo Masaüstü kısayolu oluşturuluyor...
echo.

REM PowerShell ile kısayol oluştur
powershell -Command "$WshShell = New-Object -ComObject WScript.Shell; $Shortcut = $WshShell.CreateShortcut('%USERPROFILE%\Desktop\Otomatik Arama Sistemi.lnk'); $Shortcut.TargetPath = %CHROME_PATH%; $Shortcut.Arguments = '--new-window http://192.168.181.139:8009/OtomatikArama/'; $Shortcut.WorkingDirectory = 'C:\'; $Shortcut.Description = 'Otomatik Arama Sistemi - Network IP ile Erişim'; $Shortcut.Save()"

if %errorlevel% equ 0 (
    echo.
    echo ═══════════════════════════════════════════════════════════════
    echo ✅ BAŞARILI!
    echo ═══════════════════════════════════════════════════════════════
    echo.
    echo Masaüstünüzde "Otomatik Arama Sistemi" kısayolu oluşturuldu.
    echo.
    echo Kısayola çift tıklayarak sisteme erişebilirsiniz:
    echo 📍 http://192.168.181.139:8009/OtomatikArama/
    echo.
) else (
    echo.
    echo ❌ Kısayol oluşturulamadı!
    echo.
)

goto :FINISH

:USE_EDGE
echo.
echo ✅ Edge kullanılacak
echo.
set CHROME_PATH="C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe"
if not exist %CHROME_PATH% (
    set CHROME_PATH="C:\Program Files\Microsoft\Edge\Application\msedge.exe"
)

echo Masaüstü kısayolu oluşturuluyor...
echo.

powershell -Command "$WshShell = New-Object -ComObject WScript.Shell; $Shortcut = $WshShell.CreateShortcut('%USERPROFILE%\Desktop\Otomatik Arama Sistemi.lnk'); $Shortcut.TargetPath = %CHROME_PATH%; $Shortcut.Arguments = '--new-window http://192.168.181.139:8009/OtomatikArama/'; $Shortcut.WorkingDirectory = 'C:\'; $Shortcut.Description = 'Otomatik Arama Sistemi - Network IP ile Erişim'; $Shortcut.Save()"

if %errorlevel% equ 0 (
    echo ✅ BAŞARILI! Masaüstünde kısayol oluşturuldu.
) else (
    echo ❌ Kısayol oluşturulamadı!
)
goto :FINISH

:CUSTOM_BROWSER
echo.
set /p CHROME_PATH="Tarayıcı .exe yolunu girin: "
goto :CREATE_SHORTCUT_CHROME

:FINISH
echo.
echo ═══════════════════════════════════════════════════════════════
echo 📝 ÖNEMLİ NOTLAR:
echo ═══════════════════════════════════════════════════════════════
echo.
echo ✅ YENİ URL: http://192.168.181.139:8009/OtomatikArama/
echo ❌ ESKİ URL: http://localhost:8009/OtomatikArama/ (kullanmayın!)
echo.
echo Artık gerçek IP adresiniz (192.168.181.139) loglanacak.
echo.
echo Test için:
echo 1. Masaüstündeki kısayola çift tıklayın
echo 2. Login yapın
echo 3. Activity Logs'da IP'nizi kontrol edin
echo.
echo ═══════════════════════════════════════════════════════════════
pause
