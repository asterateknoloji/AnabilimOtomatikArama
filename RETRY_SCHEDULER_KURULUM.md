# RETRY SCHEDULER KURULUM

## ❌ SORUN: Ötelenen Aramalar Başlamıyor

`retry_scheduler.php` dosyası çalışma zamanı gelmiş aramaları (ilk arama + retry) başlatır.

**Şu anda:** Task **Disabled** durumunda!

## ✅ ÇÖZÜM: Task'ı Aktif Edin

### Yöntem 1: PowerShell (Administrator gerekli)

**PowerShell'i Yönetici olarak açın**, sonra:

```powershell
schtasks /change /tn "OtomatikAramaTekrar" /enable
```

### Yöntem 2: Task Scheduler (GUI)

1. **Task Scheduler'ı açın:**
   - Windows tuşu + R
   - `taskschd.msc` yazın, Enter

2. **Task'ı bulun:**
   - Sol panelde: Task Scheduler Library
   - Sağ panelde: `OtomatikAramaTekrar` göreceksiniz

3. **Task'ı Enable edin:**
   - Sağ tık → **Enable**
   - Veya: Task'ı seçin → Sağ panelde "Enable" butonuna tıklayın

4. **Kontrol edin:**
   - Status: **Ready** olmalı
   - Next Run Time: Bir tarih göstermeli

## 📋 TASK DETAYLARI

- **İsim:** OtomatikAramaTekrar
- **Script:** `retry_scheduler.php`
- **Çalışma:** Her **5 dakika**
- **Görev:** 
  - İlk kez aranacakları başlatır (CallCount=0)
  - Retry zamanı gelmiş olanları başlatır (CallCount>0)

## 🧪 TEST

Task'ı manuel çalıştırın:

```powershell
Start-ScheduledTask -TaskName "OtomatikAramaTekrar"
```

Veya direkt script'i çalıştırın:

```powershell
d:\xampp\php\php.exe retry_scheduler.php
```

## ⚙️ SİSTEM ÇALIŞMA MANTIĞI

### Arama Başlatma Sistemi:

1. **Manuel Başlatma** → `arama_yonetimi.php` → "START (TÜM)" butonu
2. **Otomatik Retry** → `retry_scheduler.php` (Her 5 dk) → Zamanı gelmiş aramaları başlatır

### Retry Akışı:

1. Çağrı reddedilir → AGI veya unlock script çalışır
2. `CallTime` 60 dakika ilerisine ayarlanır
3. `retry_scheduler.php` her 5 dakikada kontrol eder
4. `CallTime <= NOW()` ise aramayı başlatır

## ✅ SONUÇ

Task'ı enable ettikten sonra sistem tamamen otomatik çalışacak!
