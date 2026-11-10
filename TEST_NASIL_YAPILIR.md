# SIP UPDATER TEST

## ✅ KURULUM TAMAMLANDI

Santral'da script kuruldu:
- **Konum**: `/var/www/html/asterisk_sip_updater.php`
- **Çalışma**: Her **5 dakikada bir** otomatik
- **Log**: `/var/log/asterisk/sip_updater.log`

## 🧪 NASIL TEST EDERSİNİZ?

### 1. Yeni Bir Arama Başlatın

http://localhost:8009/WindowsOtomatikAramason97/arama_yonetimi.php

- Bir import seçin
- "START (TÜM)" veya "Yeniden Başlat" butonuna tıklayın

### 2. Telefonu Reddedin

Gelen aramayı **red edin** veya **cevap vermeyin**

### 3. Script'i Manuel Çalıştırın (veya 5 dakika bekleyin)

```powershell
d:\xampp\php\php.exe test_sip_updater.php
```

### 4. Sonucu Kontrol Edin

```powershell
d:\xampp\php\php.exe check_system_status.php
```

## 📊 BEKLENTİLER

**Script çalışmadan önce:**
- Locked = 1
- SipCode = NULL
- Durum = NULL

**Script çalıştıktan sonra:**
- Locked = 0
- SipCode = **486** (Busy) veya **480** (NoAnswer) veya **503** (Unreachable)
- Durum = **"Meşgul"** veya **"Cevapsız"** veya **"Ulaşılamıyor"**
- CallTime = +60 dakika (retry)

## 🔄 ÇALIŞMA MANTIĞI

### ÖNCE (Windows Unlock Script):
- Her 1 dakikada çalışır
- 30 saniye timeout
- **SipCode = 503** (varsayılan)
- Durum = "[Unlock: ...]"

### SONRA (Asterisk SIP Updater):
- Her 5 dakikada çalışır
- Call file'ları kontrol eder
- **Gerçek SIP kodunu** bulur
- Database'i günceller

## 📝 LOG KONTROLÜ

Santral'da:
```bash
ssh root@213.14.87.158 -p 45555
tail -f /var/log/asterisk/sip_updater.log
```

## ⚙️ CRONTAB KONTROLÜ

```bash
crontab -l
```

Output:
```
*/5 * * * * /usr/bin/php /var/www/html/asterisk_sip_updater.php >> /var/log/asterisk/sip_updater.log 2>&1
```

## 🎯 ÖZET

Artık iki sistem birlikte çalışıyor:

1. **Windows Unlock Script** (1 dk) → Stuck call'ları hızlıca açar, SipCode=503
2. **Asterisk SIP Updater** (5 dk) → Gerçek SIP kodunu bulur ve günceller

**Sonuç:** Gerçek SIP kodları (486 Busy, 480 NoAnswer) database'e yazılacak!
