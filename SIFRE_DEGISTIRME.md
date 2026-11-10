# 🔑 Şifre Değiştirme Kılavuzu

Bu kılavuz, sistemdeki şifreleri güvenli bir şekilde değiştirmeniz için adımları içerir.

---

## 🎯 Hızlı Şifre Değiştirme (3 Adım)

### 1️⃣ .env Dosyasını Düzenle

```bash
# .env dosyasını aç
notepad .env
```

**Değiştirebileceğiniz Şifreler:**

```bash
# Veritabanı Şifresi
DB_PASS=ESKİ_ŞİFRE        → DB_PASS=YENİ_ŞİFRE

# AMI Şifresi
AMI_PASS=ESKİ_ŞİFRE       → AMI_PASS=YENİ_ŞİFRE

# SSH Şifresi
ASTERISK_SSH_PASS=ESKİ_ŞİFRE → ASTERISK_SSH_PASS=YENİ_ŞİFRE
```

### 2️⃣ Sistemi Test Et

```bash
# Veritabanı bağlantısını test et
php test_database.php
```

**Beklenen Çıktı:**
```
✅ Veritabanı bağlantısı başarılı!
✅ CALLS tablosu var
✅ IMPORT_HISTORY tablosu var
```

### 3️⃣ Web Arayüzünü Kontrol Et

```
http://localhost/OtomatikArama/
```

- ✅ Login çalışıyor mu?
- ✅ Sayfalar açılıyor mu?
- ✅ Import çalışıyor mu?

---

## 📝 Detaylı Adımlar

### Senaryo 1: MySQL Şifresini Değiştir

#### Adım 1: MySQL'de Şifreyi Değiştir

```sql
-- MySQL'e giriş yap
mysql -u root -p

-- Şifreyi değiştir
ALTER USER 'astera'@'%' IDENTIFIED BY 'YeniGuvenliSifre123!';
FLUSH PRIVILEGES;
EXIT;
```

#### Adım 2: .env Dosyasını Güncelle

```bash
# .env dosyasını aç
notepad .env

# DB_PASS satırını değiştir
DB_PASS=YeniGuvenliSifre123!

# Kaydet ve kapat
```

#### Adım 3: Test Et

```bash
# Bağlantıyı test et
php test_database.php

# Başarısızsa eski şifreyi geri al
DB_PASS=EskiSifre
```

---

### Senaryo 2: SSH Şifresini Değiştir

#### Adım 1: Asterisk'te Şifreyi Değiştir

```bash
# SSH ile Asterisk'e bağlan
ssh root@192.168.181.16 -p 45555

# Root şifresini değiştir
passwd

# Yeni şifreyi gir (2 kez)
New password: YeniSSHSifre456!
Retype password: YeniSSHSifre456!

# Çıkış
exit
```

#### Adım 2: .env Dosyasını Güncelle

```bash
# .env dosyasını aç
notepad .env

# SSH şifresini değiştir
ASTERISK_SSH_PASS=YeniSSHSifre456!

# Kaydet ve kapat
```

#### Adım 3: Test Et

```cmd
# SSH bağlantısını test et
tools\plink.exe -ssh root@192.168.181.16 -P 45555 -pw YeniSSHSifre456! "echo OK"

# Beklenen çıktı: OK
```

---

### Senaryo 3: Login Şifresini Değiştir

#### Adım 1: login.php Dosyasını Düzenle

```php
// login.php dosyasını aç
notepad login.php

// Şifre kontrolünü bul (satır ~12):
if ($username === 'superadmin' && $password === 'Astera2020') {

// Değiştir:
if ($username === 'superadmin' && $password === 'YeniLoginSifre789!') {

// Kaydet ve kapat
```

#### Adım 2: Test Et

```
http://localhost/OtomatikArama/
Kullanıcı: superadmin
Şifre: YeniLoginSifre789!
```

---

## 🔒 Güçlü Şifre Oluşturma

### Şifre Üretici Script

```bash
# generate_password.bat
@echo off
php -r "echo substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()'), 0, 20);"
```

**Veya Online:**
- https://passwordsgenerator.net/
- https://www.lastpass.com/password-generator

### Güçlü Şifre Kriterleri

```
✅ Minimum 12 karakter
✅ Büyük harf (A-Z)
✅ Küçük harf (a-z)
✅ Rakam (0-9)
✅ Özel karakter (!@#$%^&*)
❌ Yaygın kelimeler (password, 123456)
❌ Kişisel bilgiler (isim, doğum tarihi)
❌ Klavye dizileri (qwerty, asdfgh)
```

**Örnek Güçlü Şifreler:**
```
K8m$pL2qR9vT@nX5
Fj#7wZ4kP!bH9yN2
Qx@3sV8mL#nD6tR1
```

---

## 📋 Şifre Değiştirme Kontrol Listesi

### Hazırlık ✅

- [ ] Mevcut şifreleri kaydet (güvenli yere not et)
- [ ] Sistemin yedeğini al
- [ ] Yeni güçlü şifreler oluştur
- [ ] Değişiklik zamanını planla (bakım saati)

### MySQL Şifresi 🗄️

- [ ] MySQL'de şifreyi değiştir
- [ ] `.env` dosyasını güncelle
- [ ] `php test_database.php` çalıştır
- [ ] Web arayüzünü test et
- [ ] Başarılıysa devam et

### SSH Şifresi 🔐

- [ ] Asterisk'te root şifresini değiştir
- [ ] `.env` dosyasını güncelle
- [ ] `plink.exe` ile test et
- [ ] Ses dosyası yüklemeyi test et
- [ ] Başarılıysa devam et

### AMI Şifresi 📡

- [ ] Asterisk AMI şifresini değiştir (`/etc/asterisk/manager.conf`)
- [ ] `.env` dosyasını güncelle
- [ ] AMI bağlantısını test et
- [ ] Asterisk reload: `asterisk -rx "manager reload"`

### Login Şifresi 🔑

- [ ] `login.php` dosyasını düzenle
- [ ] Yeni şifre ile giriş yap
- [ ] Çıkış yap ve tekrar dene
- [ ] Başarılıysa kaydet

### Son Kontroller ✅

- [ ] Tüm şifreler değişti
- [ ] Tüm testler başarılı
- [ ] `.env` dosyası güvende
- [ ] Yedek alındı
- [ ] Dokümante edildi

---

## 🚨 Sorun Giderme

### Problem 1: MySQL Bağlanamıyor

**Hata:**
```
PDOException: Access denied for user 'astera'@'...'
```

**Çözüm:**
```sql
-- MySQL'e root ile giriş yap
mysql -u root -p

-- Şifre kontrolü
SELECT user, host FROM mysql.user WHERE user='astera';

-- Şifreyi sıfırla
ALTER USER 'astera'@'%' IDENTIFIED BY 'DogruSifre';
FLUSH PRIVILEGES;
```

### Problem 2: SSH Bağlanamıyor

**Hata:**
```
Access denied
```

**Çözüm:**
```bash
# Asterisk konsolundan root şifresini sıfırla
# (Fiziksel veya KVM erişimi gerekli)

# Single user mode'a boot et
# Root şifresini değiştir
passwd root

# Normal boot
reboot
```

### Problem 3: Web Arayüzü Çalışmıyor

**Hata:**
```
Database connection error
```

**Çözüm:**
```bash
# 1. .env dosyasını kontrol et
notepad .env

# 2. config.secure.php kullanıldığından emin ol
# Tüm PHP dosyalarında:
require_once 'config.secure.php';

# 3. EnvLoader.php var mı?
dir includes\EnvLoader.php

# 4. Test et
php test_database.php
```

---

## 💾 Yedekleme

### .env Dosyasını Yedekle

```bash
# Güvenli bir yere kopyala
copy .env .env.backup.%date:~-4,4%%date:~-7,2%%date:~-10,2%

# Örnek: .env.backup.20260128
```

**Önemli:** Yedek dosyayı güvenli bir yerde sakla (şifreli disk, password manager)

### Şifreleri Güvenli Sakla

**Password Manager Kullan:**
- LastPass
- 1Password
- Bitwarden
- KeePass (offline)

**Şifrelenmiş Dosya:**
```bash
# 7-Zip ile şifrele
7z a -p passwords.zip .env.backup
```

---

## 📅 Düzenli Şifre Değiştirme

### Önerilen Süre

```
🔴 Kritik (DB, SSH, Root): Her 90 gün
🟡 Önemli (AMI, Login):     Her 180 gün
🟢 Normal (Diğer):          Her 365 gün
```

### Hatırlatıcı Kur

```cmd
# Windows Task Scheduler ile hatırlatıcı
schtasks /Create /TN "PasswordReminder" /SC MONTHLY /MO 3 /TR "msg %USERNAME% 'Şifreleri değiştirme zamanı!'"
```

---

## 🔄 Toplu Şifre Değiştirme Scripti

```bash
# change_passwords.bat
@echo off
echo ═══════════════════════════════════════
echo    Toplu Şifre Değiştirme
echo ═══════════════════════════════════════
echo.

REM Yeni şifreleri sor
set /p NEW_DB_PASS="Yeni MySQL Şifresi: "
set /p NEW_SSH_PASS="Yeni SSH Şifresi: "
set /p NEW_LOGIN_PASS="Yeni Login Şifresi: "

echo.
echo .env dosyası güncelleniyor...

REM .env dosyasını güncelle
powershell -Command "(gc .env) -replace 'DB_PASS=.*', 'DB_PASS=%NEW_DB_PASS%' | Out-File -encoding ASCII .env"
powershell -Command "(gc .env) -replace 'ASTERISK_SSH_PASS=.*', 'ASTERISK_SSH_PASS=%NEW_SSH_PASS%' | Out-File -encoding ASCII .env"

echo ✅ .env dosyası güncellendi

REM Test
echo.
echo Test ediliyor...
php test_database.php

echo.
echo ═══════════════════════════════════════
echo    Şifreler Değiştirildi!
echo ═══════════════════════════════════════
echo.
echo Sonraki adımlar:
echo 1. MySQL şifresini değiştir
echo 2. SSH şifresini değiştir
echo 3. login.php dosyasını düzenle
echo.
pause
```

---

## 📝 Şifre Değişiklik Logu

```
# password_changes.txt

[2026-01-28 14:30] MySQL şifresi değiştirildi (astera@%) - Admin
[2026-01-28 14:35] SSH şifresi değiştirildi (root@asterisk) - Admin
[2026-01-28 14:40] Login şifresi değiştirildi (superadmin) - Admin

Sonraki değişim: 2026-04-28
```

---

## 🔐 Acil Durum Şifre Sıfırlama

### MySQL Root Şifresi Kayboldu

```bash
# 1. MySQL'i durdur
net stop MySQL

# 2. Güvenli modda başlat
mysqld --skip-grant-tables

# 3. Yeni pencerede MySQL'e giriş yap
mysql -u root

# 4. Şifreyi sıfırla
FLUSH PRIVILEGES;
ALTER USER 'root'@'localhost' IDENTIFIED BY 'YeniSifre';
EXIT;

# 5. MySQL'i normal başlat
net stop MySQL
net start MySQL
```

---

**Şifre değiştirme bu kadar basit!** 🔑✨

**Hatırlatma:** Düzenli şifre değiştirme = Daha güvenli sistem! 🔒
