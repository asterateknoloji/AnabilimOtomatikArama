@echo off
REM =====================================================
REM TUM GOREVLERI OTOMATIK KUR
REM Bu dosyayi YONETICI olarak calistirin!
REM =====================================================

echo ==========================================
echo OTOMATIK ARAMA SISTEMI - GOREV KURULUM
echo ==========================================
echo.

REM Yonetici kontrolu
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo HATA: Bu dosya YONETICI olarak calistirilmali!
    echo.
    echo Sag tiklayin ve "Yonetici olarak calistir" secin.
    echo.
    pause
    exit /b 1
)

echo Gorevler kuruluyor...
echo.

REM Gorev 1: cleanup_stuck_calls (Her 1 dakika)
echo [1/3] Takili kayitlari temizle gorevi...
schtasks /delete /tn "OtomatikAramaUnlock" /f >nul 2>&1
schtasks /create /tn "OtomatikAramaUnlock" /tr "C:\xampp\htdocs\WindowsOtomatikAramason97\task1_cleanup.bat" /sc minute /mo 1 /ru SYSTEM /f
if %errorLevel% equ 0 (
    echo   OK - OtomatikAramaUnlock kuruldu
) else (
    echo   HATA - OtomatikAramaUnlock kurulamadi!
)

REM Gorev 2: retry_scheduler (Her 5 dakika)
echo [2/3] Otelenen aramalari baslat gorevi...
schtasks /delete /tn "OtomatikAramaTekrar" /f >nul 2>&1
schtasks /create /tn "OtomatikAramaTekrar" /tr "C:\xampp\htdocs\WindowsOtomatikAramason97\task2_retry.bat" /sc minute /mo 5 /ru SYSTEM /f
if %errorLevel% equ 0 (
    echo   OK - OtomatikAramaTekrar kuruldu
) else (
    echo   HATA - OtomatikAramaTekrar kurulamadi!
)

REM Gorev 3: scheduled_calls (Her 2 dakika)
echo [3/3] Zamanlanmis importlari baslat gorevi...
schtasks /delete /tn "OtomatikAramaZamanlanmis" /f >nul 2>&1
schtasks /create /tn "OtomatikAramaZamanlanmis" /tr "C:\xampp\htdocs\WindowsOtomatikAramason97\task3_scheduled.bat" /sc minute /mo 2 /ru SYSTEM /f
if %errorLevel% equ 0 (
    echo   OK - OtomatikAramaZamanlanmis kuruldu
) else (
    echo   HATA - OtomatikAramaZamanlanmis kurulamadi!
)

echo.
echo ==========================================
echo KURULUM TAMAMLANDI!
echo ==========================================
echo.
echo Kurulu gorevler:
schtasks /query /tn "OtomatikArama*"
echo.
echo Gorevler aktif ve calisiyor!
echo.
pause
