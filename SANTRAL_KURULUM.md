# SANTRAL KURULUM

## 1. Dosyaları Yükle

Asterisk sunucusuna bağlan ve dosyaları yükle:

```bash
# SSH ile bağlan
ssh root@213.14.87.158 -p 45555

# Klasör oluştur
mkdir -p /var/www/html

# Dosyaları yükle (başka bir terminalde, yerel bilgisayarınızdan)
```

Windows'tan:
```powershell
cd d:\xampp\htdocs\WindowsOtomatikAramason97
pscp -P 45555 asterisk_sip_updater.php root@213.14.87.158:/var/www/html/
pscp -P 45555 .env root@213.14.87.158:/var/www/html/
```

## 2. İzinleri Ayarla

```bash
chmod 644 /var/www/html/asterisk_sip_updater.php
chmod 600 /var/www/html/.env
chown asterisk:asterisk /var/www/html/asterisk_sip_updater.php
chown asterisk:asterisk /var/www/html/.env
```

## 3. Test Et

```bash
php /var/www/html/asterisk_sip_updater.php
```

Çıktı:
```
=== ASTERISK SIP UPDATER ===
Time: 2026-02-09 11:20:00

✅ Call 26833: SipCode=486, Status=failed, Durum=Meşgul

Summary:
  Updated: 1
  Skipped: 0
  Errors: 0
========================
```

## 4. Crontab'a Ekle (5 Dakikada Bir)

```bash
crontab -e
```

Ekle:
```
*/5 * * * * /usr/bin/php /var/www/html/asterisk_sip_updater.php >> /var/log/asterisk/sip_updater.log 2>&1
```

Kaydet ve çık (`:wq`)

## 5. Crontab Kontrol

```bash
crontab -l
```

## 6. Log Kontrol

```bash
tail -f /var/log/asterisk/sip_updater.log
```

## ÖNEMLİ NOTLAR

- Script **5 dakikada bir** çalışır
- Son **100 call file**'ı kontrol eder
- **Gerçek SIP kodlarını** yakalar (486 Busy, 480 NoAnswer, vb.)
- Database'i **otomatik günceller**
- **Locked=0** yapar ve retry zamanını ayarlar

## SIP Kodları

- **200**: Cevaplandı (AGI tarafından halledilir)
- **480**: Cevapsız
- **486**: Meşgul
- **487**: İptal Edildi
- **503**: Ulaşılamıyor
- **603**: Reddedildi
