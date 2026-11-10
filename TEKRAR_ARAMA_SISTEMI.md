# 🔄 Otomatik Tekrar Arama Sistemi

## 🎯 Sistem Özeti

Ulaşılamayan müşterilere **otomatik tekrar arama** yapan sistem.

### Ana Özellikler:
- ✅ **TÜM kayıtları** arama başlatır (limit yok)
- ✅ **Ulaşılamayana** otomatik tekrar ara
- ✅ **Belirli saat aralığı** ile tekrar dene
- ✅ **Maksimum tekrar sayısı** kontrolü
- ✅ **Otomatik scheduler** sistemi

## 📋 Kurulum Adımları

### 1️⃣ Veritabanı Şemasını Güncelle

```batch
setup_retry_settings.bat
```

**Eklenen Alanlar:**
- `IMPORT_HISTORY.RetryCount` - Maksimum kaç kez tekrar aranacak
- `IMPORT_HISTORY.RetryIntervalHours` - Tekrar aramalar arası bekleme süresi (saat)
- `CALLS.LastCallTime` - Son arama zamanı

### 2️⃣ Windows Task Scheduler'ı Kur

```batch
YÖNETİCİ OLARAK ÇALIŞTIR:
setup_scheduler_task.bat
```

**Görev Ayarları:**
- İsim: `OtomatikAramaTekrar`
- Sıklık: Her 30 dakikada bir
- Komut: `php retry_scheduler.php`

## 🎮 Kullanım

### Arama Yönetimi Sayfası

```
http://localhost:8009/OtomatikArama/arama_yonetimi.php
```

### Tablo Görünümü

```
┌─────────┬───────────────────┬─────────┬──────────────┬──────────────┬──────────┬─────────┐
│  SAAT   │    DOSYA ADI      │  PROJE  │ TEKRAR SAYISI│ ARALIK (SAAT)│ İSTATİST.│  İŞLEM  │
├─────────┼───────────────────┼─────────┼──────────────┼──────────────┼──────────┼─────────┤
│ 27.01   │ test_import.csv   │[10.Kas▼]│     [3]      │     [2]      │ 🟡 100   │▶️ START │
│ 22:52   │ 📊 100 kayıt      │         │              │              │          │ (TÜM)   │
│         │                   │         │              │              │          │100 kayıt│
└─────────┴───────────────────┴─────────┴──────────────┴──────────────┴──────────┴─────────┘
```

### Her Dosya İçin Ayarlar

#### 1. Proje Seçimi
```
Dropdown'dan proje seçin
→ Otomatik kaydedilir
```

#### 2. Tekrar Sayısı
```
Değer Aralığı: 1-10
Örnek: 3

Anlamı: Ulaşılamazsa 3 kez daha aranacak
```

#### 3. Aralık (Saat)
```
Değer Aralığı: 1-24 saat
Örnek: 2

Anlamı: Her tekrar arama arası 2 saat beklenecek
```

#### 4. START Butonu
```
▶️ START (TÜM)
100 kayıt başlatılacak

→ TÜM bekleyen kayıtlar başlatılır
→ Limit YOK
```

## 📊 İşleyiş Senaryosu

### Örnek: 100 Kayıtlık Dosya

```
Ayarlar:
- Tekrar Sayısı: 3
- Aralık: 2 saat

─────────────────────────────────────────────────────

1️⃣ İLK ARAMA (Saat 10:00)
   
   100 kayıt başlatıldı
   ↓
   70 cevap verdi ✅ (Status=3)
   30 cevap vermedi ❌ (Status=1, CallCount=1)

─────────────────────────────────────────────────────

2️⃣ SCHEDULER (30 dakikada bir kontrol ediyor)

   Saat 10:30 → Henüz 2 saat geçmedi, bekle
   Saat 11:00 → Henüz 2 saat geçmedi, bekle
   Saat 11:30 → Henüz 2 saat geçmedi, bekle
   Saat 12:00 → 2 saat geçti! ✅

─────────────────────────────────────────────────────

3️⃣ OTOMATİK 2. ARAMA (Saat 12:00)

   30 kayıt otomatik başlatıldı
   ↓
   20 cevap verdi ✅ (Status=3)
   10 cevap vermedi ❌ (Status=1, CallCount=2)

─────────────────────────────────────────────────────

4️⃣ OTOMATİK 3. ARAMA (Saat 14:00)

   10 kayıt otomatik başlatıldı
   ↓
   5 cevap verdi ✅ (Status=3)
   5 cevap vermedi ❌ (Status=1, CallCount=3)

─────────────────────────────────────────────────────

5️⃣ OTOMATİK 4. ARAMA (Saat 16:00)

   5 kayıt otomatik başlatıldı (SON DENEME)
   ↓
   3 cevap verdi ✅ (Status=3)
   2 cevap vermedi ❌ (Status=1, CallCount=3)

─────────────────────────────────────────────────────

6️⃣ SCHEDULER (Saat 18:00)

   CallCount = 3
   TryCallCount = 3
   CallCount >= TryCallCount → Artık arama YOK ❌

─────────────────────────────────────────────────────

SONUÇ:
✅ 98 başarılı (70+20+5+3)
❌ 2 başarısız (max deneme sayısına ulaşıldı)
```

## 🔧 Scheduler Detayları

### Otomatik Çalışma

```
Windows Task Scheduler:
- Her 30 dakikada bir kontrol eder
- Koşullar:
  1. Status = 1 (Bekliyor)
  2. Aktif = 1
  3. CallCount > 0 (En az 1 kez arandı)
  4. CallCount < TryCallCount (Henüz max'a ulaşmadı)
  5. Locked = 0 (Kilitli değil)
  6. Son aramadan belirlenen saat geçti
```

### Manuel Test

```batch
php d:\xampp\htdocs\OtomatikArama\retry_scheduler.php
```

**Çıktı Örneği:**
```
===========================================
Otomatik Tekrar Arama Scheduler
Çalışma Zamanı: 2026-01-27 12:00:00
===========================================

Tekrar aranacak kayıt sayısı: 30

İşleniyor: ID=123, Tel=5301234567, Deneme=1/3, Son Arama=2.1 saat önce
  ✅ Başarılı - Arama başlatıldı
İşleniyor: ID=124, Tel=5301234568, Deneme=1/3, Son Arama=2.0 saat önce
  ✅ Başarılı - Arama başlatıldı
...

===========================================
Özet:
  ✅ Başarılı: 30
  ❌ Başarısız: 0
  📊 Toplam: 30
===========================================
```

## 🎯 Pratik Örnekler

### Örnek 1: Agresif Arama (Hızlı, Çok Tekrar)

```
Tekrar Sayısı: 5
Aralık: 1 saat

Senaryo:
10:00 → İlk arama
11:00 → 2. arama (otomatik)
12:00 → 3. arama (otomatik)
13:00 → 4. arama (otomatik)
14:00 → 5. arama (otomatik)
15:00 → 6. arama (otomatik) - SON
```

### Örnek 2: Nazik Arama (Yavaş, Az Tekrar)

```
Tekrar Sayısı: 2
Aralık: 6 saat

Senaryo:
10:00 → İlk arama
16:00 → 2. arama (otomatik)
22:00 → 3. arama (otomatik) - SON
```

### Örnek 3: Standart Arama (Önerilen)

```
Tekrar Sayısı: 3
Aralık: 2 saat

Senaryo:
10:00 → İlk arama
12:00 → 2. arama (otomatik)
14:00 → 3. arama (otomatik)
16:00 → 4. arama (otomatik) - SON
```

## 📈 İstatistik Takibi

### CALLS Tablosu Alanları

```sql
-- Tekrar arama ile ilgili alanlar
CallCount        -- Kaç kez arandı (her aramada +1)
TryCallCount     -- Max kaç kez aranacak
LastCallTime     -- Son arama zamanı
Status           -- 1=Bekliyor, 2=Aranıyor, 3=Tamamlandı, 4=Başarısız
```

### Sorgu Örnekleri

#### 1. Tekrar Aranacaklar
```sql
SELECT * FROM ANABILIM.CALLS
WHERE Status = 1
AND CallCount > 0
AND CallCount < TryCallCount;
```

#### 2. Max Denemeye Ulaşanlar
```sql
SELECT * FROM ANABILIM.CALLS
WHERE CallCount >= TryCallCount
AND Status = 1;
```

#### 3. Şu An Tekrar Aranması Gerekenler
```sql
SELECT c.*, ih.RetryIntervalHours
FROM ANABILIM.CALLS c
JOIN ANABILIM.IMPORT_HISTORY ih ON c.ImportId = ih.id
WHERE c.Status = 1
AND c.CallCount > 0
AND c.CallCount < c.TryCallCount
AND TIMESTAMPDIFF(HOUR, c.LastCallTime, NOW()) >= ih.RetryIntervalHours;
```

## 🔍 Sorun Giderme

### Scheduler Çalışıyor mu?

```batch
# Görev listesini kontrol et
schtasks /Query /TN "OtomatikAramaTekrar"

# Hemen çalıştır (test)
schtasks /Run /TN "OtomatikAramaTekrar"

# Son çalışma zamanını gör
taskschd.msc → OtomatikAramaTekrar'a sağ tık → Özellikler → Geçmiş
```

### Manuel Test

```batch
# Scheduler'ı manuel çalıştır
php d:\xampp\htdocs\OtomatikArama\retry_scheduler.php

# Çıktıyı kontrol et:
# - Tekrar aranacak kayıt sayısı: X
# - Başarılı: Y
# - Başarısız: Z
```

### Tekrar Arama Olmuyor?

**Kontrol Listesi:**
1. ✅ Scheduler kurulu mu? (`taskschd.msc`)
2. ✅ Görev çalışıyor mu? (Son çalışma zamanı)
3. ✅ Belirlenen süre geçti mi? (LastCallTime + RetryIntervalHours <= NOW)
4. ✅ Max tekrar sayısına ulaşıldı mı? (CallCount < TryCallCount)
5. ✅ Kayıt kilitli mi? (Locked = 0)

### Log Kontrolü

```batch
# Scheduler logları
# Windows Event Viewer → Task Scheduler → History

# Asterisk logları
plink.exe -ssh root@192.168.181.16 -P 45555 -pw "KKAstera*2o21@.!" "tail -f /var/log/asterisk/full"
```

## 💡 İpuçları

### ✅ İyi Pratikler

1. **Test Et**: İlk kullanımda 5-10 kayıtlık küçük dosya ile test edin
2. **Makul Aralık**: 2 saat aralık standart, çok sık aramayın
3. **Az Tekrar**: 3 tekrar yeterli, çok fazla tekrar rahatsız edici
4. **Gece Araması**: Tekrar aramaları gün içine yayın
5. **İstatistik İzle**: Başarı oranlarını takip edin

### ❌ Kaçınılması Gerekenler

1. ❌ 30 dakikadan kısa aralık (çok sık)
2. ❌ 10'dan fazla tekrar (çok fazla)
3. ❌ Gece 22:00-08:00 arası tekrar arama
4. ❌ TryCallCount'u düşük tutma (3-5 arası ideal)
5. ❌ Scheduler'ı kapatmayı unutma (test sonrası)

## 📊 Performans

### Scheduler Kapasitesi

```
Her çalışmada: 100 kayıt işler (LIMIT 100)
Her 30 dakika: 1 çalışma
Saatte: 2 çalışma = 200 kayıt
Günde: 48 çalışma = 4,800 kayıt
```

**Not:** Limit artırılabilir (retry_scheduler.php → LIMIT 100)

## 🚀 Özet

### Kurulum
```batch
1. setup_retry_settings.bat        → Veritabanı güncelle
2. setup_scheduler_task.bat        → Windows görev ekle
   (Yönetici olarak çalıştır!)
```

### Kullanım
```
1. http://localhost:8009/OtomatikArama/arama_yonetimi.php
2. Her dosya için:
   - Proje seç
   - Tekrar sayısı: 3
   - Aralık: 2 saat
   - START (TÜM)
3. Scheduler otomatik çalışır
```

### Takip
```
- Sayfa: Canlı istatistikler
- Scheduler: Manuel test (php retry_scheduler.php)
- Görev: taskschd.msc
```

---

**Başarılar! 🎉**
