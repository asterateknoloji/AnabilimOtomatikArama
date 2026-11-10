# Apache Yapılandırması - Gerçek IP'yi Kaydetme

## Sorun:
`localhost:8009` ile erişildiğinde PHP `127.0.0.1` veya `::1` görüyor.

## Çözüm 1: Network IP ile Erişim (ÖNERİLEN - YAPILDI)
```
✅ http://192.168.181.139:8009/OtomatikArama/
```
Bu durumda PHP direkt olarak gerçek IP'yi görür (`REMOTE_ADDR`).

---

## Çözüm 2: Apache mod_remoteip Modülü (İLERİ SEVİYE)

### Adım 1: httpd.conf'u Düzenle
Dosya: `d:\xampp\apache\conf\httpd.conf`

```apache
# mod_remoteip modülünü aktif et
LoadModule remoteip_module modules/mod_remoteip.so

# VirtualHost tanımına ekle (veya genel olarak)
<VirtualHost *:8009>
    ServerName localhost
    DocumentRoot "d:/xampp/htdocs"
    
    # Gerçek IP'yi X-Forwarded-For başlığından al
    RemoteIPHeader X-Forwarded-For
    
    # Güvenilir proxy IP'leri (localhost)
    RemoteIPInternalProxy 127.0.0.1
    RemoteIPInternalProxy ::1
    
    # Log formatını güncelle
    LogFormat "%a %l %u %t \"%r\" %>s %b" common_remoteip
    CustomLog "logs/access_log" common_remoteip
</VirtualHost>
```

### Adım 2: Apache'yi Yeniden Başlat
```cmd
d:\xampp\apache\bin\httpd.exe -k restart
```

### Adım 3: Test Et
```
http://localhost:8009/OtomatikArama/debug_ip.php
```

**Artık göreceksiniz:**
```
REMOTE_ADDR: 192.168.181.139 ✅
```

---

## Çözüm 3: Nginx Reverse Proxy (ÇOK İLERİ SEVİYE)

Nginx'i Apache önünde proxy olarak kullan:

```nginx
server {
    listen 8009;
    server_name localhost;
    
    location / {
        proxy_pass http://localhost:8010;  # Apache 8010'da çalışsın
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header Host $host;
    }
}
```

---

## ÖNERİ:
**Çözüm 1'i kullanın** (Network IP ile erişim). En basit ve en güvenilir yöntem.

```
✅ http://192.168.181.139:8009/OtomatikArama/
```

Bu durumda ekstra yapılandırma gerektirmez ve her zaman doğru IP'yi kaydeder.
