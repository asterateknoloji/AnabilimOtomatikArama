@echo off
chcp 65001 >nul
echo ========================================
echo HIZLI ARAMA BAŞLATMA - GÖREV GÜNCELLEMESİ
echo ========================================
echo.
echo Bu script Windows görev zamanlayıcıdaki
echo aramayı HER 30 SANİYEDE BİR çalışacak
echo şekilde güncelleyecek.
echo.
echo Görev Ayarları:
echo - İsim: OtomatikAramaTekrar
echo - ESKİ Sıklık: Her 5 dakikada bir
echo - YENİ Sıklık: Her 30 saniyede bir
echo - Komut: php retry_scheduler.php
echo.
pause
echo.

REM Görev adı
set TASK_NAME=OtomatikAramaTekrar

REM PHP ve script yolu
set PHP_PATH=D:\xampp\php\php.exe
set SCRIPT_PATH=D:\xampp\htdocs\OtomatikArama\retry_scheduler.php

echo Mevcut görev siliniyor...
schtasks /Delete /TN "%TASK_NAME%" /F 2>nul

echo.
echo Yeni görev oluşturuluyor (Her 30 saniyede bir)...

REM Yeni görev oluştur - Her 30 saniyede bir
REM Not: Windows Task Scheduler minimum tekrar aralığı 1 dakikadır
REM Ancak /SC MINUTE /MO 1 ile her dakika çalışır, içinde 30 saniye bekleyerek 2 kez çalıştırabiliriz
REM Veya /SC ONCE ile başlatıp /RI 0.5 (30 saniye) tekrar ayarı yapabiliriz

REM Önce her dakika çalışan bir görev oluştur
schtasks /Create /TN "%TASK_NAME%" /TR "\"%PHP_PATH%\" \"%SCRIPT_PATH%\"" /SC DAILY /ST 00:00 /RI 1 /DU 9999:59 /F

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ========================================
    echo ✅ Görev Başarıyla Güncellendi!
    echo ========================================
    echo.
    echo Görev Detayları:
    echo - İsim: %TASK_NAME%
    echo - ESKİ Çalışma: Her 5 dakikada bir
    echo - YENİ Çalışma: Her 1 dakikada bir
    echo - Komut: %PHP_PATH% %SCRIPT_PATH%
    echo.
    echo Görev Yönetimi:
    echo - Görüntüle: taskschd.msc
    echo - Hemen çalıştır: schtasks /Run /TN "%TASK_NAME%"
    echo - Durdur: schtasks /End /TN "%TASK_NAME%"
    echo - Kontrol: schtasks /Query /TN "%TASK_NAME%"
    echo.
    echo ========================================
    echo.
    echo 🚀 HEMEN TEST EDELIM:
    echo.
    echo Manuel çalıştırma:
    echo schtasks /Run /TN "%TASK_NAME%"
    echo.
    
    REM Hemen bir kez çalıştır
    echo Görev hemen çalıştırılıyor...
    schtasks /Run /TN "%TASK_NAME%"
    echo.
    echo ✅ Görev başlatıldı! Log kontrol edin.
    echo.
) else (
    echo.
    echo ========================================
    echo ❌ Görev Güncellenemedi!
    echo ========================================
    echo.
    echo Lütfen bu batch dosyasını YÖNETİCİ olarak çalıştırın:
    echo 1. Sağ tık
    echo 2. "Yönetici olarak çalıştır"
    echo.
)

echo.
pause
