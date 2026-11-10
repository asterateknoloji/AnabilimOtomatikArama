@echo off
chcp 65001 > nul
color 0A
title Güçlü Şifre Oluşturucu

:start
php generate_password.php %1

echo Başka şifre üretmek ister misiniz? (Y/N)
set /p again=""
if /i "%again%"=="Y" goto start

echo.
echo Şifreleri güvenli bir yerde saklayın!
pause
