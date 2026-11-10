# 🚀 HIZLI KURULUM REHBERİ

Yeni makine için tek komutla kurulum.

---

## ✅ ÖN KOŞULLAR

1. **XAMPP kurulu** (`D:\xampp\`)
2. **Klasör taşındı** (`D:\xampp\htdocs\WindowsOtomatikAramason97\`)
3. **Apache çalışıyor** (port 8009)

---

## 🎯 HIZLI KURULUM

### 1️⃣ PowerShell'i Yönetici Olarak Aç
```
Windows + X → Windows PowerShell (Admin)
```

### 2️⃣ Klasöre Git
```powershell
cd D:\xampp\htdocs\WindowsOtomatikAramason97
```

### 3️⃣ Tüm Testleri Çalıştır
```powershell
d:\xampp\php\php.exe test_connections.php
```

**Beklenen çıktı:**
```
✅ PHP 5.4+ gereksinimi
✅ pdo_mysql
✅ openssl
✅ curl
✅ mbstring
✅ .env yüklendi
✅ MySQL bağlantısı başarılı
✅ SSH portu açık
✅ TTS sesi bulundu
```

### 4️⃣ Task Scheduler Görevlerini Kur
```powershell
.\setup_all_tasks.ps1
```

**Beklenen çıktı:**
```
✅ OtomatikAramaUnlock kuruldu
✅ OtomatikAramaTekrar kuruldu
✅ OtomatikAramaZamanlanmis kuruldu
```

### 5️⃣ Görevlerin Durumunu Kontrol Et
```powershell
Get-ScheduledTask -TaskName "OtomatikArama*" | Format-Table TaskName, State, NextRunTime
```

**Beklenen çıktı:**
```
TaskName                    State  NextRunTime
--------                    -----  -----------
OtomatikAramaUnlock        Ready  9.02.2026 15:23:00
OtomatikAramaTekrar        Ready  9.02.2026 15:25:00
OtomatikAramaZamanlanmis   Ready  9.02.2026 15:22:00
```

### 6️⃣ Web Arayüzünü Test Et
```
http://192.168.150.11:8009/WindowsOtomatikAramason97/login.php
```

**Giriş bilgileri:**
- Kullanıcı: `superadmin`
- Şifre: `Astera2021`

---

## 🔍 SORUN ÇIKTI MI?

### ❌ Test başarısız
```powershell
# Detaylı testler
d:\xampp\php\php.exe test_db.php        # MySQL
d:\xampp\php\php.exe test_ssh.php       # SSH
d:\xampp\php\php.exe test_tts.php       # TTS
```

### ❌ Task kurulamadı
```powershell
# PowerShell'i YÖNETİCİ olarak açtığınızdan emin olun!
# Sağ tık → Run as Administrator
```

### ❌ Firewall engelliyor
```powershell
# Apache için port aç
New-NetFirewallRule -DisplayName "XAMPP Apache 8009" -Direction Inbound -LocalPort 8009 -Protocol TCP -Action Allow
```

---

## ✅ KURULUM TAMAMLANDI!

Şimdi yapabilecekleriniz:

1. **Test araması başlat**
   - Arama Yönetimi → Excel İmport → Arama Başlat

2. **Logları kontrol et**
   ```powershell
   Get-Content D:\xampp\htdocs\WindowsOtomatikAramason97\logs\cleanup_*.log -Tail 20
   ```

3. **Task Scheduler'ı izle**
   ```powershell
   taskschd.msc
   ```

---

## 📞 ACİL DESTEK

Sorun devam ediyorsa:

1. Apache error log:
   ```
   D:\xampp\apache\logs\error.log
   ```

2. PHP error log:
   ```
   D:\xampp\php\logs\php_error_log.txt
   ```

3. Uygulama logları:
   ```
   D:\xampp\htdocs\WindowsOtomatikAramason97\logs\
   ```

---

**TOPLAM SÜRE: ~5 dakika** ⏱️
