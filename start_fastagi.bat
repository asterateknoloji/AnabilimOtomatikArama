@echo off
echo ========================================
echo   FastAGI Sunucusu Başlatılıyor...
echo ========================================
echo.
echo Sunucu: http://localhost:4573
echo Log: logs\fastagi_*.log
echo.
echo Durdurmak için: CTRL+C
echo.
echo ========================================
cd asterisk\agi
php -S localhost:4573 fastagi_server.php
pause
