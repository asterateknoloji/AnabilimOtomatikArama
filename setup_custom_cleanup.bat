@echo off
chcp 65001 >nul
color 0A
cls

echo ═══════════════════════════════════════════════════════════════
echo     🧹 ASTERISK CUSTOM KLASÖR TEMİZLEME KURULUMU
echo ═══════════════════════════════════════════════════════════════
echo.
echo Bu script, Asterisk sunucusundaki /var/lib/asterisk/sounds/custom/
echo klasöründe biriken call_* dosyalarını otomatik olarak temizler.
echo.
echo ⏰ Çalışma Zamanı: Her gece saat 00:00
echo 🗑️  Temizleme Kriteri: 24 saatten eski dosyalar
echo 📋 Log Dosyası: /var/log/asterisk/cleanup_custom.log
echo.
echo ═══════════════════════════════════════════════════════════════
echo.

pause
echo.
echo 🚀 Kurulum başlatılıyor...
echo.

REM Tarayıcıyı aç
start "" "http://localhost:8009/OtomatikArama/setup_custom_cleanup_cron.php"

echo.
echo ═══════════════════════════════════════════════════════════════
echo ✅ Tarayıcı açıldı!
echo ═══════════════════════════════════════════════════════════════
echo.
echo Kurulum sayfası tarayıcınızda açıldı.
echo.
echo Kurulum adımları:
echo 1. ✅ Script Asterisk sunucusuna yüklenecek
echo 2. ✅ Gerekli izinler ayarlanacak
echo 3. ✅ Cron job eklenecek
echo 4. ✅ Log dosyası oluşturulacak
echo 5. ✅ Test çalıştırması yapılacak
echo.
echo Kurulum tamamlandıktan sonra test sayfasını açmak için:
echo    http://localhost:8009/OtomatikArama/test_custom_cleanup.php
echo.
echo Dokümantasyon:
echo    d:\xampp\htdocs\OtomatikArama\CUSTOM_KLASOR_TEMIZLEME.md
echo.
echo ═══════════════════════════════════════════════════════════════
pause
