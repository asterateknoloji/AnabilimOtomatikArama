@echo off
echo ========================================
echo SimpleXLSX Kutuphanesi Yukleniyor...
echo ZIP extension gerektirmez!
echo ========================================
echo.

cd /d "%~dp0"

REM Composer var mi kontrol et
where composer >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo UYARI: Composer bulunamadi!
    echo Manuel yukleme yapiliyor...
    echo.
    
    REM SimpleXLSX'i manuel indir
    powershell -Command "& {Invoke-WebRequest -Uri 'https://raw.githubusercontent.com/shuchkin/simplexlsx/master/src/SimpleXLSX.php' -OutFile 'SimpleXLSX.php'}"
    
    if exist SimpleXLSX.php (
        echo.
        echo ============================================
        echo SimpleXLSX basariyla yuklendi!
        echo ============================================
    ) else (
        echo.
        echo HATA: SimpleXLSX yuklenemedi!
        echo Manuel olarak indirin: https://github.com/shuchkin/simplexlsx
    )
) else (
    echo Composer bulundu, yukleme yapiliyor...
    composer require shuchkin/simplexlsx
    
    echo.
    echo ============================================
    echo SimpleXLSX basariyla yuklendi!
    echo ============================================
)

echo.
pause
