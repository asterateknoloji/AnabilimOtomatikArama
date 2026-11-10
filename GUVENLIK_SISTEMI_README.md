# 🔐 Güvenli Konfigürasyon ve Yetkilendirme Sistemi

## 📋 Özellikler

- ✅ **Şifreli Konfigürasyon**: Tüm hassas veriler AES-256-CBC ile şifrelenir
- ✅ **Rol Tabanlı Yetkilendirme**: Superadmin, Admin, Operator, Viewer rolleri
- ✅ **Detaylı İzinler**: Modül bazında granüler izin sistemi
- ✅ **Aktivite Loglama**: Tüm kullanıcı işlemleri loglanır
- ✅ **Oturum Yönetimi**: Güvenli session yönetimi
- ✅ **MySQL 5.5+ Uyumlu**: Eski sistemlerde çalışır

---

## 🚀 Kurulum

### 1️⃣ Veritabanı Tablolarını Oluştur

```bash
mysql -u astera -p ANABILIM < sql/create_secure_system.sql
```

### 2️⃣ Config.php Verilerini Migrate Et

```bash
php migrate_config_to_db.php
```

**Çıktı:**
```
✅ Başarılı: 17
❌ Başarısız: 0
📊 Toplam: 17
```

### 3️⃣ İlk Superadmin Kullanıcısı

Varsayılan olarak `users` tablosundaki `id=1` kullanıcısına `superadmin` rolü atanır.

---

## 📊 Veritabanı Tabloları

| Tablo | Açıklama |
|-------|----------|
| `SYSTEM_CONFIG` | Şifreli sistem ayarları |
| `ROLES` | Kullanıcı rolleri |
| `PERMISSIONS` | Sistem izinleri |
| `USER_ROLES` | Kullanıcı-Rol ilişkisi |
| `ROLE_PERMISSIONS` | Rol-İzin ilişkisi |
| `USER_ACTIVITY_LOG` | Kullanıcı aktivite logları |
| `USER_SESSIONS` | Oturum yönetimi |

---

## 🔑 Varsayılan Roller

### 1. Superadmin
- **Kod:** `superadmin`
- **Yetkiler:** Tüm izinler
- **Kullanım:** Sistem yöneticisi

### 2. Admin
- **Kod:** `admin`
- **Yetkiler:** Sistem ayarları ve kullanıcı silme hariç tüm izinler
- **Kullanım:** Departman yöneticisi

### 3. Operator
- **Kod:** `operator`
- **Yetkiler:** İçe aktarma, şablon ve rapor izinleri (silme hariç)
- **Kullanım:** Çağrı merkezi operatörü

### 4. Viewer
- **Kod:** `viewer`
- **Yetkiler:** Sadece görüntüleme ve rapor indirme
- **Kullanım:** Raporlama ekibi

---

## 🔐 İzin Grupları

### Import (İçe Aktarma)
- `import.view` - Görüntüleme
- `import.create` - Oluşturma
- `import.edit` - Düzenleme
- `import.delete` - Silme
- `import.control` - Başlatma/Durdurma
- `import.bulk` - Toplu arama

### Template (Şablon)
- `template.view`
- `template.create`
- `template.edit`
- `template.delete`

### Project (Proje)
- `project.view`
- `project.create`
- `project.edit`
- `project.delete`

### Report (Rapor)
- `report.view`
- `report.export`
- `report.detailed`

### User (Kullanıcı)
- `user.view`
- `user.create`
- `user.edit`
- `user.delete`
- `user.assign_role`

### System (Sistem)
- `config.view`
- `config.edit`
- `log.view`
- `workhours.manage`

---

## 💻 Kullanım Örnekleri

### 1. Config Manager

```php
<?php
require_once 'includes/ConfigManager.php';

$pdo = ConfigManager::createPDO();
$config = new ConfigManager($pdo);

// Değer al (şifre otomatik çözülür)
$dbHost = $config->get('db_host');
$sshPass = $config->get('ssh_pass');

// Değer kaydet (şifreli)
$config->set('new_key', 'secret_value', 'system', true, 'Açıklama', $userId);

// Grup bazında al
$sshConfigs = $config->getGroup('ssh');
// ['ssh_host' => '192.168.181.16', 'ssh_port' => '45555', ...]
```

### 2. Authentication

```php
<?php
require_once 'includes/Auth.php';

$auth = new Auth($pdo);

// Giriş
$user = $auth->login('username', 'password');
if ($user) {
    echo "Giriş başarılı!";
}

// Oturum kontrolü
if ($auth->isLoggedIn()) {
    $currentUser = $auth->getCurrentUser();
}

// Çıkış
$auth->logout();
```

### 3. Authorization

```php
<?php
// Rol kontrolü
if ($auth->hasRole('superadmin')) {
    echo "Superadmin!";
}

// İzin kontrolü
if ($auth->hasPermission('import.create')) {
    // İçe aktarma oluşturabilir
}

// Aktivite logla
$auth->logActivity(
    $userId,
    'create',
    'import',
    'Yeni içe aktarma oluşturuldu',
    $importId
);
```

### 4. Encryption

```php
<?php
require_once 'includes/Encryption.php';

$enc = new Encryption();

// Şifrele
$encrypted = $enc->encrypt('secret data');

// Çöz
$decrypted = $enc->decrypt($encrypted);

// Şifre hash
$hash = $enc->hashPassword('mypassword');

// Şifre doğrula
if ($enc->verifyPassword('mypassword', $hash)) {
    echo "Şifre doğru!";
}
```

---

## 🔒 Güvenlik Notları

### Şifreleme Anahtarı

**ÖNEMLİ:** Production'da `Encryption.php` içindeki varsayılan anahtarı değiştirin!

```php
// includes/Encryption.php
private function __construct($secretKey = null) {
    if ($secretKey === null) {
        // ÖNEMLİ: Bu anahtarı değiştirin!
        $secretKey = 'SİZİN_GİZLİ_ANAHTARINIZ_32BYTE';
    }
    // ...
}
```

### Session Timeout

Varsayılan: **1 saat**

```php
// includes/Auth.php
private $sessionTimeout = 3600; // 1 saat
```

### Şifrelenecek Veriler

`SYSTEM_CONFIG` tablosunda `is_encrypted = 1` olan tüm veriler şifrelenir:
- Veritabanı şifreleri
- SSH şifreleri
- AMI şifreleri
- Diğer hassas bilgiler

---

## 📈 Aktivite Logları

Tüm kullanıcı işlemleri `USER_ACTIVITY_LOG` tablosuna kaydedilir:

- **login/logout**: Giriş/çıkış
- **create**: Oluşturma
- **update**: Güncelleme
- **delete**: Silme
- **view**: Görüntüleme

**Örnek Sorgu:**

```sql
-- Son 100 işlem
SELECT 
    u.username,
    ual.action_type,
    ual.action_module,
    ual.action_description,
    ual.ip_address,
    ual.created_at
FROM USER_ACTIVITY_LOG ual
JOIN users u ON ual.user_id = u.id
ORDER BY ual.created_at DESC
LIMIT 100;
```

---

## 🎯 Sonraki Adımlar

1. ✅ Tabloları oluştur
2. ✅ Config verilerini migrate et
3. ⏳ Mevcut login sayfasını güncelle (Auth class kullan)
4. ⏳ Sayfalara izin kontrolü ekle
5. ⏳ Kullanıcı yönetim paneli oluştur
6. ⏳ Aktivite log görüntüleyici ekle

---

## 📝 Notlar

- MySQL 5.5+ uyumlu (Foreign key, InnoDB)
- PHP 5.4+ uyumlu (openssl extension gerekli)
- Şifreleme: AES-256-CBC
- Hash: SHA-256
- Session: Database tabanlı

---

## 🆘 Sorun Giderme

### Şifre Çözme Hatası

Şifreleme anahtarı değiştirilmişse eski veriler çözülemez!

**Çözüm:** Migration script'i tekrar çalıştırın.

### Session Timeout

Çok kısa timeout sorun yaratıyorsa:

```php
// Auth.php içinde artırın
private $sessionTimeout = 7200; // 2 saat
```

---

**🎉 Kurulum Tamamlandı!**
