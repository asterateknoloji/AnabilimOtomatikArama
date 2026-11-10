# 📚 Otomatik Arama Sistemi - Kullanım Kılavuzu

## 🚀 Hızlı Başlangıç

### 1. Veritabanı Kurulumu
```bash
setup_database.bat
```
Bu script yeni tabloları oluşturacak:
- PROJECTS (Projeler)
- MESSAGE_TEMPLATES (Mesaj Şablonları)
- IMPORT_HISTORY (İçe Aktarma Geçmişi)

### 2. Zamanlanmış Arama Servisi Kurulumu (Opsiyonel)
```bash
setup_scheduler.bat
```
(Yönetici olarak çalıştırın)

Bu servis her dakika çalışıp zamanı gelmiş aramaları otomatik başlatır.

---

## 📁 Proje Yönetimi

### Proje Oluşturma
1. http://localhost:8009/OtomatikArama/projeler.php
2. "Yeni Proje Oluştur" bölümünden:
   - Proje adı girin (Örn: "Ocak 2026 Tahsilat")
   - Açıklama ekleyin
   - Varsayılan mesaj şablonu seçin
3. "Proje Oluştur" tıklayın

### Proje Kullanımı
- Her proje için ayrı arama kayıtları tutulur
- Her projenin kendi istatistikleri vardır
- Projeler arasında geçiş yapabilirsiniz

---

## 📝 Mesaj Şablonu Yönetimi

### Şablon Oluşturma
1. http://localhost:8009/OtomatikArama/sablonlar.php
2. "Yeni Şablon" butonuna tıklayın
3. Şablon adı ve açıklama girin
4. Alanları ekleyin:
   - **Müşteri Adı** (`{Adi}`) - Excel'deki isim
   - **Ana Metin** (`{Metin}`) - Genel bilgi
   - **Tutar** (`{Tutar}`) - Borç tutarı (Türkçe okunur)
   - **Ek Metin 1, 2, 3** - Ek bilgiler
   - **Sabit Metin** - "Merhaba", "Türk Lirasıdır" gibi
5. Alanları istediğiniz sıraya koyun (▲▼ butonları)
6. "Şablonu Kaydet" tıklayın

### Örnek Şablon:
```
Merhaba {Müşteri Adı}. {Ana Metin}. Borcunuz {Tutar} Türk Lirasıdır. 
{Ek Metin 1}. Tekrar dinlemek için 1, operatöre bağlanmak için 2'yi tuşlayınız.
```

---

## 📥 Gelişmiş İçe Aktarma

### Excel Dosyası Hazırlama
Excel dosyanız şu sütunları içermeli:
1. **Telefon** (Örn: 05301234567)
2. **Ad Soyad** (Örn: Ahmet Yılmaz)
3. **Tutar** (Örn: 1250.50)
4. **Metin 1** (Opsiyonel)
5. **Metin 2** (Opsiyonel)
6. **Metin 3** (Opsiyonel)

### Import İşlemi
1. http://localhost:8009/OtomatikArama/import.php
2. Formu doldurun:
   - **Proje Seçiniz:** Hangi projeye ait
   - **Mesaj Şablonu:** Hangi şablon kullanılsın (opsiyonel, proje varsayılanı kullanılır)
   - **Zamanlanmış Tarih/Saat:** İstediğiniz tarih ve saat (opsiyonel, boş bırakırsanız hemen kullanılır)
3. Excel dosyasını seçin
4. "Excel'i İçe Aktar" tıklayın

### Dosya İsimlendirme
Sistem otomatik olarak dosyayı şu formatta kaydeder:
```
2026-01-27_OriginalDosyaAdi.xlsx
```

---

## ⏰ Zamanlanmış Aramalar

### Manuel Zamanlama
1. Import sayfasında "Zamanlanmış Tarih" ve "Saat" alanlarını doldurun
2. Import edin
3. Belirtilen tarih/saatte otomatik başlatılacak

### Otomatik Servis
- `setup_scheduler.bat` ile Task Scheduler kurulumu yapın
- Servis her dakika çalışıp zamanı gelmiş aramaları başlatır
- Manuel müdahale gerekmez

---

## 📊 Raporlama

### Özet Rapor
1. http://localhost:8009/OtomatikArama/raporlar.php
2. Filtreler:
   - Proje seçin
   - Tarih aralığı seçin
3. İstatistikleri görün:
   - Toplam arama
   - Başarılı/Başarısız
   - Tuş basma istatistikleri
   - Ortalama arama süresi

### Detay Rapor
1. "İçe Aktarma Geçmişi" tablosundan "Rapor" butonuna tıklayın
2. Tüm kayıtların detayını görün:
   - Telefon, müşteri, tutar
   - Arama zamanları
   - Hangi tuşlar basıldı
   - Transfer edildi mi
   - Durum

### Excel Export
- Özet rapor sayfasından "Excel İndir" butonuna tıklayın
- Detay rapor sayfasından "Excel İndir" butonuna tıklayın
- Dosya otomatik indirilir

---

## 🔧 Sistem Ayarları

### SSH Bağlantısı (config.php)
```php
define('ASTERISK_SSH_HOST', '192.168.181.16');
define('ASTERISK_SSH_PORT', 45555);
define('ASTERISK_SSH_USER', 'root');
define('ASTERISK_SSH_PASS', 'KKAstera*2o21@.!');
```

### Caller ID (Arayan Numara)
```php
define('CALLER_ID', '02129999999');
```

### Asterisk Yolları
```php
define('ASTERISK_SPOOL_PATH', '/var/spool/asterisk/outgoing/');
define('ASTERISK_SOUNDS_PATH', '/var/lib/asterisk/sounds/custom/');
```

---

## 🎯 Kullanım Senaryoları

### Senaryo 1: Basit Tahsilat Araması
1. "Tahsilat Ocak 2026" projesi oluşturun
2. "Tahsilat Mesajı" şablonu oluşturun
3. Excel'i içe aktarın (proje ve şablon seçin)
4. "Aramaları Başlat" tıklayın

### Senaryo 2: Zamanlanmış Kampanya
1. "Yıl Sonu Kampanyası" projesi oluşturun
2. Excel'i içe aktarın
3. Zamanlanmış tarih: **01.02.2026 09:00**
4. Sistem otomatik başlatacak

### Senaryo 3: Çoklu Proje
1. Her müşteri grubu için ayrı proje oluşturun:
   - "VIP Müşteriler"
   - "Normal Müşteriler"
   - "Eski Borçlar"
2. Her proje için farklı şablon kullanın
3. Ayrı ayrı raporlayın

---

## 📞 Arama Akışı

1. **Arama Başlatma:** Web panelden veya zamanlanmış
2. **TTS Oluşturma:** Windows SAPI ile Türkçe ses
3. **FFmpeg Dönüştürme:** 8kHz, Mono, 16-bit
4. **SSH Gönderimi:** Asterisk sunucusuna
5. **Arama:** Asterisk call file
6. **IVR Menü:** Auto-calls dialplan
7. **DTMF Algılama:** Tuş 1 (tekrar), Tuş 2 (operatör)
8. **Veritabanı Güncelleme:** AGI scriptleri
9. **Raporlama:** Web paneli

---

## ❓ Sık Sorulan Sorular

**S: Aynı anda kaç arama yapılır?**
C: `startPendingCalls()` her seferinde 50 arama başlatır (config.php'den değiştirilebilir).

**S: Başarısız aramalar tekrar dener mi?**
C: Evet, `TryCallCount` kadar deneme yapar (varsayılan 3).

**S: Mesaj şablonunda Türkçe karakter sorunu?**
C: Veritabanı utf8mb4 kullanıyor, sorun olmamalı.

**S: Asterisk dialplan'ı nasıl güncellenir?**
C: `install_asterisk_dialplan.bat` scriptini çalıştırın.

---

## 🆘 Destek

Sorun yaşarsanız:
1. `test_autocall.php` ile sistem testini çalıştırın
2. PHP error log kontrol edin: `D:\xampp\apache\logs\error.log`
3. Asterisk log kontrol edin: SSH ile `/var/log/asterisk/full`
