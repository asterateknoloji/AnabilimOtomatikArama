# 🎵 FFmpeg Kurulum Kılavuzu (Windows)

## 📋 FFmpeg Nedir?

FFmpeg, ses ve video dosyalarını dönüştürmek için kullanılan güçlü bir araçtır. Bu sistemde:
- **MP3 → WAV** dönüşümü
- **Asterisk formatına** (8kHz, 16-bit, Mono) dönüştürme

için kullanılır.

---

## ⚠️ FFmpeg Olmadan

Eğer FFmpeg kurulu değilse:
- ✅ **WAV dosyaları** yüklenebilir
- ❌ **MP3 dosyaları** dönüştürülemez

---

## 🚀 HIZLI KURULUM (Önerilen)

### Adım 1: FFmpeg İndir

1. Şu sayfaya gidin:
   ```
   https://www.gyan.dev/ffmpeg/builds/
   ```

2. **"ffmpeg-release-essentials.zip"** dosyasını indirin
   - Veya doğrudan link: https://www.gyan.dev/ffmpeg/builds/ffmpeg-release-essentials.zip

### Adım 2: Klasöre Çıkart

```
C:\ffmpeg\
```

Klasör yapısı şöyle olmalı:
```
C:\ffmpeg\
├── bin\
│   ├── ffmpeg.exe  ✅
│   ├── ffplay.exe
│   └── ffprobe.exe
├── doc\
└── presets\
```

### Adım 3: Config Dosyasını Güncelle

`d:\xampp\htdocs\OtomatikArama\config.php` dosyasını açın:

**Önce:**
```php
define('FFMPEG_PATH', 'ffmpeg');
```

**Sonra:**
```php
define('FFMPEG_PATH', 'C:/ffmpeg/bin/ffmpeg.exe');
```

⚠️ **Dikkat:** Windows'da `/` kullanın, `\` değil!

### Adım 4: Test Et

1. Komut satırını açın (CMD)
2. Şunu yazın:
   ```cmd
   C:\ffmpeg\bin\ffmpeg.exe -version
   ```

✅ **Başarılı:** Versiyon bilgisi görünecek
❌ **Hata:** Dosya yolu yanlış

---

## 🔄 ALTERNATİF: PATH'e Ekle (İsteğe Bağlı)

FFmpeg'i sistem PATH'ine eklerseniz, `config.php`'de değişiklik gerekmez.

### Windows 10/11:

1. **Başlat** → "Ortam Değişkenleri" ara
2. **Sistem değişkenlerini düzenle** → **Ortam Değişkenleri**
3. **Sistem Değişkenleri** → **Path** → **Düzenle**
4. **Yeni** → `C:\ffmpeg\bin` yazın
5. **Tamam** → **Tamam** → **Tamam**
6. CMD'yi **yeniden başlatın**

Test:
```cmd
ffmpeg -version
```

Başarılıysa, `config.php`'de:
```php
define('FFMPEG_PATH', 'ffmpeg');  // Bu kalabilir
```

---

## 🧪 TEST KOMUTU

### Manuel Test:

```cmd
cd C:\ffmpeg\bin

ffmpeg.exe -i "test.mp3" -acodec pcm_s16le -ar 8000 -ac 1 "test.wav"
```

**Parametreler:**
- `-i "test.mp3"` : Girdi dosyası
- `-acodec pcm_s16le` : PCM 16-bit codec
- `-ar 8000` : 8kHz sample rate
- `-ac 1` : Mono (tek kanal)
- `"test.wav"` : Çıktı dosyası

---

## ✅ SİSTEM KONTROLÜ

`d:\xampp\htdocs\OtomatikArama\` klasöründe `test_ffmpeg.php` oluşturun:

```php
<?php
require_once 'config.php';

echo "FFmpeg Test\n";
echo "===========\n\n";

$ffmpegPath = defined('FFMPEG_PATH') ? FFMPEG_PATH : 'ffmpeg';
echo "FFmpeg Yolu: $ffmpegPath\n\n";

exec($ffmpegPath . " -version 2>&1", $output, $returnCode);

if ($returnCode === 0) {
    echo "✅ FFmpeg ÇALIŞIYOR!\n\n";
    echo "Versiyon:\n";
    echo $output[0] . "\n";
} else {
    echo "❌ FFmpeg BULUNAMADI!\n\n";
    echo "Hata Detayları:\n";
    print_r($output);
    echo "\nLütfen FFMPEG_KURULUM.md dosyasını okuyun.\n";
}
?>
```

Test:
```
http://localhost:8009/OtomatikArama/test_ffmpeg.php
```

---

## 🎯 ÖNEMLİ NOTLAR

### ✅ Doğru Yol Formatları:
```php
'C:/ffmpeg/bin/ffmpeg.exe'         // ✅ DOĞRU
'C:\\ffmpeg\\bin\\ffmpeg.exe'      // ✅ DOĞRU
'C:\ffmpeg\bin\ffmpeg.exe'         // ❌ YANLIŞ (tek \)
```

### 📁 Klasör İzinleri:
- `temp/audio/` klasörü yazılabilir olmalı
- XAMPP çalışıyorken kurulum yapın

### 🔧 Sorun Giderme:

**Hata: "FFmpeg bulunamadı"**
- FFmpeg yolunu kontrol edin
- `ffmpeg.exe` dosyası var mı kontrol edin
- PATH'e eklediyseniz CMD'yi yeniden başlatın

**Hata: "Ses formatı dönüştürülemedi"**
- Dosya bozuk olabilir
- Başka bir ses dosyası deneyin
- Zaten 8kHz WAV dosyası yükleyin (dönüştürme gerektirmez)

---

## 🎬 SONUÇ

FFmpeg kurulumu tamamlandıktan sonra:

1. ✅ MP3 ve WAV dosyalarını yükleyebilirsiniz
2. ✅ Otomatik olarak Asterisk formatına dönüştürülür
3. ✅ SSH ile santrale gönderilir

---

## 📞 DESTEK

Sorun yaşarsanız:

1. `test_ffmpeg.php` çalıştırın
2. Hata mesajlarını not edin
3. `d:\xampp\htdocs\OtomatikArama\temp\audio\` klasöründe dosya oluşuyor mu kontrol edin

---

## 🔗 Faydalı Linkler

- FFmpeg Resmi: https://ffmpeg.org/
- Windows Build: https://www.gyan.dev/ffmpeg/builds/
- Dokümantasyon: https://ffmpeg.org/documentation.html
