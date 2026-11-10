# 🚀 YENİ MAKİNEYE KURULUM REHBERİ

## 📋 HIZLI ÖZET

1. ✅ XAMPP kur
2. ✅ Klasör kopyala (`C:\xampp\htdocs\WindowsOtomatikAramason97\`)
3. ✅ Database import et
4. ✅ `.env` güncelle
5. ✅ Task Scheduler'ı kur (tek tık!)
6. ✅ Hazır!

**Toplam Süre:** ~15 dakika

---

## 📦 ADIM 1: XAMPP KURULUMU

### A) XAMPP İndir ve Kur

**İndirme:**
```
https://www.apachefriends.org/download.html
```

**Kurulum:**
- Varsayılan ayarlarla kur: `C:\xampp\`
- Apache ve MySQL servislerini başlat

**Kontrol:**
```
http://localhost
```
XAMPP kontrol panelini görmeli.

---

## 📁 ADIM 2: DOSYALARI KOPYALA

### A) Eski Makineden

Tüm klasörü kopyalayın:
```
C:\xampp\htdocs\WindowsOtomatikAramason97\
```

### B) Yeni Makineye

Yapıştırın:
```
C:\xampp\htdocs\WindowsOtomatikAramason97\
```

**VEYA**

Eğer farklı isimde klasör kullanıyorsanız, yapıştırdıktan sonra `.bat` dosyalarındaki yolları güncelleyin.

---

## 🗄️ ADIM 3: DATABASE IMPORT

### A) Eski Makineden Export

**Yöntem 1: phpMyAdmin**
```
http://localhost/phpmyadmin
1. ANABILIMNEW database'i seç
2. "Export" → "Go"
3. anabilimnew.sql dosyası indirilecek
```

**Yöntem 2: Komut Satırı**
```cmd
cd C:\xampp\mysql\bin
mysqldump -u astera -p ANABILIMNEW > anabilimnew.sql
```

### B) Yeni Makineye Import

**Yöntem 1: phpMyAdmin**
```
http://localhost/phpmyadmin
1. "New" → Database adı: ANABILIMNEW → Create
2. ANABILIMNEW'i seç
3. "Import" → anabilimnew.sql seç → Go
```

**Yöntem 2: Komut Satırı**
```cmd
cd C:\xampp\mysql\bin
mysql -u root -p -e "CREATE DATABASE ANABILIMNEW"
mysql -u root -p ANABILIMNEW < anabilimnew.sql
```

---

## ⚙️ ADIM 4: .ENV DOSYASINI GÜNCELLE

`C:\xampp\htdocs\WindowsOtomatikAramason97\.env` dosyasını açın:

### A) Lokal MySQL (Önerilen)

```ini
# VERİTABANI (Lokal)
DB_HOST=localhost
DB_NAME=ANABILIMNEW
DB_USER=root
DB_PASS=

# ASTERISK SSH (Uzak sunucu)
ASTERISK_SSH_HOST=213.14.87.158
ASTERISK_SSH_PORT=45555
ASTERISK_SSH_USER=root
ASTERISK_SSH_PASS=KKAstera*2o21@.!

# AMI (Uzak sunucu)
AMI_HOST=213.14.87.158
AMI_PORT=5038
AMI_USER=astera
AMI_PASS=astera2012
```

### B) Her Şey Lokal

```ini
# VERİTABANI (Lokal)
DB_HOST=localhost
DB_NAME=ANABILIMNEW
DB_USER=root
DB_PASS=

# ASTERISK SSH (Lokal - eğer Asterisk de bu makinedeyse)
ASTERISK_SSH_HOST=localhost
ASTERISK_SSH_PORT=22
ASTERISK_SSH_USER=root
ASTERISK_SSH_PASS=your_password

# AMI (Lokal)
AMI_HOST=localhost
AMI_PORT=5038
AMI_USER=astera
AMI_PASS=astera2012
```

---

## 🔧 ADIM 5: TASK SCHEDULER KURULUM (TEK TIK!)

### Yöntem 1: Otomatik (Önerilen) ⚡

1. Dosya yolunu açın:
   ```
   C:\xampp\htdocs\WindowsOtomatikAramason97\
   ```

2. **`install_all_tasks.bat`** dosyasına **SAĞ TIK**

3. **"Yönetici olarak çalıştır"** seçin

4. Bekleyin... 3 görev otomatik kurulacak!

5. Tamamlandığında "Kurulum Tamamlandı!" mesajı göreceksiniz

**Kurulacak Görevler:**
- ✅ `OtomatikAramaUnlock` - Her 1 dakika
- ✅ `OtomatikAramaTekrar` - Her 5 dakika  
- ✅ `OtomatikAramaZamanlanmis` - Her 2 dakika

---

### Yöntem 2: Manuel

Eğer `.bat` dosyası çalışmazsa, her `.bat` dosyasını tek tek manuel ekleyin:

1. Windows + R → `taskschd.msc` → Enter
2. Sağ panel → "Görev Oluştur"
3. **Genel** sekmesi:
   - Ad: `OtomatikAramaUnlock`
   - ☑ En yüksek ayrıcalıklarla çalıştır
4. **Tetikleyiciler** sekmesi:
   - Yeni → Günlük → Tekrarla: Her 1 dakika → Süre: Sınırsız
5. **Eylemler** sekmesi:
   - Program: `C:\xampp\htdocs\WindowsOtomatikAramason97\task1_cleanup.bat`
6. **Tamam**

Diğer görevler için de aynısını tekrarlayın.

---

## ✅ ADIM 6: KONTROL VE TEST

### A) Görevleri Kontrol Et

Çift tıklayın:
```
check_tasks.bat
```

**VEYA** Komut satırı:
```cmd
schtasks /query /tn "OtomatikArama*"
```

3 görev görmelisiniz (Ready/Running durumunda).

---

### B) Web Arayüzünü Aç

```
http://localhost/WindowsOtomatikAramason97/login.php
```

**Giriş:**
- Kullanıcı: `superadmin`
- Şifre: `Astera2021`

Eğer giriş yapamıyorsanız:
```cmd
cd C:\xampp\htdocs\WindowsOtomatikAramason97
C:\xampp\php\php.exe reset_superadmin.php
```

---

### C) Test Araması

1. Arama Yönetimi'ne gidin
2. Excel import edin
3. "Arama Başlat" tıklayın
4. Logları kontrol edin:
   ```
   C:\xampp\htdocs\WindowsOtomatikAramason97\logs\
   ```

---

## 🔥 ADIM 7: FFMPEG KURULUMU (Opsiyonel)

Eğer TTS kullanıyorsanız FFmpeg gerekli:

### Otomatik:
```powershell
cd C:\xampp\htdocs\WindowsOtomatikAramason97
.\install_ffmpeg.ps1
```

### Manuel:
1. İndirin: https://ffmpeg.org/download.html
2. `ffmpeg.exe` → `C:\xampp\php\ffmpeg.exe` kopyalayın
3. `.env` güncelleyin: `FFMPEG_PATH=C:\xampp\php\ffmpeg.exe`

---

## 📊 SORUN GİDERME

### ❌ "MySQL bağlanamıyor"

**Kontrol:**
```cmd
C:\xampp\mysql\bin\mysql.exe -u root -p -e "SHOW DATABASES;"
```

**Çözüm:**
- XAMPP Control Panel → MySQL → Start
- `.env` dosyasında `DB_HOST=localhost` olmalı

---

### ❌ "Task Scheduler çalışmıyor"

**Kontrol:**
```cmd
schtasks /query /tn "OtomatikArama*" /v
```

**Çözüm:**
- Görevler var mı kontrol edin
- Görev "Running" veya "Ready" durumunda mı?
- Log dosyalarını kontrol edin: `logs/` klasörü

---

### ❌ "Login yapamıyorum"

**Çözüm:**
```cmd
cd C:\xampp\htdocs\WindowsOtomatikAramason97
C:\xampp\php\php.exe reset_superadmin.php
```

Şifre: `Astera2021`

---

### ❌ "USER_SESSIONS hatası"

**Çözüm:**
```
http://localhost/WindowsOtomatikAramason97/check_user_sessions_table.php
```
→ "USER_SESSIONS Tablosunu Sil" butonuna basın

---

## 🎯 ÖNEMLİ NOTLAR

### 📁 Klasör Yapısı
```
C:\xampp\htdocs\WindowsOtomatikAramason97\
├── .env                          ← Ayarlar burda!
├── install_all_tasks.bat         ← TEK TIK KURULUM
├── check_tasks.bat               ← Kontrol
├── uninstall_all_tasks.bat       ← Kaldır
├── task1_cleanup.bat             ← Görev 1
├── task2_retry.bat               ← Görev 2
├── task3_scheduled.bat           ← Görev 3
├── logs\                         ← Log dosyaları
├── audio\                        ← Ses dosyaları
└── uploads\                      ← Excel dosyaları
```

### 🔑 Varsayılan Şifreler
- **MySQL:** root / (boş)
- **Web:** superadmin / Astera2021

### 📝 Log Dosyaları
```
C:\xampp\htdocs\WindowsOtomatikAramason97\logs\
- cleanup_2026-02-10.log
- retry_2026-02-10.log
```

---

## ✅ KONTROL LİSTESİ

Kurulum tamamlandı mı?

- [ ] XAMPP kurulu ve çalışıyor
- [ ] Klasör kopyalandı (`C:\xampp\htdocs\WindowsOtomatikAramason97\`)
- [ ] Database import edildi (ANABILIMNEW)
- [ ] `.env` güncellendi
- [ ] Task Scheduler kuruldu (3 görev)
- [ ] Web arayüzü açılıyor (`http://localhost/...`)
- [ ] Login yapılabiliyor (superadmin)
- [ ] Test araması başarılı

**HEPSİ TAMAM İSE:** 🎉 SİSTEM HAZIR!

---

## 🆘 DESTEK

Sorun yaşarsanız:

1. **Log dosyalarını kontrol edin:**
   ```
   C:\xampp\htdocs\WindowsOtomatikAramason97\logs\
   ```

2. **Apache log:**
   ```
   C:\xampp\apache\logs\error.log
   ```

3. **PHP error log:**
   ```
   C:\xampp\php\logs\php_error_log.txt
   ```

4. **Test scriptleri:**
   ```cmd
   cd C:\xampp\htdocs\WindowsOtomatikAramason97
   C:\xampp\php\php.exe test_connections.php
   ```

---

**HIZLI KURULUM: `install_all_tasks.bat` dosyasına sağ tık → Yönetici olarak çalıştır!** ⚡

**Toplam Süre: 15 dakika** 🚀
