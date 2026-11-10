# 🚚 YENİ MAKİNEYE TAŞIMA REHBERİ

## 📍 Yeni Makine Bilgileri
- **IP:** 192.168.150.11
- **Dizin:** `D:\xampp\htdocs\WindowsOtomatikAramason97\`
- **URL:** http://192.168.150.11:8009/WindowsOtomatikAramason97/login.php
- **Login:** superadmin / Astera2021

## ✅ KONTROL LİSTESİ

### 1️⃣ XAMPP/PHP Kurulumu
- [ ] XAMPP kurulu mu? (PHP 5.4+)
- [ ] Apache çalışıyor mu?
- [ ] PHP CLI çalışıyor mu? (`php -v`)
- [ ] PHP uzantıları:
  - [ ] `php_pdo_mysql.dll` (MySQL)
  - [ ] `php_openssl.dll` (SSH)
  - [ ] `php_curl.dll` (HTTP)
  - [ ] `php_mbstring.dll` (String)

**Test:**
```powershell
d:\xampp\php\php.exe -v
d:\xampp\php\php.exe -m | Select-String "pdo_mysql"
```

---

### 2️⃣ .ENV DOSYASI
`.env` dosyası **DEĞİŞMEZ** (Asterisk uzak sunucuda):

```ini
# VERİTABANI (Uzak sunucu - değişmez)
DB_HOST=213.14.87.158
DB_NAME=ANABILIMNEW
DB_USER=astera
DB_PASS=astera2012

# ASTERISK SSH (Uzak sunucu - değişmez)
ASTERISK_SSH_HOST=213.14.87.158
ASTERISK_SSH_PORT=45555
ASTERISK_SSH_USER=root
ASTERISK_SSH_PASS=KKAstera*2o21@.!

# Diğer ayarlar aynı kalır
```

**Test bağlantısı:**
```powershell
d:\xampp\php\php.exe test_connections.php
```

---

### 3️⃣ WINDOWS TTS AYARLARI
Windows TTS yeni makinede çalışmalı:

**Kontrol:**
```powershell
# TTS seslerini listele
Add-Type -AssemblyName System.Speech
$synth = New-Object System.Speech.Synthesis.SpeechSynthesizer
$synth.GetInstalledVoices() | ForEach-Object { $_.VoiceInfo.Name }
```

**Test TTS:**
```powershell
d:\xampp\php\php.exe test_tts.php
```

---

### 4️⃣ KLASÖR İZİNLERİ
Aşağıdaki klasörlere yazma izni gerekli:

- [ ] `logs/` (Log kayıtları)
- [ ] `tmp/` (Geçici dosyalar)
- [ ] `audio/` (Ses dosyaları)
- [ ] `uploads/` (Excel dosyaları)

**İzin ver:**
```powershell
icacls "D:\xampp\htdocs\WindowsOtomatikAramason97\logs" /grant Users:(OI)(CI)F
icacls "D:\xampp\htdocs\WindowsOtomatikAramason97\tmp" /grant Users:(OI)(CI)F
icacls "D:\xampp\htdocs\WindowsOtomatikAramason97\audio" /grant Users:(OI)(CI)F
icacls "D:\xampp\htdocs\WindowsOtomatikAramason97\uploads" /grant Users:(OI)(CI)F
```

---

### 5️⃣ TASK SCHEDULER GÖREVLERİ
**3 görev kurulmalı:**

#### A) cleanup_stuck_calls.php (Her 1 dakikada)
```powershell
cd D:\xampp\htdocs\WindowsOtomatikAramason97
.\setup_unlock_task.ps1
```

#### B) retry_scheduler.php (Her 5 dakikada)
```powershell
cd D:\xampp\htdocs\WindowsOtomatikAramason97
.\setup_retry_scheduler.ps1
```

#### C) cron/scheduled_calls.php (Her 2 dakikada) - Opsiyonel
```powershell
$action = New-ScheduledTaskAction -Execute "d:\xampp\php\php.exe" -Argument "D:\xampp\htdocs\WindowsOtomatikAramason97\cron\scheduled_calls.php" -WorkingDirectory "D:\xampp\htdocs\WindowsOtomatikAramason97"
$trigger = New-ScheduledTaskTrigger -Once -At (Get-Date) -RepetitionInterval (New-TimeSpan -Minutes 2) -RepetitionDuration ([TimeSpan]::MaxValue)
$principal = New-ScheduledTaskPrincipal -UserId "SYSTEM" -LogonType ServiceAccount -RunLevel Highest
Register-ScheduledTask -TaskName "OtomatikAramaZamanlanmis" -Action $action -Trigger $trigger -Principal $principal -Force
```

**Kontrol:**
```powershell
Get-ScheduledTask | Where-Object {$_.TaskName -like "*Otomatik*"}
```

---

### 6️⃣ SSH BAĞLANTISI TESTİ
Yeni makineden Asterisk'e SSH bağlantısı:

**Test:**
```powershell
d:\xampp\php\php.exe test_ssh.php
```

---

### 7️⃣ VERİTABANI BAĞLANTISI TESTİ
Yeni makineden MySQL bağlantısı:

**Test:**
```powershell
d:\xampp\php\php.exe test_db.php
```

---

### 8️⃣ APACHE/FIREWALL AYARLARI
- [ ] Apache 8009 portunda çalışıyor mu?
- [ ] Windows Firewall 8009 portunu açık mı?

**Firewall kuralı:**
```powershell
New-NetFirewallRule -DisplayName "XAMPP Apache 8009" -Direction Inbound -LocalPort 8009 -Protocol TCP -Action Allow
```

---

### 9️⃣ İLK GİRİŞ VE TEST
1. Tarayıcıdan giriş: http://192.168.150.11:8009/WindowsOtomatikAramason97/login.php
2. superadmin / Astera2021
3. Arama Yönetimi → Test araması başlat
4. Task Scheduler logları kontrol et:
   - `logs/cleanup_*.log`
   - `logs/retry_*.log`

---

## 🔧 SORUN GİDERME

### ❌ "Veritabanı bağlantı hatası"
**Çözüm:** MySQL sunucusu 213.14.87.158'den erişime açık mı?
```powershell
Test-NetConnection -ComputerName 213.14.87.158 -Port 3306
```

### ❌ "SSH bağlantı hatası"
**Çözüm:** SSH portu açık mı?
```powershell
Test-NetConnection -ComputerName 213.14.87.158 -Port 45555
```

### ❌ "TTS ses üretilemiyor"
**Çözüm:** Windows TTS servisi çalışıyor mu?
```powershell
Get-Service | Where-Object {$_.Name -like "*speech*"}
```

### ❌ "Task Scheduler çalışmıyor"
**Çözüm:** Görevler aktif mi?
```powershell
Get-ScheduledTask -TaskName "OtomatikArama*" | Select-Object TaskName, State, LastRunTime, NextRunTime
```

---

## 📞 DESTEK
Sorun yaşarsanız:
1. Log dosyalarını kontrol edin (`logs/` klasörü)
2. PHP error log: `d:\xampp\apache\logs\error.log`
3. Test scriptlerini çalıştırın

---

## ✅ TAŞIMA TAMAMLANDI MI?
- [ ] Web arayüzü açılıyor
- [ ] Giriş yapılıyor
- [ ] Test araması başarılı
- [ ] Task Scheduler görevleri çalışıyor
- [ ] SSH bağlantısı çalışıyor
- [ ] TTS ses üretiyor

**HEPSİ TAMAM İSE:** 🎉 Sistem hazır!
