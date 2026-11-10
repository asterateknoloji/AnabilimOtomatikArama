@echo off
chcp 65001 >nul
echo ========================================
echo Windows Task Scheduler Kurulumu
echo ========================================
echo.
echo Bu script Windows görev zamanlayıcıya
echo otomatik tekrar arama görevi ekler.
echo.
echo Görev Ayarları:
echo - İsim: OtomatikAramaTekrar
echo - Sıklık: Her 5 dakikada bir
echo - Komut: php retry_scheduler.php
echo.
pause
echo.

REM Görev adı
set TASK_NAME=OtomatikAramaTekrar

REM PHP ve script yolu
set PHP_PATH=D:\xampp\php\php.exe
set SCRIPT_PATH=D:\xampp\htdocs\OtomatikArama\retry_scheduler.php

REM Varsa önce sil
schtasks /Delete /TN "%TASK_NAME%" /F 2>nul

REM Yeni görev oluştur - Her 5 dakikada bir
schtasks /Create /TN "%TASK_NAME%" /TR "\"%PHP_PATH%\" \"%SCRIPT_PATH%\"" /SC MINUTE /MO 1 /F

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ========================================
    echo ✅ Görev Başarıyla Eklendi!
    echo ========================================
    echo.
    echo Görev Detayları:
    echo - İsim: %TASK_NAME%
    echo - Çalışma: Her 5 dakikada bir
    echo - Komut: %PHP_PATH% %SCRIPT_PATH%
    echo.
    echo Görev Yönetimi:
    echo - Görüntüle: taskschd.msc
    echo - Hemen çalıştır: schtasks /Run /TN "%TASK_NAME%"
    echo - Durdur: schtasks /End /TN "%TASK_NAME%"
    echo - Sil: schtasks /Delete /TN "%TASK_NAME%" /F
    echo.
    echo ========================================
    echo.
    echo Manuel Test:
    echo php %SCRIPT_PATH%
    echo.
) else (
    echo.
    echo ========================================
    echo ❌ Görev Eklenemedi!
    echo ========================================
    echo.
    echo Lütfen bu batch dosyasını YÖNETİCİ olarak çalıştırın:
    echo 1. Sağ tık
    echo 2. "Yönetici olarak çalıştır"
    echo.
)

echo.
pause
