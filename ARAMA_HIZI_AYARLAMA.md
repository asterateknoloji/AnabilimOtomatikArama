# 🚀 Dakikada Arama Sayısı Ayarlama Rehberi

## 📊 Arama Hızını Kontrol Eden 2 Parametre

### 1️⃣ **Hız (MaxConcurrentCalls)** - Aynı Anda Kaç Arama
- **Nerede:** `arama_yonetimi.php` → ⚡ Hız kolonu
- **Anlam:** Aynı anda maksimum kaç arama aktif olabilir
- **Örnek:** Hız=10 → Aynı anda 10 kişi aranır

### 2️⃣ **Cron Job Sıklığı** - Ne Kadar Sık Kontrol Edilir
- **Nerede:** Windows Task Scheduler
- **Anlam:** Sistem kaç dakikada bir bekleyen aramaları kontrol eder
- **Örnek:** Her 1 dakika → Dakikada bir kontrol

---

## 🧮 Dakikada Arama Hesaplama

### Formül:
```
Dakikada Arama = (Hız × 60 saniye) ÷ Ortalama Arama Süresi
```

### Örnekler:

| Hız | Arama Süresi | Dakikada Arama |
|-----|--------------|----------------|
| 10  | 30 saniye    | ~20 arama/dk   |
| 20  | 30 saniye    | ~40 arama/dk   |
| 50  | 30 saniye    | ~100 arama/dk  |
| 10  | 60 saniye    | ~10 arama/dk   |

---

## ⚙️ Kurulum Dosyaları

### 📁 Hazır Kurulum Scripti

| Dosya | Açıklama | Ne Zaman Kullan |
|-------|----------|-----------------|
| `setup_cron.bat` | **Her 1 dakika** kontrol | Maksimum hız istiyorsanız |
| `setup_cron_5min.bat` | **Her 5 dakika** kontrol | Stabil çalışma için |
| `remove_cron.bat` | Cron job'u kaldır | Sadece manuel kontrol istiyorsanız |
| `check_cron_status.bat` | Durum kontrolü | Cron job çalışıyor mu? |

---

## 🚀 Kurulum Adımları

### Adım 1: Hız Ayarını Belirle
1. `http://localhost:8009/AnabilimOtomatikArama/arama_yonetimi.php` sayfasına git
2. Import'unuz için **⚡ Hız** değerini ayarlayın (örn: 10, 20, 50)
3. **💾 Kaydet** butonuna tıklayın

### Adım 2: Cron Job Kur (Opsiyonel ama Önerilen)
1. **Sağ tık** → `setup_cron.bat` (veya `setup_cron_5min.bat`)
2. **"Yönetici olarak çalıştır"** seçeneğini tıklayın
3. Başarılı mesajını bekleyin

### Adım 3: Kontrol Et
```bash
check_cron_status.bat
```

---

## 📊 Farklı Senaryolar

### 🐌 **Yavaş Arama (Günlük 1000-2000 arama)**
```
✅ Hız: 5
✅ Cron: Her 5 dakika
✅ Sonuç: ~10 arama/dakika = ~600 arama/saat
```

### ⚡ **Orta Hız (Günlük 5000-10000 arama)**
```
✅ Hız: 20
✅ Cron: Her 1 dakika
✅ Sonuç: ~40 arama/dakika = ~2400 arama/saat
```

### 🚀 **Maksimum Hız (Günlük 20000+ arama)**
```
✅ Hız: 50-100
✅ Cron: Her 1 dakika
✅ Sonuç: ~100-200 arama/dakika = ~6000-12000 arama/saat
```

---

## ❓ Sık Sorulan Sorular

### **S: Cron job kurmadan da çalışır mı?**
C: ✅ Evet! "Aktif" butonu ile manuel başlatırsınız. Ama cron job daha stabil.

### **S: Hız 100 yaptım ama yavaş arıyor?**
C: Santral kapasitesi yetersiz olabilir. Trunk ayarlarını kontrol edin.

### **S: Cron job'u nasıl durdururum?**
C: `remove_cron.bat` dosyasını çalıştırın.

### **S: Log dosyaları nerede?**
C: `logs/autocall_YYYY-MM-DD.log` dosyasında

---

## 🔍 Test ve Kontrol

### Test 1: Hız Kontrolü
```sql
-- Kaç arama şu anda aktif?
SELECT ImportId, COUNT(*) as AktifArama
FROM CALLS 
WHERE Locked = 1 
GROUP BY ImportId;
```

### Test 2: Dakikalık Arama Sayısı
```sql
-- Son 5 dakikada kaç arama başladı?
SELECT COUNT(*) as AramaSayisi
FROM CALLS 
WHERE LastCallTime >= DATE_SUB(NOW(), INTERVAL 5 MINUTE);
```

### Test 3: Cron Job Çalışıyor mu?
```bash
check_cron_status.bat
```

---

## 📞 Sorun Giderme

| Sorun | Çözüm |
|-------|-------|
| Çok yavaş arıyor | Hız değerini artırın (20, 50, 100) |
| Hız limiti aşılıyor | `AutoCallManager.php` düzeltildi, güncel mi kontrol edin |
| Pasif yapınca durmuyor | `toggle_active.php` güncel mi kontrol edin |
| Cron job çalışmıyor | `setup_cron.bat`'i Yönetici olarak çalıştırın |
| Log dosyası yok | Görev henüz çalışmamış, bekleyin |

---

## 📝 Önemli Notlar

⚠️ **Santral Kapasitesi:** Çok yüksek hız ayarı santralinizi yavaşlatabilir
⚠️ **Telefon Şirketi:** Dakikada çok fazla arama operatör tarafından engellenebilir
⚠️ **Test:** Küçük bir grup ile test edip sonra artırın

---

## 🎯 Önerilen Ayarlar

### Başlangıç İçin:
```
Hız: 10
Cron: Her 5 dakika
Beklenen: ~20 arama/dakika
```

### Deneyimliyseniz:
```
Hız: 20-50
Cron: Her 1 dakika
Beklenen: ~40-100 arama/dakika
```

---

## 📚 Ek Kaynaklar

- `arama_yonetimi.php` → Import yönetimi ve Hız ayarı
- `toggle_active.php` → Aktif/Pasif kontrol
- `cron/process_calls.php` → Otomatik arama işleyici
- `logs/` → Tüm işlem logları

---

**Son Güncelleme:** 2026-07-07
**Versiyon:** 2.0
