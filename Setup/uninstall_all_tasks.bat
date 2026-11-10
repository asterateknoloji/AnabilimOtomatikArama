@echo off
REM =====================================================
REM TUM GOREVLERI SIL
REM Bu dosyayi YONETICI olarak calistirin!
REM =====================================================

echo ==========================================
echo OTOMATIK ARAMA SISTEMI - GOREV KALDIR
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

echo Gorevler kaldiriliyor...
echo.

schtasks /delete /tn "OtomatikAramaUnlock" /f
schtasks /delete /tn "OtomatikAramaTekrar" /f
schtasks /delete /tn "OtomatikAramaZamanlanmis" /f

echo.
echo ==========================================
echo TUM GOREVLER KALDIRILDI!
echo ==========================================
echo.
pause
