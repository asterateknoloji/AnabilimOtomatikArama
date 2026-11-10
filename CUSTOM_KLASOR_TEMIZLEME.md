# 🧹 ASTERISK CUSTOM KLASÖR OTOMATİK TEMİZLEME SİSTEMİ

## 📋 GENEL BAKIŞ

Bu sistem, Asterisk sunucusundaki `/var/lib/asterisk/sounds/custom/` klasöründe biriken `call_*` ile başlayan dosyaları otomatik olarak temizler.

---

## 🎯 AMAÇ

**Sorun:**
- Asterisk her arama için `call_XXXXXXXXXX` formatında dosyalar oluşturur
- Bu dosyalar zamanla birikir ve disk alanı kaplar
- Manuel temizleme zaman alıcı ve unutulabilir

**Çözüm:**
- Her gece saat **00:00**'da otomatik temizleme
- **24 saatten eski** dosyalar silinir
- Detaylı log kaydı
- Otomatik disk alanı geri kazanımı

---

## 📦 DOSYALAR

```
asterisk/
└── cleanup_custom_files.sh       → Temizleme bash scripti

setup_custom_cleanup_cron.php     → Kurulum scripti
test_custom_cleanup.php           → Test ve kontrol arayüzü
CUSTOM_KLASOR_TEMIZLEME.md        → Bu dokümantasyon
```

---

## 🚀 KURULUM

### Adım 1: Kurulum Scriptini Çalıştır

Tarayıcınızda açın:
```
http://192.168.181.139:8009/OtomatikArama/setup_custom_cleanup_cron.php
```

**Script şunları yapar:**
1. ✅ `cleanup_custom_files.sh` scriptini Asterisk sunucusuna yükler
2. ✅ Gerekli izinleri ayarlar (`chmod +x`)
3. ✅ Cron job ekler (her gece 00:00)
4. ✅ Log dosyasını oluşturur
5. ✅ Log rotation ayarlarını yapar
6. ✅ Test çalıştırması yapar

### Adım 2: Kurulumu Doğrula

```
http://192.168.181.139:8009/OtomatikArama/test_custom_cleanup.php
```

**Kontrol edilecekler:**
- ✅ Script dosyası mevcut mu?
- ✅ Cron job eklenmiş mi?
- ✅ Custom klasörde kaç dosya var?
- ✅ Kaç dosya silinecek?
- ✅ Log dosyası çalışıyor mu?

---

## ⚙️ NASIL ÇALIŞIR?

### Otomatik Çalışma

**Zaman:** Her gece saat 00:00
**Hedef:** `/var/lib/asterisk/sounds/custom/call_*`
**Kriter:** 24 saatten eski dosyalar
**Log:** `/var/log/asterisk/cleanup_custom.log`

### Cron Job

```bash
# Asterisk custom klasör temizleme (her gece saat 00:00)
0 0 * * * /usr/local/bin/cleanup_custom_files.sh >> /var/log/asterisk/cleanup_custom.log 2>&1
```

**Açıklama:**
- `0 0 * * *` → Her gün saat 00:00
- Script çalışır → Eski dosyaları bulur
- Dosyaları siler → Log'a kaydeder
- Özet rapor → İstatistikler

---

## 📊 SCRIPT ÇİKTISI

### Örnek Log:

```
═══════════════════════════════════════════════════════════════
  ASTERISK CUSTOM KLASÖR TEMİZLEME
  Tarih: 2026-01-30 00:00:05
═══════════════════════════════════════════════════════════════

📁 Klasör: /var/lib/asterisk/sounds/custom

📊 İŞLEM ÖNCESİ DURUM:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
📦 Toplam call_* dosyası: 1250
🗑️  24 saatten eski dosya: 850
💾 Klasör boyutu: 2.3G

📋 SİLİNECEK DOSYALAR (son 10):
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
-rw-r--r-- 1 asterisk asterisk 145K Jan 28 10:25 call_1706437543.123
-rw-r--r-- 1 asterisk asterisk 152K Jan 28 10:30 call_1706437845.456
...

💾 Silinecek toplam boyut: 1.8G

🗑️  SİLME İŞLEMİ BAŞLIYOR...
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  ✓ Silindi: call_1706437543.123
  ✓ Silindi: call_1706437845.456
  ...

✅ Silme işlemi tamamlandı: 850 dosya silindi

📊 İŞLEM SONRASI DURUM:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
📦 Kalan call_* dosyası: 400
💾 Mevcut klasör boyutu: 512M
🧹 Temizlenen alan: 1.8G

═══════════════════════════════════════════════════════════════
  ✅ TEMİZLEME TAMAMLANDI
  📊 850 dosya silindi (1.8G)
  📁 400 dosya kaldı
═══════════════════════════════════════════════════════════════
```

---

## 🔍 KONTROL VE TEST

### Web Arayüzü ile Test

```
http://192.168.181.139:8009/OtomatikArama/test_custom_cleanup.php
```

**Özellikler:**
- 📊 Klasör istatistikleri
- 🗑️ Silinecek dosya listesi
- 📋 Log görüntüleme
- ▶️ Manuel test çalıştırma
- ⏰ Sonraki çalışma zamanı

### SSH Komutları

#### Script'i Manuel Çalıştır:
```bash
/usr/local/bin/cleanup_custom_files.sh
```

#### Log'ları Görüntüle:
```bash
# Canlı takip
tail -f /var/log/asterisk/cleanup_custom.log

# Son 50 satır
tail -50 /var/log/asterisk/cleanup_custom.log

# Tüm log
cat /var/log/asterisk/cleanup_custom.log
```

#### Klasör Durumunu Kontrol Et:
```bash
# Toplam dosya sayısı
find /var/lib/asterisk/sounds/custom -name 'call_*' | wc -l

# 24 saatten eski dosyalar
find /var/lib/asterisk/sounds/custom -name 'call_*' -mtime +0 -ls

# Klasör boyutu
du -sh /var/lib/asterisk/sounds/custom

# Eski dosyaların toplam boyutu
find /var/lib/asterisk/sounds/custom -name 'call_*' -mtime +0 -exec du -ch {} + | grep total$
```

#### Cron Job'ı Kontrol Et:
```bash
# Cron listesini görüntüle
crontab -l

# Cron job'ın çalışıp çalışmadığını kontrol et
grep CRON /var/log/syslog | grep cleanup_custom
```

---

## 🛠️ YAPILANDI RMALAR

### Temizleme Zamanını Değiştir

Cron job'ı düzenle:
```bash
crontab -e
```

**Örnekler:**
```bash
# Her gece 02:00
0 2 * * * /usr/local/bin/cleanup_custom_files.sh >> /var/log/asterisk/cleanup_custom.log 2>&1

# Her 6 saatte bir
0 */6 * * * /usr/local/bin/cleanup_custom_files.sh >> /var/log/asterisk/cleanup_custom.log 2>&1

# Sadece hafta sonları 00:00
0 0 * * 6,0 /usr/local/bin/cleanup_custom_files.sh >> /var/log/asterisk/cleanup_custom.log 2>&1
```

### Temizleme Kriterini Değiştir

Script'i düzenle:
```bash
nano /usr/local/bin/cleanup_custom_files.sh
```

**Mevcut:**
```bash
find "$CUSTOM_DIR" -name "call_*" -type f -mtime +0 2>/dev/null
# +0 = 24 saatten eski
```

**Alternatifler:**
```bash
# 48 saatten eski (2 gün)
find "$CUSTOM_DIR" -name "call_*" -type f -mtime +1 2>/dev/null

# 7 günden eski
find "$CUSTOM_DIR" -name "call_*" -type f -mtime +6 2>/dev/null

# 30 günden eski
find "$CUSTOM_DIR" -name "call_*" -type f -mtime +29 2>/dev/null

# 12 saatten eski (0.5 gün)
find "$CUSTOM_DIR" -name "call_*" -type f -mmin +720 2>/dev/null
```

---

## 📋 LOG ROTATION

Log dosyası otomatik olarak rotate edilir:

**Config:** `/etc/logrotate.d/asterisk-custom-cleanup`

```
/var/log/asterisk/cleanup_custom.log {
    weekly           → Haftalık rotation
    rotate 12        → 12 hafta saklansın
    compress         → Eski log'lar sıkıştırılsın
    delaycompress    → Son log sıkıştırılmasın
    missingok        → Log yoksa hata verme
    notifempty       → Boşsa rotate etme
    create 0644 root root → Yeni log oluştur
}
```

**Manuel rotation testi:**
```bash
logrotate -f /etc/logrotate.d/asterisk-custom-cleanup
```

---

## 🚨 SORUN GİDERME

### Sorun 1: Script çalışmıyor

**Kontrol:**
```bash
# Script mevcut mu?
ls -lh /usr/local/bin/cleanup_custom_files.sh

# Çalıştırılabilir mi?
file /usr/local/bin/cleanup_custom_files.sh

# Manuel çalıştır
/usr/local/bin/cleanup_custom_files.sh
```

**Çözüm:**
```bash
# İzinleri düzelt
chmod +x /usr/local/bin/cleanup_custom_files.sh

# Script'i yeniden yükle
# setup_custom_cleanup_cron.php'yi tekrar çalıştır
```

### Sorun 2: Cron çalışmıyor

**Kontrol:**
```bash
# Cron job var mı?
crontab -l | grep cleanup_custom

# Cron service çalışıyor mu?
systemctl status cron  # veya
service cron status
```

**Çözüm:**
```bash
# Cron'u yeniden başlat
systemctl restart cron

# Cron log'unu kontrol et
grep CRON /var/log/syslog | tail -20
```

### Sorun 3: İzin hatası (Permission denied)

**Kontrol:**
```bash
# Script sahibi kim?
ls -lh /usr/local/bin/cleanup_custom_files.sh

# Custom klasörü izinleri
ls -ld /var/lib/asterisk/sounds/custom
```

**Çözüm:**
```bash
# Script'i root olarak çalıştır (cron zaten root)
# Veya asterisk kullanıcısına izin ver
chown asterisk:asterisk /usr/local/bin/cleanup_custom_files.sh
```

### Sorun 4: Dosyalar silinmiyor

**Kontrol:**
```bash
# 24 saatten eski dosya var mı?
find /var/lib/asterisk/sounds/custom -name 'call_*' -mtime +0 -ls

# Dosyalar gerçekten call_* formatında mı?
ls -lh /var/lib/asterisk/sounds/custom/ | grep call_
```

**Debug:**
```bash
# Script'i verbose modda çalıştır
bash -x /usr/local/bin/cleanup_custom_files.sh
```

---

## 📊 İSTATİSTİKLER VE RAPORLAR

### Günlük Rapor

Log dosyasından özet çıkar:
```bash
#!/bin/bash
# daily_cleanup_report.sh

LOG_FILE="/var/log/asterisk/cleanup_custom.log"
TODAY=$(date '+%Y-%m-%d')

echo "═══════════════════════════════════════"
echo "  Günlük Temizleme Raporu: $TODAY"
echo "═══════════════════════════════════════"
echo ""

DELETED=$(grep "dosya silindi" "$LOG_FILE" | grep "$TODAY" | tail -1 | grep -oP '\d+' | head -1)
FREED=$(grep "Silinecek toplam boyut:" "$LOG_FILE" | grep "$TODAY" | tail -1 | awk '{print $NF}')

echo "🗑️  Silinen Dosya: ${DELETED:-0}"
echo "🧹 Temizlenen Alan: ${FREED:-0}"
echo ""
```

### Haftalık İstatistik

```bash
#!/bin/bash
# weekly_cleanup_stats.sh

LOG_FILE="/var/log/asterisk/cleanup_custom.log"

echo "═══════════════════════════════════════"
echo "  Haftalık Temizleme İstatistikleri"
echo "═══════════════════════════════════════"
echo ""

TOTAL_DELETED=0
for i in {0..6}; do
    DATE=$(date -d "$i days ago" '+%Y-%m-%d')
    DELETED=$(grep "dosya silindi" "$LOG_FILE" | grep "$DATE" | tail -1 | grep -oP '\d+' | head -1)
    DELETED=${DELETED:-0}
    TOTAL_DELETED=$((TOTAL_DELETED + DELETED))
    echo "$DATE: $DELETED dosya"
done

echo ""
echo "📊 Toplam: $TOTAL_DELETED dosya"
```

---

## ⚠️ ÖNEMLİ NOTLAR

### 1. Backup Stratejisi
```
❌ Silinen dosyalar GERİ GETİRİLEMEZ!
✅ Gerekli dosyaları başka yere yedekleyin
✅ 24 saat süre yeterli mi kontrol edin
```

### 2. Disk Alanı
```
✅ Düzenli temizleme ile disk dolması önlenir
✅ Asterisk performansı artar
✅ Sistem daha stabil çalışır
```

### 3. Log Takibi
```
✅ Log dosyasını düzenli kontrol edin
✅ Hataları gözden kaçırmayın
✅ İstatistikleri takip edin
```

---

## 🎯 EN İYİ PRATİKLER

### 1. İlk Kurulumdan Sonra
- ✅ Test çalıştırması yapın
- ✅ Log dosyasını kontrol edin
- ✅ İlk otomatik çalışmayı bekleyin
- ✅ Ertesi gün sonuçları kontrol edin

### 2. Düzenli Kontrol
- ✅ Haftada 1 kez log'ları inceleyin
- ✅ Disk kullanımını takip edin
- ✅ Silinen dosya sayısını kontrol edin

### 3. Sorun Durumunda
- ✅ Log dosyasını kontrol edin
- ✅ Manuel çalıştırma yapın
- ✅ Test arayüzünü kullanın

---

## 📞 DESTEK

### Test Arayüzü:
```
http://192.168.181.139:8009/OtomatikArama/test_custom_cleanup.php
```

### Kurulum Sayfası:
```
http://192.168.181.139:8009/OtomatikArama/setup_custom_cleanup_cron.php
```

### Manuel Komutlar:
```bash
# Script'i çalıştır
/usr/local/bin/cleanup_custom_files.sh

# Log'ları görüntüle
tail -f /var/log/asterisk/cleanup_custom.log

# Durum kontrolü
find /var/lib/asterisk/sounds/custom -name 'call_*' | wc -l
```

---

## ✅ KURULUM CHECKLIST

- [ ] `setup_custom_cleanup_cron.php` çalıştırıldı
- [ ] Script Asterisk sunucusuna yüklendi
- [ ] Cron job eklendi
- [ ] Log dosyası oluşturuldu
- [ ] Test çalıştırması yapıldı
- [ ] İlk otomatik çalışma bekleniyor
- [ ] Log dosyası kontrol edildi
- [ ] Düzenli kontrol planlandı

---

**Son Güncelleme:** 30 Ocak 2026  
**Versiyon:** 1.0  
**Hazırlayan:** Sistem Yöneticisi
