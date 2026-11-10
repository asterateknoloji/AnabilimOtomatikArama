@echo off
REM =====================================================
REM Otomatik Arama - Zamanlanmis Importlari Baslat
REM Her 2 dakikada calisir
REM =====================================================

cd /d C:\xampp\htdocs\WindowsOtomatikAramason97\cron
C:\xampp\php\php.exe scheduled_calls.php

exit
