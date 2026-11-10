# ✅ Kurulum Kontrol Listesi

Yeni sunucuya taşırken bu listeyi takip edin.

---

## 📦 Dosya Hazırlığı

- [ ] OtomatikArama klasörünü yeni sunucuya kopyala
- [ ] `logs/` klasörünün yazma izni var mı?
- [ ] `temp/` klasörünün yazma izni var mı?
- [ ] `tools/plink.exe` ve `tools/pscp.exe` var mı?

---

## ⚙️ Yapılandırma

- [ ] `config.php` dosyasını düzenle:
  - [ ] `$dbHost` (Asterisk IP)
  - [ ] `$dbUser` ve `$dbPass`
  - [ ] `ASTERISK_SSH_HOST`
  - [ ] `ASTERISK_SSH_PORT`
  - [ ] `ASTERISK_SSH_USER`
  - [ ] `ASTERISK_SSH_PASS`
  - [ ] `CALLER_ID`

---

## 🗄️ Veritabanı

- [ ] MySQL bağlantısı çalışıyor mu?
  ```bash
  mysql -u astera -p -h 192.168.181.16 asteriskcdrdb
  ```

- [ ] SQL scriptleri çalıştırıldı mı?
  - [ ] `database/schema.sql`
  - [ ] `database/add_retry_settings.sql`
  - [ ] `database/add_template_actions.sql`
  - [ ] `database/add_audio_file_fields.sql`
  - [ ] `database/add_concurrent_limit.sql`
  - [ ] `database/update_retry_to_minutes.sql`

- [ ] Tablolar oluştu mu?
  ```sql
  SHOW TABLES FROM asteriskcdrdb;
  -- CALLS, IMPORT_HISTORY, MESSAGE_TEMPLATES, PROJECTS
  ```

---

## 📚 Composer Bağımlılıkları

- [ ] Composer kurulu mu?
  ```bash
  php composer.phar --version
  ```

- [ ] Bağımlılıklar kuruldu mu?
  ```bash
  php composer.phar install
  ```

- [ ] `vendor/` klasörü oluştu mu?

---

## 🔐 SSH Bağlantısı

- [ ] SSH bağlantısı test edildi mi?
  ```cmd
  tools\plink.exe -ssh root@192.168.181.16 -P 45555 -pw SIFRENIZ "echo OK"
  ```

- [ ] SSH key cache temizlendi mi?

- [ ] Asterisk erişimi çalışıyor mu?
  ```bash
  asterisk -rx "core show version"
  ```

---

## 🎵 Asterisk Dosyaları

- [ ] Ses dosyaları yüklendi mi?
  ```bash
  upload_turkish_prompts.bat
  ```

- [ ] Asterisk'te ses dosyaları var mı?
  ```bash
  ls /var/lib/asterisk/sounds/otomatik/
  ```

- [ ] AGI scriptleri yüklendi mi?
  ```bash
  ls /var/lib/asterisk/agi-bin/*.php
  ```

---

## ⏰ Task Scheduler

- [ ] Retry Scheduler kuruldu mu?
  ```cmd
  schtasks /Query /TN "OtomatikArama_Retry"
  ```

- [ ] Cleanup Scheduler kuruldu mu?
  ```cmd
  schtasks /Query /TN "OtomatikArama_Cleanup"
  ```

- [ ] Task'lar çalışıyor mu?
  ```cmd
  # Manuel test
  php retry_scheduler.php
  php cleanup_stuck_calls.php
  ```

---

## 🧪 Testler

- [ ] Veritabanı testi
  ```bash
  php test_database.php
  ```
  **Beklenen:** ✅ Tüm tablolar var

- [ ] Web arayüzü açılıyor mu?
  ```
  http://SUNUCU_IP/OtomatikArama/
  ```

- [ ] Login çalışıyor mu?
  - Kullanıcı: `superadmin`
  - Şifre: `Astera2020`

- [ ] Menü sayfası görünüyor mu?
  - 📞 Arama Yönetimi
  - 📁 Projeler
  - 📝 Şablonlar

---

## 🎯 İlk Kullanım

- [ ] Proje oluştur
  ```
  Projeler → Yeni Proje → 10 Kasım
  ```

- [ ] Şablon oluştur
  ```
  Şablonlar → Yeni Şablon → Test Şablonu
  ```

- [ ] Excel import test et
  ```
  Arama Yönetimi → Import → ornek_import1.xlsx
  ```

- [ ] Arama başlat
  ```
  START → 1 arama
  ```

- [ ] Log kontrol et
  ```bash
  tail -f /var/log/asterisk/full
  ```

---

## 🔒 Güvenlik

- [ ] `config.php` izinleri kısıtlandı mı?
  ```bash
  chmod 600 config.php
  ```

- [ ] `.htaccess` oluşturuldu mu?

- [ ] Veritabanı kullanıcısı sadece gerekli izinlere sahip mi?

- [ ] Firewall kuralları ayarlandı mı?
  - [ ] 3306 (MySQL) - Sadece web server IP
  - [ ] 45555 (SSH) - Sadece web server IP
  - [ ] 80/443 (HTTP/HTTPS) - Gerekli IP'ler

---

## 📊 Performans

- [ ] PHP memory_limit yeterli mi?
  ```ini
  memory_limit = 256M
  ```

- [ ] PHP max_execution_time yeterli mi?
  ```ini
  max_execution_time = 300
  ```

- [ ] MySQL bağlantı limiti yeterli mi?
  ```sql
  SHOW VARIABLES LIKE 'max_connections';
  ```

---

## 🐛 Sorun Giderme

- [ ] Hata logları kontrol edildi mi?
  - [ ] `logs/cleanup_*.log`
  - [ ] `/var/log/asterisk/full`
  - [ ] Apache/Nginx error log

- [ ] PHP hataları görünüyor mu?
  ```ini
  display_errors = On
  error_reporting = E_ALL
  ```

---

## 📝 Notlar

```
Kurulum Tarihi: _______________
Sunucu IP: _______________
Asterisk IP: _______________
Sorunlar: 
______________________________
______________________________
______________________________
```

---

## ✅ Kurulum Tamamlandı!

Tüm maddeler işaretlendiyse sistem kullanıma hazır! 🎉

**Sonraki Adımlar:**
1. İlk projeyi oluştur
2. İlk şablonu oluştur
3. Test araması yap
4. Kullanıcıları eğit
5. Dokümantasyonu oku

---

**Destek:** Log dosyalarını kontrol edin, hata mesajlarını kaydedin.
