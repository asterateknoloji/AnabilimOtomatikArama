# 🚚 Yeni Sunucuya Taşıma - Hızlı Başlangıç

Bu dosya sistemi başka bir web server'a taşımak için **minimum adımları** içerir.

---

## 🎯 Hızlı Kurulum (5 Dakika)

### 1️⃣ Klasörü Kopyala

```bash
# OtomatikArama klasörünü yeni sunucuya kopyala
C:\xampp\htdocs\OtomatikArama\
```

### 2️⃣ Config Dosyasını Düzenle

`config.php` dosyasını aç ve düzenle:

```php
$dbHost = '192.168.181.16';     // Asterisk sunucu IP
$dbUser = 'astera';             // Veritabanı kullanıcısı
$dbPass = 'astera2012';         // Veritabanı şifresi

define('ASTERISK_SSH_HOST', '192.168.181.16');
define('ASTERISK_SSH_PORT', 45555);
define('ASTERISK_SSH_USER', 'root');
define('ASTERISK_SSH_PASS', 'SIFRENIZ');  // ← BURAYI DEĞİŞTİR
define('CALLER_ID', '02129999999');       // ← BURAYI DEĞİŞTİR
```

### 3️⃣ Hızlı Kurulum Scripti Çalıştır

```cmd
# Administrator olarak
HIZLI_KURULUM.bat
```

**Bu script:**
- ✅ PHP ve MySQL kontrolü yapar
- ✅ Veritabanı bağlantısını test eder
- ✅ Composer bağımlılıklarını kurar
- ✅ Veritabanı tablolarını oluşturur
- ✅ Task Scheduler'ı kurar
- ✅ Testleri çalıştırır

### 4️⃣ Tarayıcıdan Aç

```
http://SUNUCU_IP/OtomatikArama/
```

**Login:**
- Kullanıcı: `superadmin`
- Şifre: `Astera2020`

---

## 📋 Manuel Kurulum (Detaylı)

Otomatik script çalışmazsa manuel adımları takip edin:

### Adım 1: Veritabanı Tabloları

```bash
# PowerShell'den çalıştır
Get-Content database\schema.sql | D:\xampp\mysql\bin\mysql.exe -u astera -pastera2012 -h 192.168.181.16 asteriskcdrdb
Get-Content database\add_retry_settings.sql | D:\xampp\mysql\bin\mysql.exe -u astera -pastera2012 -h 192.168.181.16 asteriskcdrdb
Get-Content database\add_template_actions.sql | D:\xampp\mysql\bin\mysql.exe -u astera -pastera2012 -h 192.168.181.16 asteriskcdrdb
Get-Content database\add_audio_file_fields.sql | D:\xampp\mysql\bin\mysql.exe -u astera -pastera2012 -h 192.168.181.16 asteriskcdrdb
Get-Content database\add_concurrent_limit.sql | D:\xampp\mysql\bin\mysql.exe -u astera -pastera2012 -h 192.168.181.16 asteriskcdrdb
Get-Content database\update_retry_to_minutes.sql | D:\xampp\mysql\bin\mysql.exe -u astera -pastera2012 -h 192.168.181.16 asteriskcdrdb
```

### Adım 2: Composer

```bash
php composer.phar install
```

### Adım 3: Test

```bash
php test_database.php
```

### Adım 4: Task Scheduler (Administrator)

```cmd
schtasks /Create /TN "OtomatikArama_Retry" /TR "D:\xampp\php\php.exe D:\xampp\htdocs\OtomatikArama\retry_scheduler.php" /SC MINUTE /MO 5 /F

schtasks /Create /TN "OtomatikArama_Cleanup" /TR "D:\xampp\php\php.exe D:\xampp\htdocs\OtomatikArama\cleanup_stuck_calls.php" /SC MINUTE /MO 10 /F
```

---

## ✅ Kontrol Listesi

**Kurulum tamamlandıysa:**

- [ ] `config.php` düzenlendi
- [ ] Veritabanı tabloları oluşturuldu
- [ ] Composer bağımlılıkları kuruldu
- [ ] `logs/` klasörü yazılabilir
- [ ] `temp/` klasörü yazılabilir
- [ ] Task Scheduler kuruldu
- [ ] Web arayüzü açılıyor
- [ ] Login çalışıyor
- [ ] SSH bağlantısı çalışıyor

---

## 🔧 Gereksinimler

### Minimum:
```
✅ PHP 5.4+
✅ MySQL 5.5+
✅ Apache/Nginx
✅ 2 GB RAM
✅ 10 GB Disk
```

### PHP Extensions:
```
✅ mysqli / pdo_mysql
✅ json
✅ mbstring
✅ curl
❌ zip (GEREKMEZ - SimpleXLSX kullanılıyor)
```

### Harici Araçlar:
```
✅ PuTTY (plink.exe, pscp.exe) - tools/ klasöründe
✅ Composer - Otomatik indirilir
```

---

## 🐛 Sorun mu Yaşıyorsun?

### Veritabanı Bağlanamıyor

```bash
# Test et
mysql -u astera -p -h 192.168.181.16 asteriskcdrdb
```

**Çözüm:**
- Firewall'u kontrol et (3306 portu)
- MySQL remote erişim açık mı?

### Web Arayüzü Açılmıyor

```bash
# PHP hatalarını aç
# php.ini
display_errors = On
error_reporting = E_ALL
```

**Çözüm:**
- Apache/Nginx çalışıyor mu?
- `config.php` doğru mu?
- Log dosyalarını kontrol et

### Excel Import Çalışmıyor

**Çözüm:**
- `SimpleXLSX.php` var mı?
- `vendor/autoload.php` var mı?
- `php composer.phar install` çalıştır

### Task Scheduler Çalışmıyor

```cmd
# Test et
php retry_scheduler.php
```

**Çözüm:**
- Administrator yetkisi var mı?
- PHP yolu doğru mu?
- Task durumunu kontrol et:
  ```cmd
  schtasks /Query /TN "OtomatikArama_Retry" /V
  ```

---

## 📚 Dokümantasyon

**Detaylı Kılavuzlar:**
- 📖 [KURULUM_KILAVUZU.md](KURULUM_KILAVUZU.md) - Adım adım kurulum
- ✅ [KURULUM_CHECKLIST.md](KURULUM_CHECKLIST.md) - Kontrol listesi
- 📘 [KULLANIM_KILAVUZU.md](KULLANIM_KILAVUZU.md) - Kullanım kılavuzu
- 🔧 [ARAMA_YONETIMI_KULLANIM.md](ARAMA_YONETIMI_KULLANIM.md) - Arama yönetimi

**Hızlı Başlangıç:**
- 🚀 [HIZLI_BASLANGIC.md](HIZLI_BASLANGIC.md)
- ⚡ [HIZLI_KURULUM.bat](HIZLI_KURULUM.bat) - Otomatik kurulum

**Teknik:**
- 🛠️ [README_MYSQL55.md](README_MYSQL55.md) - MySQL 5.5 uyumluluk
- 🔄 [TEKRAR_ARAMA_SISTEMI.md](TEKRAR_ARAMA_SISTEMI.md) - Retry mantığı

---

## 💡 İpuçları

### 1️⃣ İlk Kullanım

```
1. Proje oluştur → "10 Kasım"
2. Şablon oluştur → "Borç Bildirimi"
3. Excel import et → ornek_import1.xlsx
4. Arama başlat → START (1 arama)
5. Log kontrol et → Asterisk logs
```

### 2️⃣ Yedekleme

```bash
# Veritabanı yedekle
mysqldump -u astera -p -h 192.168.181.16 asteriskcdrdb > backup.sql

# Dosyaları yedekle
xcopy /E /I D:\xampp\htdocs\OtomatikArama D:\Backup\OtomatikArama\
```

### 3️⃣ Performans

```ini
# php.ini
memory_limit = 256M
max_execution_time = 300
post_max_size = 50M
upload_max_filesize = 50M
```

---

## 📞 Destek

**Sorun yaşıyorsanız:**

1. 📋 Log dosyalarını kontrol edin (`logs/`)
2. 🧪 Test scriptlerini çalıştırın
3. 📝 Hata mesajlarını kaydedin
4. 📖 Dokümantasyonu okuyun

---

## 🎉 Başarıyla Kuruldu!

Sistem çalışıyorsa:

✅ **Tebrikler!** Sistem kullanıma hazır.

**Artık yapabilecekleriniz:**
- 📁 Proje oluştur
- 📝 Şablon oluştur
- 📊 Excel import et
- 📞 Otomatik arama başlat
- 📈 Rapor al

---

**Versiyon:** 1.0.0  
**Son Güncelleme:** 28 Ocak 2026
