# ⚡ Otomatik Arama Sistemi - Hızlı Başlangıç

5 dakikada sistemi kurun ve ilk aramanızı başlatın!

## 📦 1. Kurulum (2 dakika)

### Windows'ta:

```batch
cd d:\xampp\htdocs\AsteraWeb
install_autocall.bat
```

### Manuel Kurulum:

```bash
# Composer bağımlılıkları
composer install

# Klasörleri oluştur
mkdir asterisk\audio\calls
mkdir asterisk\callfiles
mkdir logs
mkdir temp

# Test et
php test_autocall.php
```

## 🎤 2. Ses Dosyalarını Oluştur (1 dakika)

```bash
php generate_menu_audio.php
```

Bu komut şu sesleri oluşturur:
- Hoş geldiniz
- Ana menü
- Operatöre aktarılıyor
- Hoşça kalın
- Geçersiz tuş

## 📞 3. İlk Arama Testi (2 dakika)

### Web Panelini Aç

```
http://localhost:8009/AsteraWeb/otomatikarama.php
```

### Excel Şablonunu İndir

1. "📥 Excel Şablon İndir" butonuna tıkla
2. Dosyayı aç
3. Test için birkaç numara ekle:

| Telefon      | AdSoyad      | Tutar  |
|--------------|--------------|--------|
| 05551234567  | Test Kullanıcı | 100.00 |

4. Dosyayı kaydet

### Excel'i Yükle

1. Web panelinde "Excel Dosyası Seçin" alanına sürükle
2. Dosya otomatik yüklenecek
3. "✓ Başarıyla X kayıt eklendi" mesajını gör

### Aramaları Başlat

1. "📞 Aramaları Başlat" butonuna tıkla
2. Sistem TTS sesleri oluşturacak
3. Asterisk'e call file gönderecek
4. Aramalar başlayacak

## 🔧 4. Asterisk Ayarları (Opsiyonel - İleri Seviye)

### Dialplan Ekle

`C:\Program Files\Asterisk\etc\asterisk\extensions.conf` dosyasına:

```ini
#include extensions_autocall.conf
```

Sonra:

```bash
asterisk -rx "dialplan reload"
```

### AGI Scriptlerini Ayarla

**Seçenek A: FastAGI (Önerilen)**

```bash
cd d:\xampp\htdocs\AsteraWeb\asterisk\agi
php -S localhost:4573
```

**Seçenek B: AGI Dosyaları**

```bash
copy asterisk\agi\*.php "C:\Program Files\Asterisk\var\lib\asterisk\agi-bin\"
```

## ⏰ 5. Otomatik İşleme (Opsiyonel)

### Windows Task Scheduler

**Manuel Çalıştırma** (test için):

```bash
php cron\process_calls.php
```

**Otomatik Çalıştırma** (her 5 dakika):

1. Görev Zamanlayıcısı'nı aç
2. Yeni Temel Görev oluştur:
   - İsim: Otomatik Arama
   - Tetikleyici: Yineleyici
   - Sıklık: 5 dakika
   - Eylem: Program Başlat
   - Program: `C:\xampp\php\php.exe`
   - Argüman: `-f "d:\xampp\htdocs\AsteraWeb\cron\process_calls.php"`

## 🎯 Kullanım Akışı

```
Excel Hazırla
    ↓
Web Panelinde Yükle
    ↓
"Aramaları Başlat" Tıkla
    ↓
Sistem TTS Oluşturur
    ↓
Asterisk Arar
    ↓
Müşteri Dinler
    ↓
1: Tekrar / 2: Operatör
    ↓
Durum Kaydedilir
```

## 📊 İzleme

### Web Panelinde

- **İstatistikler**: Toplam, Bekleyen, Aranıyor, Tamamlanan
- **Filtreler**: Duruma göre kayıtları filtrele
- **Detaylar**: Her aramanın tuş bilgisi, süre vb.

### Log Dosyaları

```bash
# Cron logları
tail -f logs\autocall_2026-01-27.log

# Asterisk logları (Linux)
tail -f /var/log/asterisk/full

# Windows Event Viewer
eventvwr.msc
```

## 🐛 Hızlı Sorun Giderme

### TTS Çalışmıyor

```powershell
# Türkçe ses var mı kontrol et
Add-Type -AssemblyName System.speech
$synth = New-Object System.Speech.Synthesis.SpeechSynthesizer
$synth.GetInstalledVoices() | Select -ExpandProperty VoiceInfo
```

Yoksa: **Ayarlar** > **Konuşma** > **Ses Ekle** > **Türkçe**

### Aramalar Başlamıyor

```bash
# Test scripti çalıştır
php test_autocall.php

# Manuel arama başlat
php cron\process_calls.php
```

### Veritabanı Hatası

```bash
# Bağlantıyı test et
php -r "require 'config.php'; echo 'OK';"
```

### FFmpeg Hatası

```bash
# FFmpeg yüklü mü?
ffmpeg -version

# Yoksa indir: https://www.gyan.dev/ffmpeg/builds/
# PATH'e ekle: C:\ffmpeg\bin
```

## 📚 Daha Fazla Bilgi

- **Detaylı Dokümantasyon**: `OTOMATIK_ARAMA_README.md`
- **Test Scripti**: `php test_autocall.php`
- **Menü Sesleri**: `php generate_menu_audio.php`

## 💡 İpuçları

✓ İlk testlerde kendi numaranızı kullanın
✓ Excel'de maksimum 100-200 kayıt ile başlayın
✓ TTS hızını ayarlamak için `WindowsTTS.php` → `Rate` değerini değiştirin
✓ Arama süresini uzatmak için `extensions_autocall.conf` → `WaitTime` artırın
✓ Log dosyalarını düzenli kontrol edin

## ⚙️ Varsayılan Ayarlar

| Ayar | Değer | Dosya |
|------|-------|-------|
| Deneme Sayısı | 3 | `AutoCallManager.php` |
| Bekleme Süresi | 30 sn | `extensions_autocall.conf` |
| TTS Hızı | 0 (normal) | `WindowsTTS.php` |
| Ses Seviyesi | 100 | `WindowsTTS.php` |
| Arayan ID | 02129999999 | `AsteriskCallFile.php` |
| Eşzamanlı Arama | 50 | `AutoCallManager.php` |

## 🚀 Hemen Başla!

```bash
# 1. Kur
install_autocall.bat

# 2. Test et
php test_autocall.php

# 3. Sesleri oluştur
php generate_menu_audio.php

# 4. Panele git
start http://localhost:8009/AsteraWeb/otomatikarama.php
```

**5 dakika sonra ilk aramanız başlayacak!** 🎉
