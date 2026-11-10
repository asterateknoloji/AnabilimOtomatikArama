@echo off
chcp 65001 >nul
echo ========================================
echo ANLIK ARAMA BAŞLATMA SİSTEMİ
echo ========================================
echo.
echo Bu script, görev zamanlayıcısını tamamen KALDIRIR
echo ve web tabanlı anlık başlatma kullanır.
echo.
echo ÖNCESİ:
echo - Görev zamanlayıcı her 5 dakikada kontrol eder
echo - Excel yükle → 5 dakika bekle → arama başla
echo.
echo SONRASI:
echo - Görev zamanlayıcı YOK (manuel tetikleme)
echo - Excel yükle → Web üzerinden başlat → ANINDA başla
echo.
echo NOT: Web sayfasından "Arama Başlat" butonuna bastığınızda
echo      hemen startAllImportCalls() çağrılır ve 5 saniye içinde
echo      tüm aramalar başlar!
echo.
pause
echo.

REM Görev adı
set TASK_NAME=OtomatikAramaTekrar
set TASK_NAME2=OtomatikAramaServisi

echo Mevcut görevler siliniyor...
schtasks /Delete /TN "%TASK_NAME%" /F 2>nul
schtasks /Delete /TN "%TASK_NAME2%" /F 2>nul

echo.
echo ========================================
echo ✅ Görevler Silindi!
echo ========================================
echo.
echo Artık sistem TAMAMEN WEB TABANLI çalışıyor!
echo.
echo KULLANIM:
echo.
echo 1. Excel yükleyin
echo 2. Şablon + Proje seçin
echo 3. "ARAMA BAŞLAT" butonuna tıklayın
echo 4. ✅ 5 SANİYE içinde aramalar başlar!
echo.
echo Görev zamanlayıcı artık KULLANILMIYOR.
echo Tüm kontrol WEB ARAYÜZÜNDE!
echo.
echo ========================================
echo.
echo 💡 İPUCU:
echo Eğer otomatik retry istiyorsanız, görev zamanlayıcıyı
echo tekrar ekleyebilirsiniz (her 30-60 dakikada bir).
echo.
echo Ama HEMEN BAŞLATMA için görev zamanlayıcıya GEREK YOK!
echo.
pause
