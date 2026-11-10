@echo off
REM =====================================================
REM Otomatik Arama - Takili Kayitlari Temizle
REM Her 1 dakikada calisir
REM =====================================================

cd /d C:\xampp\htdocs\WindowsOtomatikAramason97
C:\xampp\php\php.exe cleanup_stuck_calls.php

exit
