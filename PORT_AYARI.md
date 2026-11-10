# 🔌 APACHE PORT AYARI

## ℹ️ PORT KULLANIMI

Dokümanlarda **8009 portu örnek olarak** verilmiştir.

**Sizin Apache portunuzu kullanın:**

---

## 🔍 MEVCUT PORT KONTROLÜ

### Yöntem 1: Apache Config
```
D:\xampp\apache\conf\httpd.conf
```

Arayın:
```apache
Listen 80        # Standart HTTP
Listen 8080      # Alternatif
Listen 8009      # Örnek
```

### Yöntem 2: PowerShell
```powershell
# Apache'nin hangi portta çalıştığını bul
netstat -ano | findstr "httpd"
```

### Yöntem 3: XAMPP Control Panel
```
XAMPP Control Panel → Apache → Config → httpd.conf
Listen satırına bakın
```

---

## 📝 URL FORMATINIZ

Eğer Apache **port 80**'de çalışıyorsa:
```
http://192.168.150.11/WindowsOtomatikAramason97/login.php
```

Eğer **başka bir port** (örn: 8080) kullanıyorsanız:
```
http://192.168.150.11:8080/WindowsOtomatikAramason97/login.php
```

---

## ✅ DOĞRU URL ŞABLONU

```
http://[IP_ADRESİ]:[APACHE_PORT]/WindowsOtomatikAramason97/login.php
```

**Örnekler:**
- `http://192.168.150.11/WindowsOtomatikAramason97/login.php` (Port 80)
- `http://192.168.150.11:8080/WindowsOtomatikAramason97/login.php` (Port 8080)
- `http://localhost/WindowsOtomatikAramason97/login.php` (Lokal)
- `http://localhost:8080/WindowsOtomatikAramason97/login.php` (Lokal + 8080)

---

## 🔥 FIREWALL KURALI (Opsiyonel)

Eğer uzaktan erişim gerekiyorsa:

```powershell
# Port 80 için
New-NetFirewallRule -DisplayName "XAMPP Apache 80" -Direction Inbound -LocalPort 80 -Protocol TCP -Action Allow

# Veya port 8080 için
New-NetFirewallRule -DisplayName "XAMPP Apache 8080" -Direction Inbound -LocalPort 8080 -Protocol TCP -Action Allow
```

---

## 💡 NOT

**Uygulama kendi içinde port kullanmıyor.**

Sadece web tarayıcısından erişirken URL'de port belirtmeniz gerekiyor.

**Tüm işlevler port'tan bağımsız çalışır:**
- ✅ MySQL bağlantısı (213.14.87.158:3306)
- ✅ SSH bağlantısı (213.14.87.158:45555)
- ✅ Task Scheduler görevleri
- ✅ TTS ses üretimi
- ✅ Arama yönetimi

---

**Dokümanlardaki 8009 referanslarını kendi portunuzla değiştirin!**
