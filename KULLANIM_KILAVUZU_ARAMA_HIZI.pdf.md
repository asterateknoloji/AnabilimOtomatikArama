# 📘 OTOMATIK ARAMA SİSTEMİ
## Arama Hızı ve Dakika Başı Arama Ayarlama Kılavuzu

---

**Versiyon:** 2.0  
**Tarih:** 7 Temmuz 2026  
**Hazırlayan:** Sistem Yöneticisi  
**Geçerlilik:** AnabilimOtomatikArama Sistemi

---

## 📑 İÇİNDEKİLER

1. [Giriş](#1-giriş)
2. [Sistem Hakkında](#2-sistem-hakkında)
3. [Arama Hızı Nedir?](#3-arama-hızı-nedir)
4. [Parametreler ve Ayarlar](#4-parametreler-ve-ayarlar)
5. [Kurulum Adımları](#5-kurulum-adımları)
6. [Kullanım Senaryoları](#6-kullanım-senaryoları)
7. [İzleme ve Kontrol](#7-izleme-ve-kontrol)
8. [Sorun Giderme](#8-sorun-giderme)
9. [Sık Sorulan Sorular](#9-sık-sorulan-sorular)
10. [Teknik Referans](#10-teknik-referans)

---

## 1. GİRİŞ

### 1.1 Kılavuzun Amacı

Bu kılavuz, Otomatik Arama Sisteminde **dakikada kaç arama yapılacağını** ve **arama hızını** nasıl ayarlayacağınızı adım adım anlatır.

### 1.2 Hedef Kitle

- Sistem Yöneticileri
- Çağrı Merkezi Operatörleri
- Teknik Destek Personeli
- Son Kullanıcılar

### 1.3 Ön Gereksinimler

✅ Windows 10/11 işletim sistemi  
✅ XAMPP kurulu ve çalışıyor  
✅ AnabilimOtomatikArama sistemi aktif  
✅ Yönetici (Administrator) yetkisi  
✅ Temel veritabanı bilgisi (opsiyonel)

---

## 2. SİSTEM HAKKINDA

### 2.1 Sistem Mimarisi

```
┌─────────────────────────────────────────────────────────┐
│                   KULLANICI ARAYÜZÜ                     │
│              (arama_yonetimi.php)                       │
└────────────────────┬────────────────────────────────────┘
                     │
         ┌───────────┴───────────┐
         │                       │
┌────────▼────────┐    ┌────────▼─────────┐
│  AKTIF/PASİF    │    │   CRON JOB       │
│  (Manuel)       │    │   (Otomatik)     │
│ toggle_active   │    │ process_calls    │
└────────┬────────┘    └────────┬─────────┘
         │                      │
         └──────────┬───────────┘
                    │
         ┌──────────▼───────────┐
         │  AUTOCALL MANAGER    │
         │  (Arama Motoru)      │
         └──────────┬───────────┘
                    │
         ┌──────────▼───────────┐
         │   ASTERISK PBX       │
         │   (Santral)          │
         └──────────────────────┘
```

### 2.2 Arama Akışı

```
1. Excel Dosyası Yükleme
   ↓
2. Aktif Yapma (Manuel/Otomatik)
   ↓
3. Hız Kontrolü (MaxConcurrentCalls)
   ↓
4. Arama Başlatma
   ↓
5. Santrale Gönderme
   ↓
6. Sonuç Kaydı
```

---

## 3. ARAMA HIZI NEDİR?

### 3.1 Tanım

**Arama Hızı**, sistemin **aynı anda kaç kişiyi arayabileceğini** belirler.

### 3.2 Örneklerle Açıklama

#### Örnek 1: Hız = 10

```
┌──────────────────────────────────────┐
│  ARAMA HAVUZU (1000 kişi)            │
│                                      │
│  Aynı anda 10 kişi aranıyor:        │
│  ┌────┐ ┌────┐ ┌────┐ ┌────┐       │
│  │ 1  │ │ 2  │ │ 3  │ │... │       │
│  └────┘ └────┘ └────┘ └────┘       │
│     ↓      ↓      ↓      ↓          │
│  [Santral - 10 Hat Meşgul]          │
└──────────────────────────────────────┘

Bir arama bitince → Yeni bir kişi aranır
```

#### Örnek 2: Hız = 50

```
┌──────────────────────────────────────┐
│  ARAMA HAVUZU (1000 kişi)            │
│                                      │
│  Aynı anda 50 kişi aranıyor:        │
│  ┌────┐ ┌────┐ ... ┌────┐          │
│  │ 1  │ │ 2  │     │ 50 │          │
│  └────┘ └────┘     └────┘          │
│     ↓      ↓          ↓             │
│  [Santral - 50 Hat Meşgul]          │
└──────────────────────────────────────┘

Daha hızlı ama santral kapasitesi gerekli!
```

### 3.3 Dakika Başı Arama Hesaplama

#### Formül:

```
Dakikada Arama Sayısı = (Hız × 60 saniye) ÷ Ortalama Arama Süresi
```

#### Hesaplama Tablosu:

| Hız | Arama Süresi | Hesaplama | Dakikada Arama |
|-----|--------------|-----------|----------------|
| 10  | 30 sn | (10 × 60) ÷ 30 | **20 arama/dk** |
| 20  | 30 sn | (20 × 60) ÷ 30 | **40 arama/dk** |
| 50  | 30 sn | (50 × 60) ÷ 30 | **100 arama/dk** |
| 10  | 60 sn | (10 × 60) ÷ 60 | **10 arama/dk** |
| 100 | 20 sn | (100 × 60) ÷ 20 | **300 arama/dk** |

#### Saatlik ve Günlük Projeksiyonlar:

| Dakikada | Saatte | Günde (8 saat) | Günde (24 saat) |
|----------|--------|----------------|-----------------|
| 10 | 600 | 4,800 | 14,400 |
| 20 | 1,200 | 9,600 | 28,800 |
| 40 | 2,400 | 19,200 | 57,600 |
| 100 | 6,000 | 48,000 | 144,000 |

---

## 4. PARAMETRELER VE AYARLAR

### 4.1 Hız (MaxConcurrentCalls)

**Konum:** `arama_yonetimi.php` → ⚡ Hız kolonu

#### Görünüm:

```
┌──────────────────────────────────────────────┐
│ Import Dosyası: musteri_listesi.xlsx         │
├──────────────────────────────────────────────┤
│ 🔄 Tekrar: [3]                               │
│ ⏱️ Aralık: [5] dakika                        │
│ ⚡ Hız:    [10] 💾 Kaydet                     │
└──────────────────────────────────────────────┘
```

#### Önerilen Değerler:

| İhtiyaç | Hız Değeri | Açıklama |
|---------|------------|----------|
| **Düşük** | 5-10 | Küçük kampanyalar, test |
| **Orta** | 20-30 | Normal operasyon |
| **Yüksek** | 50-70 | Büyük kampanyalar |
| **Maksimum** | 100+ | Santral kapasitesi yeterliyse |

⚠️ **UYARI:** Çok yüksek değerler santralinizi yavaşlatabilir!

---

### 4.2 Cron Job (Otomatik Kontrol)

**Konum:** Windows Task Scheduler

#### Ne İşe Yarar?

Cron job, **periyodik olarak** bekleyen aramaları kontrol eder ve başlatır.

#### İki Seçenek:

| Seçenek | Açıklama | Ne Zaman |
|---------|----------|----------|
| **Cron Job YOK** | Sadece "Aktif" butonu ile | Küçük işlemler |
| **Cron Job VAR** | Otomatik kontrol her X dakika | Büyük işlemler |

#### Cron Sıklığı Seçenekleri:

```
┌─────────────┬─────────────┬──────────────────────┐
│ Sıklık      │ Avantaj     │ Dezavantaj           │
├─────────────┼─────────────┼──────────────────────┤
│ Her 1 dk    │ Maksimum hız│ Sistem yükü fazla    │
│ Her 5 dk    │ Dengeli     │ Biraz yavaş          │
│ Her 10 dk   │ Hafif yük   │ Yavaş başlatma       │
└─────────────┴─────────────┴──────────────────────┘
```

---

### 4.3 Tekrar Sayısı (RetryCount)

**Konum:** `arama_yonetimi.php` → 🔄 Tekrar kolonu

**Açıklama:** Cevapsız kalan bir numara kaç kez tekrar aranır?

**Örnek:**
```
Tekrar: 3
Aralık: 5 dakika

İlk arama   → Cevapsız
    ↓ (5 dk sonra)
2. arama    → Cevapsız
    ↓ (5 dk sonra)
3. arama    → Cevapsız
    ↓
Vazgeç (Status = 2)
```

---

### 4.4 Aralık (RetryIntervalMinutes)

**Konum:** `arama_yonetimi.php` → ⏱️ Aralık kolonu

**Açıklama:** Tekrar aramalar arasında kaç dakika beklenir?

**Örnekler:**

| Aralık | Senaryo |
|--------|---------|
| 5 dk | Acil aramalar |
| 15 dk | Normal operasyon |
| 60 dk | Önemsiz aramalar |

---

## 5. KURULUM ADIMLARI

### 5.1 Hız Ayarı (Temel Kurulum)

#### Adım 1: Arama Yönetimi Sayfasını Açın

```
🌐 http://localhost:8009/AnabilimOtomatikArama/arama_yonetimi.php
```

#### Adım 2: Import Dosyanızı Bulun

```
┌────────────────────────────────────────────────┐
│ 📋 ARAMA YÖNETİMİ                              │
├────────────────────────────────────────────────┤
│                                                │
│ 📄 musteri_listesi.xlsx                        │
│    Kayıt: 1000 | Bekleyen: 850 | Aranan: 150  │
│                                                │
│    🔄 Tekrar: [3]                              │
│    ⏱️ Aralık: [5]                              │
│    ⚡ Hız:    [10]  💾 Kaydet ← BURAYA TIKLA   │
│                                                │
└────────────────────────────────────────────────┘
```

#### Adım 3: Hız Değerini Girin

```
1. ⚡ Hız kutusuna istediğiniz değeri yazın (örn: 20)
2. 💾 Kaydet butonuna tıklayın
3. Başarılı mesajı göreceksiniz
```

#### Adım 4: Aktif Yapın

```
┌────────────────────────────────────────────────┐
│ 🔘 Aktif: [Pasif] ← BURAYA TIKLA              │
└────────────────────────────────────────────────┘

Tıkladıktan sonra:
✅ Kayıtlar aktif olacak
✅ Aramalar otomatik başlayacak
```

---

### 5.2 Cron Job Kurulumu (Gelişmiş)

#### Gereksinimler:

✅ Yönetici (Administrator) yetkisi  
✅ `setup_cron.bat` dosyası mevcut

#### Adım 1: Dosyayı Bulun

```
📁 d:\xampp\htdocs\AnabilimOtomatikArama\
   ├── setup_cron.bat          ← Her 1 dakika
   ├── setup_cron_5min.bat     ← Her 5 dakika
   └── remove_cron.bat         ← Kaldır
```

#### Adım 2: Yönetici Olarak Çalıştırın

```
1. Dosyaya SAĞ TIKLA
2. "Yönetici olarak çalıştır" seçin
3. UAC izni verin
```

#### Adım 3: Kurulum Ekranı

```
============================================
Otomatik Arama Cron Job Kurulumu
============================================

Yeni görev oluşturuluyor...
Sıklık: Her 1 dakikada bir
Dosya: d:\xampp\htdocs\...\process_calls.php

[BAŞARILI] Görev başarıyla kuruldu!
============================================

Görev Adı: AutoCallProcessor
Sıklık: Her 1 dakikada bir
Durum: Aktif

Herhangi bir tuşa basın...
```

#### Adım 4: Kontrol Edin

```
check_cron_status.bat dosyasını çalıştırın

VEYA

Windows Görev Zamanlayıcısını açın:
1. Win + R
2. taskschd.msc
3. "AutoCallProcessor" görevi görünmeli
```

---

### 5.3 Kurulum Doğrulama

#### Test 1: Veritabanı Kontrolü

```sql
-- Şu anda kaç arama aktif?
SELECT COUNT(*) as AktifArama 
FROM CALLS 
WHERE Locked = 1;

-- Sonuç, Hız değerini geçmemeli!
-- Örn: Hız=10 ise, max 10 görmeli
```

#### Test 2: Log Kontrolü

```
📁 logs\autocall_2026-07-07.log

Son satırlar:
[2026-07-07 08:55:00] === Otomatik Arama İşleyici Başlatıldı ===
[2026-07-07 08:55:01] Toplam kayıt: 1000, Bekleyen: 850
[2026-07-07 08:55:02] 10 arama başlatıldı
[2026-07-07 08:55:02] İşlem tamamlandı
```

#### Test 3: Gerçek Zamanlı İzleme

```sql
-- Her 10 saniyede bir çalıştırın
SELECT 
    ImportId,
    COUNT(*) as ToplamKayit,
    SUM(CASE WHEN Locked = 1 THEN 1 ELSE 0 END) as AktifArama,
    SUM(CASE WHEN Status = 2 THEN 1 ELSE 0 END) as Tamamlanan
FROM CALLS 
GROUP BY ImportId;
```

---

## 6. KULLANIM SENARYOLARI

### Senaryo 1: Küçük Liste (100-500 Kişi)

#### İhtiyaç:
- Günde 500 arama
- Acil değil
- Test amaçlı

#### Önerilen Ayarlar:

```
⚡ Hız:     5
🔄 Tekrar:  3
⏱️ Aralık:  5 dakika
🔧 Cron:    YOK (Manuel Aktif)
```

#### Beklenen Sonuç:

```
Dakikada:  ~10 arama
Saatte:    ~600 arama
Süre:      ~1 saat içinde biter
```

---

### Senaryo 2: Orta Liste (1000-5000 Kişi)

#### İhtiyaç:
- Günde 5000 arama
- Normal çalışma saatleri
- İş günü içinde bitmeli

#### Önerilen Ayarlar:

```
⚡ Hız:     20
🔄 Tekrar:  3
⏱️ Aralık:  10 dakika
🔧 Cron:    Her 5 dakika
```

#### Beklenen Sonuç:

```
Dakikada:  ~40 arama
Saatte:    ~2400 arama
Süre:      ~2-3 saat içinde biter
```

---

### Senaryo 3: Büyük Liste (10000+ Kişi)

#### İhtiyaç:
- Günde 20000+ arama
- 7/24 çalışma
- Maksimum hız

#### Önerilen Ayarlar:

```
⚡ Hız:     50-100
🔄 Tekrar:  5
⏱️ Aralık:  15 dakika
🔧 Cron:    Her 1 dakika
```

#### Beklenen Sonuç:

```
Dakikada:  ~100-200 arama
Saatte:    ~6000-12000 arama
Süre:      ~2-3 saat içinde ilk tur biter
```

⚠️ **DİKKAT:** Santral kapasitesi kontrol edilmeli!

---

### Senaryo 4: Acil Arama (VIP Liste)

#### İhtiyaç:
- Hemen aranmalı
- Maksimum tekrar
- Kısa aralık

#### Önerilen Ayarlar:

```
⚡ Hız:     30
🔄 Tekrar:  5
⏱️ Aralık:  2 dakika
🔧 Cron:    Her 1 dakika
```

#### Beklenen Sonuç:

```
Dakikada:  ~60 arama
İlk Tur:   ~5 dakikada biter
Toplam:    10 dakikada 5 tekrar tamamlanır
```

---

## 7. İZLEME VE KONTROL

### 7.1 Anlık Durum İzleme

#### Web Arayüzü

```
🌐 http://localhost:8009/AnabilimOtomatikArama/arama_yonetimi.php

┌────────────────────────────────────────────────┐
│ 📊 İSTATİSTİKLER                               │
├────────────────────────────────────────────────┤
│ ✅ Başarılı:   150                             │
│ ⏸️ Bekleyen:   850                             │
│ 📞 Aranıyor:   10 ← Bu sayı Hız'dan fazla      │
│                     olmamalı!                  │
│ ❌ Başarısız:  0                               │
└────────────────────────────────────────────────┘
```

---

### 7.2 SQL Sorguları

#### Sorgu 1: Genel Durum

```sql
SELECT 
    ImportId,
    COUNT(*) as Toplam,
    SUM(CASE WHEN Aktif = 1 THEN 1 ELSE 0 END) as Aktif,
    SUM(CASE WHEN Locked = 1 THEN 1 ELSE 0 END) as Aranıyor,
    SUM(CASE WHEN Status = 2 THEN 1 ELSE 0 END) as Tamamlanan
FROM CALLS 
WHERE ImportId = 123  -- Import ID'nizi yazın
GROUP BY ImportId;
```

#### Sorgu 2: Son 5 Dakikada Başlatılan Aramalar

```sql
SELECT COUNT(*) as AramaSayisi
FROM CALLS 
WHERE LastCallTime >= DATE_SUB(NOW(), INTERVAL 5 MINUTE);

-- Örnek Sonuç: 50 arama
-- Dakikada: 50 ÷ 5 = 10 arama/dk
```

#### Sorgu 3: Hız Limiti Kontrolü

```sql
-- Import'un ayarlı hızı
SELECT ih.MaxConcurrentCalls as HizLimiti,
       (SELECT COUNT(*) FROM CALLS WHERE ImportId = ih.id AND Locked = 1) as AktifArama
FROM IMPORT_HISTORY ih
WHERE ih.id = 123;

-- AktifArama, HizLimiti'ni geçmemeli!
```

#### Sorgu 4: Performans Analizi

```sql
SELECT 
    DATE_FORMAT(LastCallTime, '%Y-%m-%d %H:%i:00') as Dakika,
    COUNT(*) as AramaSayisi
FROM CALLS 
WHERE LastCallTime >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
GROUP BY Dakika
ORDER BY Dakika DESC;

-- Dakikalık arama sayısını gösterir
```

---

### 7.3 Log Dosyası İzleme

#### Lokasyon:

```
📁 d:\xampp\htdocs\AnabilimOtomatikArama\logs\
   ├── autocall_2026-07-07.log
   ├── php_errors.log
   └── activity_log.log
```

#### Güncel Log'u İzleme (PowerShell):

```powershell
Get-Content "logs\autocall_*.log" -Tail 20 -Wait
```

#### Önemli Log Mesajları:

```
✅ İyi Mesajlar:
[08:55:02] 10 arama başlatıldı
[08:55:03] CDR sync: 5 kayıt güncellendi
[08:55:03] İşlem tamamlandı

⚠️ Dikkat Edilmesi Gerekenler:
[08:55:02] ⚠️ Limit dolu! (10 >= 10)
[08:55:03] ⚠️ Başlatılabilecek arama bulunamadı

❌ Hata Mesajları:
[08:55:02] ❌ SSH bağlantı hatası
[08:55:03] HATA: Call start error for ID 123
```

---

### 7.4 Santral İzleme (Asterisk CLI)

#### Bağlantı:

```bash
ssh root@192.168.181.49
asterisk -rvvv
```

#### Komutlar:

```
# Aktif çağrıları göster
core show channels

# Belirli bir context'teki çağrılar
core show channels concise | grep auto-calls

# Call file durumu
ls -l /var/spool/asterisk/outgoing/

# Gerçek zamanlı log
tail -f /var/log/asterisk/full
```

---

## 8. SORUN GİDERME

### 8.1 Aramalar Çok Yavaş

#### Belirti:
```
⚠️ Dakikada 5 arama yapıyor ama 20 olmalı
```

#### Olası Sebepler ve Çözümler:

| Sebep | Kontrol | Çözüm |
|-------|---------|-------|
| **Hız düşük** | Web arayüzü | Hız değerini artırın |
| **Cron yok** | Task Scheduler | `setup_cron.bat` çalıştırın |
| **Kayıtlar pasif** | SQL: `Aktif = 0` | Aktif butonu ile aktif yapın |
| **CallTime gelecek** | SQL: `CallTime > NOW()` | CallTime'ı düzeltin |

#### Detaylı Kontrol:

```sql
-- Neden yavaş?
SELECT 
    COUNT(*) as ToplamKayit,
    SUM(CASE WHEN Aktif = 0 THEN 1 ELSE 0 END) as Pasif,
    SUM(CASE WHEN Aktif = 1 AND Status = 1 AND Locked = 0 AND CallTime <= NOW() THEN 1 ELSE 0 END) as AranabilirKayit,
    SUM(CASE WHEN Locked = 1 THEN 1 ELSE 0 END) as AktifArama
FROM CALLS 
WHERE ImportId = 123;

-- AranabilirKayit > 0 olmalı
-- AktifArama = Hız değeri olmalı
```

---

### 8.2 Hız Limiti Aşılıyor

#### Belirti:
```
❌ Hız = 10 ama 50 arama aynı anda yapılıyor
```

#### Çözüm:

✅ **Bu sorun düzeltildi!** (AutoCallManager.php güncellemesi)

Kontrol edin:

```sql
-- Gerçekten aşılıyor mu?
SELECT ImportId, COUNT(*) as AktifArama
FROM CALLS 
WHERE Locked = 1 
GROUP BY ImportId;
```

Eğer hala aşılıyorsa:

```bash
# Dosya güncel mi kontrol edin
ls -l includes/AutoCallManager.php

# Son güncelleme: 2026-07-07 olmalı
```

---

### 8.3 Pasif Yapınca Durmuyor

#### Belirti:
```
⚠️ Pasif yaptım ama aramalar devam ediyor
```

#### Çözüm:

✅ **Bu sorun düzeltildi!** (toggle_active.php güncellemesi)

Manuel durdurma:

```sql
-- Tüm aktif aramaları durdur
UPDATE CALLS 
SET Locked = 0, 
    Status = 2, 
    SipCode = 487,
    EndCallTime = NOW()
WHERE ImportId = 123 
AND Locked = 1;
```

Call file'ları temizle (SSH):

```bash
ssh root@192.168.181.49
rm -f /var/spool/asterisk/outgoing/call_*.call
```

---

### 8.4 Cron Job Çalışmıyor

#### Belirti:
```
⚠️ Kurdum ama log dosyası güncellenmiyor
```

#### Kontrol:

```cmd
# Görev var mı?
schtasks /query /tn "AutoCallProcessor"

# Son çalışma zamanı?
schtasks /query /tn "AutoCallProcessor" /fo LIST /v | findstr "Son Çalışma"
```

#### Çözüm:

1. **Görevi yeniden kur:**
   ```cmd
   remove_cron.bat
   setup_cron.bat
   ```

2. **Manuel test et:**
   ```cmd
   C:\xampp\php\php.exe -f "d:\xampp\htdocs\AnabilimOtomatikArama\cron\process_calls.php"
   ```

3. **Log kontrolü:**
   ```cmd
   type logs\autocall_2026-07-07.log
   ```

---

### 8.5 Santral Hatası

#### Belirti:
```
❌ SSH bağlantı hatası
❌ Call file gönderilemedi
```

#### Kontrol:

```bash
# SSH bağlantısı test et
ssh -p 22 root@192.168.181.49

# Asterisk çalışıyor mu?
systemctl status asterisk

# Spool klasörü yazılabilir mi?
ls -ld /var/spool/asterisk/outgoing/
# Sonuç: drwxrwxrwx olmalı
```

#### Çözüm:

```bash
# İzinleri düzelt
chmod 777 /var/spool/asterisk/outgoing/
chown asterisk:asterisk /var/spool/asterisk/outgoing/

# Asterisk restart
systemctl restart asterisk
```

---

### 8.6 Log Dosyası Çok Büyük

#### Belirti:
```
⚠️ autocall_2026-07-07.log dosyası 500 MB!
```

#### Çözüm:

Eski logları temizle:

```cmd
# 7 günden eski logları sil
forfiles /p "logs" /m autocall_*.log /d -7 /c "cmd /c del @path"

# Manuel temizlik
del logs\autocall_2026-06-*.log
```

Otomatik temizlik script'i ekle:

```batch
@echo off
REM Eski logları temizle (7 günden eski)
forfiles /p "logs" /m *.log /d -7 /c "cmd /c del @path" >nul 2>&1
```

Bunu `setup_cron.bat`'e ekleyin.

---

## 9. SIK SORULAN SORULAR

### S1: Dakikada maksimum kaç arama yapabilirim?

**C:** Teorik limit yoktur, ama:
- Santral kapasitesi (kaç hat var?)
- Telefon şirketi limiti (dakikada 100+ arama spam olarak algılanabilir)
- Sunucu performansı

**Gerçekçi:** 50-200 arama/dakika

---

### S2: Cron job kurmadan da çalışır mı?

**C:** ✅ Evet! İki mod var:

| Mod | Açıklama |
|-----|----------|
| **Cron YOK** | Sadece "Aktif" butonu ile başlatırsınız |
| **Cron VAR** | Otomatik kontrol ve başlatma |

Cron job **önerilir** ama zorunlu değil.

---

### S3: Hız 100 yaptım ama çok yavaş, neden?

**C:** Olası sebepler:
- ❌ Santral kapasitesi yetersiz (kaç hat aktif?)
- ❌ Trunk ayarı yanlış
- ❌ CallTime gelecek tarihli
- ❌ Kayıtlar pasif

**Kontrol:**
```sql
SELECT COUNT(*) FROM CALLS WHERE Aktif = 1 AND Status = 1 AND Locked = 0 AND CallTime <= NOW();
-- Bu sayı > 0 olmalı
```

---

### S4: Sistemde kaç import aynı anda aktif olabilir?

**C:** ✅ Sınırsız!

Her import'un kendi Hız değeri var. Örnek:

| Import | Hız | Aktif Arama |
|--------|-----|-------------|
| Liste 1 | 10 | 10 |
| Liste 2 | 20 | 20 |
| Liste 3 | 5 | 5 |
| **TOPLAM** | - | **35** |

Santral **toplam** kapasitesini aşmayın!

---

### S5: Gece aramaları durdurmak istiyorum

**C:** Çalışma saatleri sistemi var!

**Konum:** `ZAMANLAR` tablosu

```sql
-- Pazartesi 09:00-18:00 arası çalışsın
INSERT INTO ZAMANLAR (Gun, BaslamaSaati, BitisSaati) 
VALUES (1, '09:00:00', '18:00:00');

-- Gun: 1=Pazartesi, 7=Pazar
```

Sistem otomatik kontrol eder.

---

### S6: Bir numarayı manuel olarak tekrar aramak

**C:** SQL ile yapabilirsiniz:

```sql
-- Kaydı sıfırla
UPDATE CALLS 
SET Status = 1,          -- Bekleyen
    CallCount = 0,       -- Aranma sayısı sıfırla
    CallTime = NOW(),    -- Hemen ara
    Locked = 0,          -- Kilitli değil
    Aktif = 1            -- Aktif
WHERE PhoneNumber = '05551234567';
```

---

### S7: Excel'de tekrar eden numaralar var, sorun olur mu?

**C:** ❌ Olmaz! Sistem otomatik **duplicate kontrolü** yapar.

```
Excel:
0555 123 45 67  ← Ekler
0555 123 45 67  ← Atlar (duplicate)
0555 999 99 99  ← Ekler
```

Log'da göreceksiniz:
```
[08:55:02] 1000 kayıt import edildi
[08:55:02] 50 tekrar eden numara atlandı
```

---

### S8: Sistem ani kapanırsa aramalar durur mu?

**C:** ⚠️ **Kısmen**

- ✅ Veritabanında durum kayıtlı
- ✅ Asterisk'e gönderilen aramalar devam eder
- ❌ Yeni aramalar başlamaz (cron durdu)

**Sistem açılınca:**
```
1. Cron job otomatik devreye girer
2. Locked=1 olan kayıtlar 30 dk sonra unlock olur
3. Aramalar kaldığı yerden devam eder
```

---

### S9: Santral hatası verirse ne olur?

**C:** Sistem **otomatik retry** yapar:

```
1. Arama başlatma denemesi
2. Hata (SSH bağlanamadı)
3. Log'a yaz
4. Kaydı unlock et
5. Sonraki cron'da tekrar dene
```

Log:
```
[08:55:02] ❌ Call start error for ID 123: SSH connection failed
[08:56:02] ✅ 10 arama başlatıldı (tekrar denedi)
```

---

### S10: Gerçek zamanlı rapor alabilirim miyim?

**C:** ✅ Evet! SQL sorguları ile:

```sql
-- Gerçek zamanlı durum
SELECT 
    COUNT(*) as Toplam,
    SUM(CASE WHEN Status = 2 AND SipCode = 200 THEN 1 ELSE 0 END) as Basarili,
    SUM(CASE WHEN Locked = 1 THEN 1 ELSE 0 END) as Aranıyor,
    SUM(CASE WHEN Status = 1 THEN 1 ELSE 0 END) as Bekleyen,
    ROUND(SUM(CASE WHEN Status = 2 AND SipCode = 200 THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) as BasariYuzdesi
FROM CALLS 
WHERE ImportId = 123;
```

Bunu **her 10 saniyede otomatik** çalıştırabilirsiniz.

---

## 10. TEKNİK REFERANS

### 10.1 Veritabanı Tabloları

#### CALLS Tablosu

```sql
CREATE TABLE CALLS (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ImportId INT,                   -- Hangi import'a ait
    PhoneNumber VARCHAR(20),        -- Telefon numarası
    Adi VARCHAR(100),               -- Ad Soyad
    Status INT,                     -- 1=Bekleyen, 2=Aranan
    CallCount INT DEFAULT 0,        -- Kaç kez arandı
    TryCallCount INT DEFAULT 3,     -- Maksimum deneme
    Aktif TINYINT DEFAULT 0,        -- 0=Pasif, 1=Aktif
    Locked TINYINT DEFAULT 0,       -- 0=Boş, 1=Aranıyor
    CallTime DATETIME,              -- Ne zaman aranacak
    LastCallTime DATETIME,          -- Son arama zamanı
    EndCallTime DATETIME,           -- Arama bitiş zamanı
    SipCode INT,                    -- Sonuç kodu (200=Başarılı)
    ProjectId INT,                  -- Hangi projeye ait
    TemplateId INT,                 -- Hangi şablon
    -- ...diğer kolonlar
);
```

#### IMPORT_HISTORY Tablosu

```sql
CREATE TABLE IMPORT_HISTORY (
    id INT AUTO_INCREMENT PRIMARY KEY,
    OriginalFileName VARCHAR(255),   -- Dosya adı
    FileName VARCHAR(255),           -- Sunucudaki yol
    RecordCount INT,                 -- Kayıt sayısı
    RetryCount INT DEFAULT 3,        -- Tekrar sayısı
    RetryIntervalMinutes INT DEFAULT 5, -- Aralık (dakika)
    MaxConcurrentCalls INT DEFAULT 10,  -- Hız limiti
    ProjectId INT,                   -- Proje ID
    TemplateId INT,                  -- Şablon ID
    UploadDate DATETIME,             -- Yüklenme tarihi
    -- ...diğer kolonlar
);
```

---

### 10.2 Önemli Dosyalar

| Dosya | Açıklama |
|-------|----------|
| `arama_yonetimi.php` | Ana yönetim sayfası |
| `toggle_active.php` | Aktif/Pasif işlemleri |
| `cron/process_calls.php` | Otomatik arama işleyici |
| `includes/AutoCallManager.php` | Arama motoru |
| `includes/AsteriskCallFile.php` | Santral entegrasyonu |
| `config.php` | Sistem ayarları |

---

### 10.3 Konfigürasyon (config.php)

```php
<?php
// Veritabanı
define('DB_HOST', '192.168.181.49');
define('DB_NAME', 'AsteraWeb');
define('DB_USER', 'astera');
define('DB_PASS', 'astera2012');

// Asterisk
define('ASTERISK_SSH_HOST', '192.168.181.49');
define('ASTERISK_SSH_PORT', 22);
define('ASTERISK_SSH_USER', 'root');
define('ASTERISK_SSH_PASS', 'password');
define('ASTERISK_SPOOL_PATH', '/var/spool/asterisk/outgoing/');

// Sistem
define('CALLER_ID', '02129999999');
define('DEFAULT_TRUNK', 'SIP/provider');
define('MAX_CONCURRENT_CALLS', 100); // Global limit
?>
```

---

### 10.4 API Endpoint'leri

#### toggle_active.php (Aktif/Pasif)

**Request:**
```javascript
POST toggle_active.php
{
    "import_id": 123,
    "active": 1,              // 0=Pasif, 1=Aktif
    "retry_count": 3,
    "retry_interval": 5,
    "max_concurrent": 10,
    "project_id": 5,
    "template_id": 2
}
```

**Response:**
```json
{
    "success": true,
    "message": "✅ 1000 kayıt AKTİF yapıldı!\n\n⚡ 10 arama OTOMATIK BAŞLATILDI!",
    "affected_rows": 1000,
    "started_calls": 10,
    "stopped_calls": 0
}
```

---

### 10.5 Durum Kodları (SipCode)

| Kod | Açıklama |
|-----|----------|
| 200 | Başarılı (cevap verildi) |
| 404 | Cevapsız |
| 486 | Meşgul |
| 487 | İptal edildi |
| 503 | Ulaşılamadı |

---

### 10.6 Log Seviyeleri

```php
// php_errors.log
error_log("🚀 startAllImportCalls çağrıldı");    // Info
error_log("⚠️ Limit dolu!");                      // Warning
error_log("❌ SSH bağlantı hatası");              // Error

// autocall_YYYY-MM-DD.log
writeLog("=== İşleyici Başlatıldı ===");          // Info
writeLog("10 arama başlatıldı");                  // Success
writeLog("HATA: " . $e->getMessage());            // Error
```

---

## 📞 DESTEK VE İLETİŞİM

### Teknik Destek

**E-posta:** support@anabilim.com  
**Telefon:** 0212 999 99 99  
**Çalışma Saatleri:** Pzt-Cum 09:00-18:00

### Acil Durum

**7/24 Hattı:** 0555 999 99 99  
**Sadece:** Sistem tamamen durdu, kritik hata

### Dokümantasyon

**GitHub:** https://github.com/anabilim/otomatik-arama  
**Wiki:** https://wiki.anabilim.com/arama-sistemi

---

## 📜 VERSİYON GEÇMİŞİ

| Versiyon | Tarih | Değişiklikler |
|----------|-------|---------------|
| 2.0 | 2026-07-07 | ✅ MaxConcurrentCalls kontrolü eklendi<br>✅ Pasif durdurma mekanizması<br>✅ Cron job kurulum scriptleri |
| 1.5 | 2026-06-15 | ✅ Import bazlı hız kontrolü<br>✅ CallTime düzeltmeleri |
| 1.0 | 2026-05-01 | İlk versiyon |

---

## 📝 LİSANS VE YASAL UYARI

⚠️ **UYARI:** Bu sistem otomatik arama yapar. Kullanırken:

- ✅ Kişisel Verileri Koruma Kanunu'na uyun
- ✅ Ticari İletişim İzin Yönetim Sistemi (TİCİS) kontrol edin
- ✅ İzinsiz kişileri aramayın
- ✅ Çalışma saatleri dışında aramayın (22:00-08:00)

**Sorumluluk:** Kullanıcı sistemin hukuka uygun kullanımından sorumludur.

---

## ✅ KONTROL LİSTESİ

Kurulum tamamlandı mı?

- [ ] XAMPP kurulu ve çalışıyor
- [ ] Veritabanı bağlantısı başarılı
- [ ] Asterisk SSH bağlantısı test edildi
- [ ] Hız değeri ayarlandı
- [ ] Cron job kuruldu (opsiyonel)
- [ ] Test araması yapıldı
- [ ] Log dosyaları kontrol edildi
- [ ] Bu kılavuz okundu ve anlaşıldı

---

**SON GÜNCELLEME:** 7 Temmuz 2026  
**DOKÜMAN KODU:** AOS-KK-2026-001  
**HAZIRLIYAN:** Sistem Yöneticisi

---

**📘 KULLANIM KILAVUZU SONU**
