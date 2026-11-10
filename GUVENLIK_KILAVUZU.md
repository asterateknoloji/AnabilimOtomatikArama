# 🔒 Güvenlik Kılavuzu

Bu kılavuz, Otomatik Arama Sistemi'nin güvenli bir şekilde yapılandırılması için adımları içerir.

---

## 🎯 Hızlı Güvenlik Kurulumu

### 1️⃣ .env Dosyası Oluştur

```bash
# .env.example dosyasını kopyala
copy .env.example .env

# Düzenle (şifreleri değiştir!)
notepad .env
```

### 2️⃣ Güvenli Config Kullan

**Mevcut `config.php` yerine `config.secure.php` kullan:**

```php
// Tüm PHP dosyalarında değiştir:
// require_once 'config.php';
require_once 'config.secure.php';
```

### 3️⃣ Dosya İzinlerini Ayarla

```bash
# Windows (ICACLS)
icacls .env /inheritance:r /grant:r "%USERNAME%:F"

# Linux
chmod 600 .env
chmod 600 config.php
chmod 600 config.secure.php
```

### 4️⃣ .htaccess Kontrolü

`.htaccess` dosyası otomatik oluşturuldu ve:
- ✅ `.env` dosyasına erişimi engelliyor
- ✅ `config.php` dosyasına erişimi engelliyor
- ✅ Log dosyalarına erişimi engelliyor
- ✅ Dizin listelemeyi kapatıyor

---

## 🔐 Güvenlik Katmanları

### Katman 1: Ortam Değişkenleri (.env)

**ÖNCESİ (Güvensiz):**
```php
// config.php
$dbPass = 'astera2012';  // ❌ Şifre açıkta!
```

**SONRASI (Güvenli):**
```php
// config.secure.php
$dbPass = EnvLoader::get('DB_PASS');  // ✅ Şifre .env'de
```

```bash
# .env (GIT'e eklenmez!)
DB_PASS=astera2012
```

### Katman 2: Dosya İzinleri

```bash
# Sadece owner okuyabilir
chmod 600 .env
chmod 600 config.php
chmod 600 config.secure.php

# Dizin izinleri
chmod 755 includes/
chmod 700 logs/
chmod 700 temp/
```

### Katman 3: Web Erişim Engeli (.htaccess)

```apache
<Files ".env">
    Order Allow,Deny
    Deny from all
</Files>
```

### Katman 4: Git İgnore

```bash
# .gitignore
.env          # ✅ GIT'e eklenmez
config.php    # ✅ GIT'e eklenmez (opsiyonel)
logs/*.log    # ✅ GIT'e eklenmez
```

---

## 🛡️ Şifre Şifreleme (İleri Düzey)

### Basit XOR Şifreleme

```php
// includes/Encryptor.php
class Encryptor
{
    private static $key = 'RANDOM_SECRET_KEY_123456789';
    
    public static function encrypt($data)
    {
        $result = '';
        $keyLen = strlen(self::$key);
        
        for ($i = 0; $i < strlen($data); $i++) {
            $result .= $data[$i] ^ self::$key[$i % $keyLen];
        }
        
        return base64_encode($result);
    }
    
    public static function decrypt($data)
    {
        $data = base64_decode($data);
        $result = '';
        $keyLen = strlen(self::$key);
        
        for ($i = 0; $i < strlen($data); $i++) {
            $result .= $data[$i] ^ self::$key[$i % $keyLen];
        }
        
        return $result;
    }
}
```

**Kullanım:**
```php
// Şifrelenmiş .env
DB_PASS=RW5jcnlwdGVkUGFzc3dvcmQ=

// config.secure.php
$dbPass = Encryptor::decrypt(EnvLoader::get('DB_PASS'));
```

---

## 🔑 Güvenli Şifre Üretimi

### Güçlü Şifre Oluşturucu

```php
// generate_password.php
function generateSecurePassword($length = 16)
{
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
    $password = '';
    
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    
    return $password;
}

echo "Yeni Güvenli Şifre: " . generateSecurePassword(20);
```

---

## 🌐 Web Server Güvenliği

### Apache (httpd.conf)

```apache
# Document Root dışında tut
<Directory "D:\xampp\htdocs\OtomatikArama">
    AllowOverride All
    Require all granted
</Directory>

# .env ve config dosyalarını engelle
<FilesMatch "^\.(env|htaccess)$">
    Require all denied
</FilesMatch>

<FilesMatch "^(config\.php|config\.secure\.php)$">
    Require all denied
</FilesMatch>
```

### Nginx (nginx.conf)

```nginx
location ~ /\.env {
    deny all;
    return 404;
}

location ~ /config\.php$ {
    deny all;
    return 404;
}

location ~ /config\.secure\.php$ {
    deny all;
    return 404;
}

location ~ /\.git {
    deny all;
    return 404;
}
```

---

## 🔒 SSL/TLS (HTTPS)

### Let's Encrypt (Ücretsiz SSL)

```bash
# Certbot kur (Windows)
choco install certbot

# SSL sertifikası al
certbot certonly --standalone -d arama.sirketim.com

# Apache'ye ekle
<VirtualHost *:443>
    ServerName arama.sirketim.com
    DocumentRoot "D:\xampp\htdocs\OtomatikArama"
    
    SSLEngine on
    SSLCertificateFile "C:\Certbot\live\arama.sirketim.com\fullchain.pem"
    SSLCertificateKeyFile "C:\Certbot\live\arama.sirketim.com\privkey.pem"
</VirtualHost>
```

---

## 🛡️ Firewall Kuralları

### Windows Firewall

```cmd
# MySQL (sadece Asterisk IP'den)
netsh advfirewall firewall add rule name="MySQL-Asterisk" dir=in action=allow protocol=TCP localport=3306 remoteip=192.168.181.16

# Web Server (güvenilir IP'lerden)
netsh advfirewall firewall add rule name="HTTP-Guvenilir" dir=in action=allow protocol=TCP localport=80 remoteip=192.168.1.0/24

# SSH (sadece yönetici IP'den)
netsh advfirewall firewall add rule name="SSH-Admin" dir=in action=allow protocol=TCP localport=22 remoteip=192.168.1.100
```

### Linux iptables

```bash
# MySQL (sadece local)
iptables -A INPUT -p tcp --dport 3306 -s 192.168.181.16 -j ACCEPT
iptables -A INPUT -p tcp --dport 3306 -j DROP

# Web Server
iptables -A INPUT -p tcp --dport 80 -j ACCEPT
iptables -A INPUT -p tcp --dport 443 -j ACCEPT

# SSH (sadece yönetici)
iptables -A INPUT -p tcp --dport 22 -s 192.168.1.100 -j ACCEPT
iptables -A INPUT -p tcp --dport 22 -j DROP
```

---

## 📊 Güvenlik Kontrol Listesi

### Zorunlu ✅

- [ ] `.env` dosyası oluşturuldu
- [ ] `config.secure.php` kullanılıyor
- [ ] `.htaccess` dosyası var
- [ ] `.gitignore` dosyası var
- [ ] `.env` dosyası GIT'e eklenmedi
- [ ] Dosya izinleri ayarlandı (600)
- [ ] Varsayılan şifreler değiştirildi

### Önerilen 🔒

- [ ] SSL/TLS (HTTPS) kullanılıyor
- [ ] Firewall kuralları ayarlandı
- [ ] MySQL remote erişim kısıtlı
- [ ] SSH key authentication kullanılıyor
- [ ] Log dosyaları düzenli siliniyor
- [ ] Yedekler güvenli yerde tutuluyor

### İleri Düzey 🛡️

- [ ] Şifre şifreleme kullanılıyor
- [ ] 2FA (Two-Factor Authentication)
- [ ] IP whitelist
- [ ] Rate limiting
- [ ] Intrusion detection
- [ ] Regular security audits

---

## 🚨 Güvenlik İhlali Durumunda

### Acil Durum Adımları

1. **Sistemi Kapat**
   ```bash
   # Apache'yi durdur
   net stop Apache2.4
   ```

2. **Şifreleri Değiştir**
   ```bash
   # .env dosyasını düzenle
   notepad .env
   
   # MySQL şifresini değiştir
   ALTER USER 'astera'@'%' IDENTIFIED BY 'YeniSifre123!';
   FLUSH PRIVILEGES;
   ```

3. **Logları Kontrol Et**
   ```bash
   # Apache access log
   type C:\xampp\apache\logs\access.log | findstr "config.php"
   
   # Apache error log
   type C:\xampp\apache\logs\error.log
   ```

4. **Yedekten Geri Yükle**
   ```bash
   # Temiz yedeği kopyala
   xcopy D:\Backup\OtomatikArama D:\xampp\htdocs\OtomatikArama /E /I /Y
   ```

---

## 📝 Güvenlik Best Practices

### 1. Şifre Politikası

```
✅ Minimum 12 karakter
✅ Büyük/küçük harf
✅ Rakam ve özel karakter
✅ Her 90 günde değiştir
❌ Yaygın kelimeler kullanma
❌ Kişisel bilgiler kullanma
```

### 2. Erişim Kontrolü

```
✅ En az yetki prensibi
✅ Rol bazlı erişim
✅ Oturum timeout (30 dk)
✅ Başarısız giriş limiti (5)
✅ IP bazlı kısıtlama
```

### 3. Log Yönetimi

```php
// Güvenlik olaylarını logla
function logSecurityEvent($event, $severity = 'INFO')
{
    $log = date('Y-m-d H:i:s') . " [{$severity}] {$event}\n";
    file_put_contents('logs/security.log', $log, FILE_APPEND);
}

// Kullanım
logSecurityEvent("Başarısız login: " . $_SERVER['REMOTE_ADDR'], 'WARNING');
```

### 4. Düzenli Güncellemeler

```bash
# PHP bağımlılıklarını güncelle
composer update

# Sistem güncellemelerini kontrol et
# Asterisk, PHP, MySQL, Apache
```

---

## 🔍 Güvenlik Testi

### Temel Testler

```bash
# 1. .env dosyası erişilebilir mi?
curl http://localhost/OtomatikArama/.env
# Beklenen: 403 Forbidden

# 2. config.php erişilebilir mi?
curl http://localhost/OtomatikArama/config.php
# Beklenen: 403 Forbidden

# 3. Dizin listeleme kapalı mı?
curl http://localhost/OtomatikArama/logs/
# Beklenen: 403 Forbidden

# 4. SQL Injection testi
curl "http://localhost/OtomatikArama/login.php?user=admin'--"
# Beklenen: Hata değil, güvenli işleme
```

---

## 📚 Ek Kaynaklar

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [PHP Security Guide](https://phpsecurity.readthedocs.io/)
- [MySQL Security](https://dev.mysql.com/doc/refman/5.7/en/security.html)

---

**Güvenlik = Sürekli İyileştirme!**

Düzenli güvenlik kontrolleri yapın ve güncel kalın! 🔒
