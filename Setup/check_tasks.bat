@echo off
REM =====================================================
REM GOREVLERI KONTROL ET
REM =====================================================

echo ==========================================
echo OTOMATIK ARAMA SISTEMI - GOREV KONTROL
echo ==========================================
echo.

echo Kurulu gorevler:
echo.
schtasks /query /tn "OtomatikArama*" /fo LIST

echo.
echo ==========================================
echo.
pause
