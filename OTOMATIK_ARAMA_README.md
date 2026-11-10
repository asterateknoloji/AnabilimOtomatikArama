# 🤖 Otomatik Arama Sistemi

Excel dosyasından müşterileri içe aktararak otomatik borç bildirimi yapan sistem.

## 📋 Özellikler

- ✅ Excel (.xls, .xlsx) dosyalarından toplu müşteri içe aktarma
- ✅ Windows TTS (SAPI) ile Türkçe ses sentezi
- ✅ Asterisk PBX entegrasyonu
- ✅ DTMF tuş algılama (1: Tekrar dinle, 2: Operatöre aktar)
- ✅ Arama durumu takibi (Beklemede, Aranıyor, Tamamlandı)
- ✅ Otomatik tekrar arama (maksimum 3 deneme)
- ✅ Web tabanlı yönetim paneli

## 🚀 Kurulum

### 1. Bağımlılıkları Yükle

```bash
cd d:\xampp\htdocs\AsteraWeb
composer install
```

Composer yoksa:
```bash
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php -r "unlink('composer-setup.php');"
php composer.phar install
```

### 2. Veritabanı

Tablo zaten mevcut: `ANABILIM.CALLS`

### 3. FFmpeg Kurulumu (Ses Dönüşümü İçin)

Windows için:
1. https://www.gyan.dev/ffmpeg/builds/ adresinden indirin
2. `C:\ffmpeg` klasörüne çıkartın
3. Path'e ekleyin: `C:\ffmpeg\bin`

Test:
```bash
ffmpeg -version
```

### 4. Asterisk Ayarları

#### 4.1 Dialplan Ekle

`C:\Program Files\Asterisk\etc\asterisk\extensions.conf` dosyasına:

```ini
#include extensions_autocall.conf
```

`extensions_autocall.conf` dosyasını Asterisk dizinine kopyalayın:
```bash
copy d:\xampp\htdocs\AsteraWeb\asterisk\extensions_autocall.conf "C:\Program Files\Asterisk\etc\asterisk\"
```

#### 4.2 AGI Scriptlerini Ayarla

AGI scriptlerini Asterisk'in erişebileceği bir yere koyun veya FastAGI kullanın.

**FastAGI Sunucusu Çalıştırma:**

```bash
cd d:\xampp\htdocs\AsteraWeb\asterisk\agi
php -S localhost:4573
```

Veya AGI scriptlerini Asterisk AGI dizinine kopyalayın:
```bash
copy d:\xampp\htdocs\AsteraWeb\asterisk\agi\*.php "C:\Program Files\Asterisk\var\lib\asterisk\agi-bin\"
```

Script izinlerini ayarlayın (Linux için):
```bash
chmod +x /var/lib/asterisk/agi-bin/*.php
```

#### 4.3 Ses Dosyaları Dizini

Asterisk ses dosyaları dizinini oluşturun:
```bash
mkdir "C:\Program Files\Asterisk\var\lib\asterisk\sounds\custom\calls"
```

`AsteriskCallFile.php` içindeki yolu güncelleyin:
```php
$this->asteriskSpoolPath = 'C:\Program Files\Asterisk\var\spool\asterisk\outgoing\\';
```

#### 4.4 Asterisk'i Yeniden Yükle

```bash
asterisk -rx "dialplan reload"
```

### 5. Windows Türkçe TTS Kurulumu

Windows 10/11'de Türkçe TTS sesi yükleme:

1. **Ayarlar** > **Saat ve Dil** > **Konuşma**
2. **Ses Ekle** > **Türkçe (Türkiye)**
3. Indirin ve aktifleştirin

Test için PowerShell'de:
```powershell
Add-Type -AssemblyName System.speech
$synthesizer = New-Object System.Speech.Synthesis.SpeechSynthesizer
$synthesizer.GetInstalledVoices()
$synthesizer.SelectVoice("Microsoft Tolga Desktop")
$synthesizer.Speak("Merhaba, test mesajı")
```

### 6. Zamanlanmış Görev Ayarla

#### Windows Task Scheduler

1. **Görev Zamanlayıcısı**'nı açın
2. **Temel Görev Oluştur**
3. İsim: "Otomatik Arama İşleyici"
4. Tetikleyici: **Yineleme** - Her 5 dakikada
5. Eylem: **Program Başlat**
   - Program: `C:\xampp\php\php.exe`
   - Argümanlar: `-f "d:\xampp\htdocs\AsteraWeb\cron\process_calls.php"`
6. Kaydet

#### Linux Cron

```bash
crontab -e
```

Ekle:
```
*/5 * * * * /usr/bin/php /var/www/html/AsteraWeb/cron/process_calls.php >> /var/log/autocall.log 2>&1
```

## 📊 Kullanım

### 1. Excel Dosyası Hazırlama

Excel formatı (Sütun başlıkları isteğe bağlı):

| Telefon      | AdSoyad       | Tutar    | Metin1      | Metin2        | Metin3 |
|--------------|---------------|----------|-------------|---------------|--------|
| 05551234567  | Ahmet Yılmaz  | 1500.50  | Vade tarihi | 15 Ocak 2026  |        |
| 05559876543  | Ayşe Demir    | 2300.00  |             |               |        |
| 02129876543  | Mehmet Kaya   | 850.75   |             |               |        |

**Örnek Excel Şablonu:** `excel_sablon.xlsx` (otomatikarama.php sayfasından indirilebilir)

### 2. Web Paneli

Tarayıcıda açın:
```
http://localhost:8009/AsteraWeb/otomatikarama.php
```

### 3. Arama Akışı

1. **Excel Yükle** → Kayıtlar veritabanına eklenir (Durum: Beklemede)
2. **Aramaları Başlat** → TTS sesleri oluşturulur, call file'lar Asterisk'e gönderilir
3. **Asterisk Arar** → Müşteri cevaplarsa dialplan çalışır
4. **Ses Oynatılır** → "Merhaba {Ad}, bugünkü borcunuz {Tutar} TL'dir..."
5. **Tuş Beklenir**:
   - **1**: Mesajı tekrar dinle
   - **2**: Operatör kuyruğuna aktar
   - **Timeout**: 30 saniye sonra kapat
6. **Durum Güncellenir** → Veritabanında arama bilgileri kaydedilir

### 4. Durum Kodları

| Durum | Açıklama |
|-------|----------|
| 1     | Beklemede |
| 2     | Aranıyor |
| 3     | Tamamlandı |
| 4     | Başarısız |

## 🎯 Arama Senaryosu Özelleştirme

`includes/WindowsTTS.php` dosyasındaki `generateCallScript()` metodunu düzenleyin:

```php
private function generateCallScript($adSoyad, $tutar, $metin1, $metin2, $metin3)
{
    $script = "Merhaba";
    
    if (!empty($adSoyad)) {
        $script .= " " . $adSoyad;
    }
    
    $script .= ". ";
    
    // Kendi mesajınızı buraya ekleyin
    $script .= "Bugünkü borcunuz " . number_format($tutar, 2) . " Türk Lirasıdır. ";
    
    if (!empty($metin1)) {
        $script .= $metin1 . " ";
    }
    
    $script .= "Tekrar dinlemek için 1, operatöre bağlanmak için 2 tuşuna basınız.";
    
    return $script;
}
```

## 🔧 Yapılandırma

### Arayan Numara Ayarla

`includes/AsteriskCallFile.php`:

```php
$this->callerID = '02129999999'; // Şirket numaranız
```

### Deneme Sayısını Ayarla

Excel yüklerken varsayılan 3 deneme. Değiştirmek için:

`includes/AutoCallManager.php` → `importFromExcel()`:

```php
$tryCallCount = 5; // 5 denemeye çıkar
```

### Operatör Kuyruğunu Ayarla

`asterisk/extensions_autocall.conf`:

```ini
same => n,Queue(TAHSILAT,t,,,300)  ; Kuyruk adını değiştirin
```

### Ses Ayarları

`includes/WindowsTTS.php`:

```php
$synthesizer.Rate = 0          # Konuşma hızı (-10 ile 10 arası)
$synthesizer.Volume = 100      # Ses seviyesi (0-100)
```

## 📁 Dosya Yapısı

```
AsteraWeb/
├── otomatikarama.php              # Ana yönetim paneli
├── includes/
│   ├── AutoCallManager.php        # Arama yönetim sınıfı
│   ├── WindowsTTS.php             # Windows TTS sınıfı
│   └── AsteriskCallFile.php       # Call file oluşturucu
├── asterisk/
│   ├── extensions_autocall.conf   # Dialplan
│   ├── agi/
│   │   ├── update_call_status.php # Durum güncelleme AGI
│   │   ├── update_call_dtmf.php   # DTMF güncelleme AGI
│   │   └── update_call_end.php    # Arama bitişi AGI
│   ├── audio/
│   │   └── calls/                 # TTS ses dosyaları
│   └── callfiles/                 # Call file geçici klasörü
├── cron/
│   └── process_calls.php          # Zamanlanmış görev
├── logs/
│   └── autocall_*.log             # Log dosyaları
└── temp/                          # Geçici dosyalar
```

## 🐛 Sorun Giderme

### 1. TTS Ses Oluşmuyor

```powershell
# PowerShell'de test edin:
Add-Type -AssemblyName System.speech
$synth = New-Object System.Speech.Synthesis.SpeechSynthesizer
$synth.GetInstalledVoices() | Select -ExpandProperty VoiceInfo
```

Türkçe ses yoksa Windows ayarlarından indirin.

### 2. Call File Oluşmuyor

- Asterisk spool dizinini kontrol edin
- Yazma izinlerini kontrol edin
- `AsteriskCallFile.php` içinde yolu güncelleyin

### 3. AGI Çalışmıyor

Log kontrol:
```bash
tail -f /var/log/asterisk/full
```

PHP'nin çalıştırılabilir olduğundan emin olun:
```bash
chmod +x /var/lib/asterisk/agi-bin/*.php
```

### 4. Aramalar Başlamıyor

Cron/Task Scheduler loglarını kontrol edin:
```
d:\xampp\htdocs\AsteraWeb\logs\autocall_*.log
```

Manuel test:
```bash
php d:\xampp\htdocs\AsteraWeb\cron\process_calls.php
```

## 📞 Operatör Kuyruğu Gereksinimleri

Asterisk'te `TAHSILAT` adında bir kuyruk oluşturun:

`/etc/asterisk/queues.conf`:

```ini
[TAHSILAT]
strategy = ringall
timeout = 15
retry = 5
maxlen = 0
announce-frequency = 30
announce-holdtime = yes
member => SIP/101
member => SIP/102
```

Reload:
```bash
asterisk -rx "queue reload"
```

## 🔐 Güvenlik

1. Web paneline auth_check.php ile giriş kontrolü eklenmiş
2. Excel yüklerken SQL injection koruması var
3. AGI scriptlerinde parametre validasyonu yapılıyor
4. Call file izinlerini doğru ayarlayın (sadece Asterisk yazabilmeli)

## 📈 Performans

- **Eşzamanlı Arama**: 50 arama aynı anda başlatılır
- **TTS Hızı**: Ortalama 2-3 saniye/ses
- **Arama Süresi**: ~30-60 saniye/arama
- **Saatlik Kapasite**: ~100-200 arama (sistem ayarlarına göre)

## 📝 Lisans

Bu proje Astera için geliştirilmiştir.

## 🆘 Destek

Sorularınız için: [destek@astera.com.tr](mailto:destek@astera.com.tr)
