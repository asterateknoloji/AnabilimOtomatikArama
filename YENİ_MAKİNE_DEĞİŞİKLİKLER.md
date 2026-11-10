# 🔄 YENİ MAKİNEYE TAŞIMA - DEĞİŞİKLİKLER

## 📌 ÖZET

**ESKİ MAKİNE:** 213.14.87.158 (Web + DB + Asterisk)  
**YENİ MAKİNE:** 192.168.150.11 (Sadece Web)  
**UZAK SUNUCU:** 213.14.87.158 (DB + Asterisk kalıyor)

---

## ✅ YAPILMASI GEREKENLER

### 1️⃣ YENİ MAKİNEDE KURULUM

#### A) XAMPP Kurulumu
- [ ] XAMPP indir ve kur (`D:\xampp\`)
- [ ] Apache başlat (port 8009)
- [ ] PHP CLI test et: `d:\xampp\php\php.exe -v`

#### B) Dosyaları Kopyala
```
ESKİ: D:\xampp\htdocs\WindowsOtomatikAramason97\
YENİ: D:\xampp\htdocs\WindowsOtomatikAramason97\
```

- [ ] Tüm dosyaları kopyala
- [ ] `.env` dosyası dahil tüm dosyalar taşınsın

#### C) Klasör İzinleri
```powershell
cd D:\xampp\htdocs\WindowsOtomatikAramason97
icacls logs /grant Users:(OI)(CI)F
icacls tmp /grant Users:(OI)(CI)F
icacls audio /grant Users:(OI)(CI)F
icacls uploads /grant Users:(OI)(CI)F
```

---

### 2️⃣ .ENV DOSYASI - DEĞİŞİKLİK GEREKMİYOR! ✅

`.env` dosyası **AYNEN KALACAK** çünkü MySQL ve Asterisk uzak sunucuda:

```ini
# VERİTABANI (Uzak sunucu - DEĞİŞMEZ)
DB_HOST=213.14.87.158          ✅ KALACAK
DB_NAME=ANABILIMNEW            ✅ KALACAK
DB_USER=astera                 ✅ KALACAK
DB_PASS=astera2012             ✅ KALACAK

# ASTERISK SSH (Uzak sunucu - DEĞİŞMEZ)
ASTERISK_SSH_HOST=213.14.87.158   ✅ KALACAK
ASTERISK_SSH_PORT=45555            ✅ KALACAK
ASTERISK_SSH_USER=root             ✅ KALACAK
ASTERISK_SSH_PASS=KKAstera*2o21@.! ✅ KALACAK

# TRUNK ve CALLER ID (DEĞİŞMEZ)
DEFAULT_TRUNK=Telsam           ✅ KALACAK
CALLER_ID=02164150000          ✅ KALACAK
```

**Yani .env'de HİÇBİR değişiklik yapmayın!**

---

### 3️⃣ BAĞLANTI TESTLERİ

```powershell
cd D:\xampp\htdocs\WindowsOtomatikAramason97

# Tüm bağlantıları test et
d:\xampp\php\php.exe test_connections.php

# MySQL test (213.14.87.158)
d:\xampp\php\php.exe test_db.php

# SSH test (213.14.87.158:45555)
d:\xampp\php\php.exe test_ssh.php

# TTS test
d:\xampp\php\php.exe test_tts.php
```

**Beklenen:** Tüm testler ✅ olmalı!

---

### 4️⃣ TASK SCHEDULER KURULUMU

**PowerShell'i YÖNETİCİ olarak aç:**

```powershell
cd D:\xampp\htdocs\WindowsOtomatikAramason97
.\setup_all_tasks.ps1
```

**Kurulacak görevler:**
1. `OtomatikAramaUnlock` - Her 1 dakika (cleanup_stuck_calls.php)
2. `OtomatikAramaTekrar` - Her 5 dakika (retry_scheduler.php)
3. `OtomatikAramaZamanlanmis` - Her 2 dakika (cron/scheduled_calls.php)

**Kontrol:**
```powershell
Get-ScheduledTask -TaskName "OtomatikArama*" | Format-Table TaskName, State, NextRunTime
```

---

### 5️⃣ FIREWALL AYARLARI

```powershell
# Apache için port 8009 aç
New-NetFirewallRule -DisplayName "XAMPP Apache 8009" -Direction Inbound -LocalPort 8009 -Protocol TCP -Action Allow
```

---

### 6️⃣ İLK GİRİŞ

**URL:** http://192.168.150.11:8009/WindowsOtomatikAramason97/login.php

**Giriş:**
- Kullanıcı: `superadmin`
- Şifre: `Astera2021`

---

## ❌ DEĞİŞMEYEN AYARLAR

Aşağıdakiler **uzak sunucuda** kaldığı için değişmez:

- ✅ MySQL veritabanı (213.14.87.158:3306)
- ✅ Asterisk PBX (213.14.87.158:45555)
- ✅ AGI scriptleri (uzak sunucuda)
- ✅ .env içindeki tüm ayarlar
- ✅ Trunk (Telsam)
- ✅ CallerID (02164150000)

---

## 🔍 SORUN GİDERME

### ❌ "MySQL bağlanamıyor"

**Test:**
```powershell
Test-NetConnection -ComputerName 213.14.87.158 -Port 3306
```

**Çözüm:** Uzak sunucuda MySQL'in 3306 portu açık olmalı ve `astera@%` kullanıcısına izin verilmeli.

```sql
-- Uzak sunucuda çalıştır:
GRANT ALL ON ANABILIMNEW.* TO 'astera'@'%' IDENTIFIED BY 'astera2012';
FLUSH PRIVILEGES;
```

---

### ❌ "SSH bağlanamıyor"

**Test:**
```powershell
Test-NetConnection -ComputerName 213.14.87.158 -Port 45555
```

**Çözüm:** SSH portu açık olmalı.

---

### ❌ "Web arayüzü açılmıyor"

**Kontrol:**
1. Apache çalışıyor mu?
   ```powershell
   Get-Process -Name httpd -ErrorAction SilentlyContinue
   ```

2. Port 8009 açık mı?
   ```powershell
   netstat -an | findstr "8009"
   ```

3. httpd.conf'da port 8009 mu?
   ```
   D:\xampp\apache\conf\httpd.conf
   Listen 8009
   ```

---

## ✅ KURULUM TAMAMLANDI MI?

- [ ] XAMPP kurulu ve çalışıyor
- [ ] Dosyalar taşındı
- [ ] test_connections.php başarılı
- [ ] Task Scheduler görevleri kuruldu ve aktif
- [ ] Web arayüzü açılıyor (http://192.168.150.11:8009/...)
- [ ] Giriş yapılabiliyor (superadmin)
- [ ] Test araması başarılı

**HEPSİ TAMAM İSE: SİSTEM HAZIR!** 🎉

---

## 📞 ÖNEMLİ NOTLAR

1. **Yeni makinede sadece web arayüzü çalışıyor**
   - Database uzak sunucuda (213.14.87.158)
   - Asterisk uzak sunucuda (213.14.87.158)

2. **Task Scheduler görevleri her zaman aktif olmalı**
   - Otomatik unlock
   - Otomatik retry
   - Zamanlanmış aramalar

3. **Logları düzenli kontrol edin**
   ```
   D:\xampp\htdocs\WindowsOtomatikAramason97\logs\
   ```

4. **Yedekleme yapmayı unutmayın**
   - Database: uzak sunucuda
   - Excel dosyaları: `uploads/` klasörü
   - Loglar: `logs/` klasörü
