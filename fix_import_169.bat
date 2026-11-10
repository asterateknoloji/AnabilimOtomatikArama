@echo off
echo ============================================
echo    BOZUK KAYITLARI DUZELT - ImportId: 169
echo ============================================
echo.

REM MySQL bilgileri config.php'den al
set MYSQL_PATH=d:\xampp\mysql\bin\mysql.exe
set DB_NAME=ANABILIM
set DB_USER=root
set DB_PASS=

echo [1/5] Kilitli kayitlari aciyoruz...
"%MYSQL_PATH%" -u%DB_USER% %DB_NAME% -e "UPDATE CALLS SET Locked = 0 WHERE ImportId = 169 AND Locked = 1 AND Status = 1;"
if %ERRORLEVEL% NEQ 0 (
    echo HATA: Kilit kaldirma basarisiz!
    pause
    exit /b 1
)
echo    OK - Kilitler kaldirildi!
echo.

echo [2/5] CallTime degerlerini duzeltiyoruz...
"%MYSQL_PATH%" -u%DB_USER% %DB_NAME% -e "UPDATE CALLS SET CallTime = NOW() WHERE ImportId = 169 AND (CallTime IS NULL OR CallTime = 0 OR CAST(CallTime AS CHAR) = '0');"
if %ERRORLEVEL% NEQ 0 (
    echo HATA: CallTime guncelleme basarisiz!
    pause
    exit /b 1
)
echo    OK - CallTime guncellendi!
echo.

echo [3/5] Aktif degerlerini duzeltiyoruz...
"%MYSQL_PATH%" -u%DB_USER% %DB_NAME% -e "UPDATE CALLS SET Aktif = 1 WHERE ImportId = 169;"
if %ERRORLEVEL% NEQ 0 (
    echo HATA: Aktif guncelleme basarisiz!
    pause
    exit /b 1
)
echo    OK - Aktif degerler duzeltildi!
echo.

echo [4/5] Durum kontrolu yapiyoruz...
echo.
"%MYSQL_PATH%" -u%DB_USER% %DB_NAME% -e "SELECT id, PhoneNumber, Adi, Status, Aktif, CallTime, Locked, CASE WHEN Locked = 1 THEN 'Kilitli' WHEN Aktif != 1 THEN 'Aktif degil' WHEN CallTime > NOW() THEN 'Gelecek' ELSE 'HAZIR' END as durum FROM CALLS WHERE ImportId = 169;"
echo.

echo [5/5] Istatistik...
echo.
"%MYSQL_PATH%" -u%DB_USER% %DB_NAME% -e "SELECT 'HEMEN ARANMALI' as kategori, COUNT(*) as adet FROM CALLS WHERE ImportId = 169 AND Status = 1 AND Aktif = 1 AND Locked = 0 AND (CallTime IS NULL OR CallTime <= NOW()) UNION ALL SELECT 'KILITLI', COUNT(*) FROM CALLS WHERE ImportId = 169 AND Locked = 1 UNION ALL SELECT 'AKTIF DEGIL', COUNT(*) FROM CALLS WHERE ImportId = 169 AND Aktif != 1;"
echo.

echo ============================================
echo    TAMAMLANDI!
echo ============================================
echo.
echo Simdi web arayuzunden "AKTIF" butonuna basin!
echo.

pause
