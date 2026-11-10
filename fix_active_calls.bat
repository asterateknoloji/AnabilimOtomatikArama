@echo off
chcp 65001 >nul
echo ========================================
echo AKTİF KAYITLARI HEMEN ARA - SQL FIX
echo ========================================
echo.
echo Bu script Aktif=1 olan ama CallTime NULL
echo veya gelecek tarihli kayıtları HEMEN
echo aranacak şekilde günceller (CallTime=NOW)
echo.
pause
echo.

set MYSQL_PATH=D:\xampp\mysql\bin\mysql.exe
set DB_USER=root
set DB_PASS=
set DB_NAME=ANABILIM
set SQL_FILE=%~dp0fix_active_calls_now.sql

echo SQL çalıştırılıyor...
echo.

"%MYSQL_PATH%" -u%DB_USER% %DB_NAME% < "%SQL_FILE%"

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ========================================
    echo ✅ Güncelleme Başarılı!
    echo ========================================
    echo.
    echo Aktif=1 olan kayıtlar HEMEN aranacak şekilde ayarlandı!
    echo CallTime = NOW() yapıldı.
    echo.
    echo ŞİMDİ NE YAPACAĞIZ?
    echo.
    echo SEÇENEK 1: Web Arayüzünden "Arama Başlat" tıkla
    echo SEÇENEK 2: Manuel PHP script çalıştır (aşağıda)
    echo SEÇENEK 3: Görev zamanlayıcının çalışmasını bekle (max 5 dk)
    echo.
    echo ========================================
    echo.
) else (
    echo.
    echo ========================================
    echo ❌ Hata Oluştu!
    echo ========================================
    echo.
)

pause
