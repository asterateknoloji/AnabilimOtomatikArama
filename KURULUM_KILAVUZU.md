# 🚀 Otomatik Arama Sistemi - Kurulum Kılavuzu

Bu kılavuz, sistemin farklı bir web server'a taşınması için gerekli adımları içerir.

---

## 📋 Ön Gereksinimler

### Web Server:
- ✅ PHP 5.4+ (PHP 7.x önerilir)
- ✅ MySQL 5.5+ veya MariaDB
- ✅ Apache veya Nginx
- ✅ PHP Extensions:
  - `mysqli` veya `pdo_mysql`
  - `json`
  - `mbstring`
  - `curl`
  - **NOT:** `zip` extension'ı GEREKMEZ (SimpleXLSX kullanılıyor)

### Asterisk PBX:
- ✅ Asterisk 13+
- ✅ SSH erişimi (root veya sudo yetkisi)
- ✅ AGI desteği

### Windows (Web Server):
- ✅ PuTTY araçları (`plink.exe`, `pscp.exe`)
- ✅ Task Scheduler erişimi

---

## 🔧 Kurulum Adımları

### 1️⃣ Dosyaları Kopyala

```bash
# OtomatikArama klasörünü yeni sunucuya kopyala
# Örnek:
C:\xampp\htdocs\OtomatikArama
```

**Önemli Klasörler:**
```
OtomatikArama/
├── includes/          # PHP sınıfları
├── database/          # SQL scriptleri
├── asterisk/          # Ses dosyaları (.wav)
├── tools/             # plink.exe, pscp.exe
├── logs/              # Log dosyaları (yazılabilir olmalı)
├── temp/              # Geçici dosyalar (yazılabilir olmalı)
└── vendor/            # Composer bağımlılıkları
```

---

### 2️⃣ Veritabanı Ayarları

#### A) `config.php` Dosyasını Düzenle

```php
<?php
// Veritabanı bağlantısı
$dbHost = '192.168.181.16';     // Asterisk sunucu IP
$dbName = 'asteriskcdrdb';      
$dbUser = 'astera';             
$dbPass = 'astera2012';         

// Asterisk SSH ayarları
define('ASTERISK_SSH_HOST', '192.168.181.16');
define('ASTERISK_SSH_PORT', 45555);
define('ASTERISK_SSH_USER', 'root');
define('ASTERISK_SSH_PASS', 'SIFRENIZ');
define('ASTERISK_SPOOL_PATH', '/var/spool/asterisk/outgoing/');
define('ASTERISK_SOUNDS_PATH', '/var/lib/asterisk/sounds/custom/');
define('CALLER_ID', '02129999999'); // Arayan numara
```

#### B) Veritabanı Tablolarını Oluştur

```bash
# SQL scriptlerini sırayla çalıştır:
mysql -u astera -p asteriskcdrdb < database/schema.sql
mysql -u astera -p asteriskcdrdb < database/add_retry_settings.sql
mysql -u astera -p asteriskcdrdb < database/add_template_actions.sql
mysql -u astera -p asteriskcdrdb < database/add_audio_file_fields.sql
mysql -u astera -p asteriskcdrdb < database/add_concurrent_limit.sql
mysql -u astera -p asteriskcdrdb < database/update_retry_to_minutes.sql
```

**PowerShell'den çalıştırma:**
```powershell
Get-Content database\schema.sql | D:\xampp\mysql\bin\mysql.exe -u astera -pastera2012 -h 192.168.181.16 asteriskcdrdb
```

---

### 3️⃣ Composer Bağımlılıklarını Kur

```bash
cd D:\xampp\htdocs\OtomatikArama

# Composer kurulu değilse:
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php -r "unlink('composer-setup.php');"

# Bağımlılıkları kur:
php composer.phar install
```

**Kurulacak Paketler:**
- `phpoffice/phpspreadsheet` (Excel export için)
- `shuchkin/simplexlsx` (Excel import için - ZIP extension gerektirmez)

---

### 4️⃣ PuTTY Araçlarını Yapılandır

#### A) İndir ve Yerleştir

```bash
# tools/ klasörüne koy:
tools/
├── plink.exe
└── pscp.exe
```

**İndirme:**
- https://www.chiark.greenend.org.uk/~sgtatham/putty/latest.html

#### B) SSH Key Cache Temizle (İlk Bağlantı)

```cmd
tools\plink.exe -ssh root@192.168.181.16 -P 45555 -pw SIFRENIZ exit
```

---

### 5️⃣ Klasör İzinlerini Ayarla

```bash
# logs/ klasörü yazılabilir olmalı
chmod 777 logs/

# temp/ klasörü yazılabilir olmalı
chmod 777 temp/
```

**Windows'ta:**
- Sağ tık → Özellikler → Güvenlik
- IUSR ve IIS_IUSRS için "Tam Denetim" ver

---

### 6️⃣ Asterisk Yapılandırması

#### A) AGI Scriptlerini Yükle

```bash
# AGI scriptleri Asterisk sunucusuna kopyalanacak
# includes/ klasöründeki PHP dosyaları

# Otomatik yükleme için:
php upload_agi_scripts.php
```

**Manuel Yükleme:**
```bash
# Web server'dan AGI scriptlerini kopyala
scp -P 45555 includes/*.php root@192.168.181.16:/var/lib/asterisk/agi-bin/
```

#### B) Ses Dosyalarını Yükle

```bash
# asterisk/ klasöründeki .wav dosyaları

# Batch ile:
upload_turkish_prompts.bat

# Manuel:
tools\pscp.exe -P 45555 -pw SIFRENIZ asterisk\*.wav root@192.168.181.16:/var/lib/asterisk/sounds/otomatik/
```

#### C) Dialplan Dosyalarını Oluştur

```bash
# Şablonlar için dialplan oluştur
php regenerate_templates.php
```

---

### 7️⃣ Windows Task Scheduler Ayarla

#### A) Retry Scheduler (Her 5 Dakikada Bir)

```xml
<Task>
  <Name>OtomatikArama_Retry</Name>
  <Triggers>
    <TimeTrigger>
      <Repetition>
        <Interval>PT5M</Interval>
      </Repetition>
    </TimeTrigger>
  </Triggers>
  <Actions>
    <Exec>
      <Command>D:\xampp\php\php.exe</Command>
      <Arguments>D:\xampp\htdocs\OtomatikArama\retry_scheduler.php</Arguments>
    </Exec>
  </Actions>
</Task>
```

**Batch ile kurulum:**
```cmd
setup_scheduler.bat
```

#### B) Cleanup Stuck Calls (Her 10 Dakikada Bir)

```xml
<Task>
  <Name>OtomatikArama_Cleanup</Name>
  <Triggers>
    <TimeTrigger>
      <Repetition>
        <Interval>PT10M</Interval>
      </Repetition>
    </TimeTrigger>
  </Triggers>
  <Actions>
    <Exec>
      <Command>D:\xampp\php\php.exe</Command>
      <Arguments>D:\xampp\htdocs\OtomatikArama\cleanup_stuck_calls.php</Arguments>
    </Exec>
  </Actions>
</Task>
```

---

### 8️⃣ Test Et

#### A) Veritabanı Bağlantısı

```bash
php test_database.php
```

**Beklenen Çıktı:**
```
✅ Veritabanı bağlantısı başarılı!
✅ CALLS tablosu var
✅ IMPORT_HISTORY tablosu var
✅ MESSAGE_TEMPLATES tablosu var
✅ PROJECTS tablosu var
```

#### B) SSH Bağlantısı

```bash
php check_ssh.php
```

#### C) Web Arayüzü

```
http://SUNUCU_IP/OtomatikArama/
```

**Login:**
- Kullanıcı: `superadmin`
- Şifre: `Astera2020`

---

## 🔐 Güvenlik Ayarları

### 1️⃣ config.php İzinleri

```bash
chmod 600 config.php
```

### 2️⃣ .htaccess Oluştur (Özel Dosyalar)

```apache
# .htaccess
<Files "config.php">
    Order Allow,Deny
    Deny from all
</Files>

<FilesMatch "\.(log|md|bat|sh)$">
    Order Allow,Deny
    Deny from all
</FilesMatch>
```

### 3️⃣ Veritabanı Kullanıcısı İzinleri

```sql
-- Sadece gerekli izinleri ver
GRANT SELECT, INSERT, UPDATE, DELETE ON asteriskcdrdb.* TO 'astera'@'WEB_SERVER_IP';
FLUSH PRIVILEGES;
```

---

## 📊 Sistem Gereksinimleri

### Minimum:
- **CPU:** 2 Core
- **RAM:** 2 GB
- **Disk:** 10 GB
- **Network:** 100 Mbps

### Önerilen:
- **CPU:** 4 Core
- **RAM:** 4 GB
- **Disk:** 20 GB SSD
- **Network:** 1 Gbps

---

## 🐛 Sorun Giderme

### Veritabanı Bağlantı Hatası

```bash
# MySQL bağlantısını test et
mysql -u astera -p -h 192.168.181.16 asteriskcdrdb
```

**Çözüm:**
- Firewall'u kontrol et (3306 portu açık mı?)
- MySQL remote erişim ayarlarını kontrol et
- `bind-address` ayarını kontrol et

### SSH Bağlantı Hatası

```bash
# SSH bağlantısını test et
tools\plink.exe -ssh root@192.168.181.16 -P 45555 -pw SIFRENIZ "echo OK"
```

**Çözüm:**
- SSH portu açık mı?
- SSH şifresi doğru mu?
- SSH key cache temizlendi mi?

### Excel Import Hatası

**Hata:** "ZIP extension required"

**Çözüm:**
- SimpleXLSX kullanıldığından emin ol
- `quick_import.php` dosyasını kontrol et

### Task Scheduler Çalışmıyor

```cmd
# Task durumunu kontrol et
schtasks /Query /TN "OtomatikArama_Retry" /V
```

**Çözüm:**
- Task Administrator yetkileri ile çalışıyor mu?
- PHP yolu doğru mu?
- Log dosyalarını kontrol et: `logs/cleanup_*.log`

---

## 📞 Yedekleme

### Otomatik Yedekleme Scripti

```bash
# backup.bat
@echo off
set BACKUP_DIR=D:\Backups\OtomatikArama
set DATE=%date:~-4,4%%date:~-7,2%%date:~-10,2%

# Veritabanı yedekle
D:\xampp\mysql\bin\mysqldump.exe -u astera -pastera2012 -h 192.168.181.16 asteriskcdrdb > %BACKUP_DIR%\db_%DATE%.sql

# Dosyaları yedekle
xcopy /E /I /Y D:\xampp\htdocs\OtomatikArama %BACKUP_DIR%\files_%DATE%\
```

---

## 🚀 Hızlı Kurulum Scripti

```bash
# install.bat
@echo off
echo ========================================
echo Otomatik Arama Sistemi - Kurulum
echo ========================================

# Composer bağımlılıkları
php composer.phar install

# Veritabanı oluştur
for %%f in (database\*.sql) do (
    echo Çalıştırılıyor: %%f
    Get-Content %%f | D:\xampp\mysql\bin\mysql.exe -u astera -pastera2012 -h 192.168.181.16 asteriskcdrdb
)

# Task Scheduler
call setup_scheduler.bat

# Asterisk dosyaları
call upload_turkish_prompts.bat

echo ========================================
echo Kurulum tamamlandı!
echo ========================================
pause
```

---

## 📚 Ek Kaynaklar

- [Hızlı Başlangıç](HIZLI_BASLANGIC.md)
- [API Dokümantasyonu](API_DOCS.md)
- [Sorun Giderme](TROUBLESHOOTING.md)
- [Changelog](CHANGELOG.md)

---

## 📧 Destek

Sorun yaşarsanız:
1. Log dosyalarını kontrol edin (`logs/`)
2. Test scriptlerini çalıştırın
3. Hata mesajlarını kaydedin

---

**Son Güncelleme:** 28 Ocak 2026
**Versiyon:** 1.0.0
