# MySQL 5.5 Uyumluluk Notları

## ✅ Yapılan Değişiklikler

### 1. Karakter Seti
- **Önceki:** `utf8mb4_unicode_ci`
- **Yeni:** `utf8_turkish_ci`
- **Neden:** MySQL 5.5'te utf8mb4 desteği sınırlıdır. utf8 Türkçe karakterler için yeterlidir.

### 2. Timestamp Alanları
- **Önceki:** `TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP`
- **Yeni:** `DATETIME DEFAULT NULL` + PHP'de `NOW()` kullanımı
- **Neden:** MySQL 5.5'te sadece bir TIMESTAMP alan otomatik güncellenebilir.

### 3. Foreign Key Kullanımı
- IMPORT_HISTORY tablosundaki FOREIGN KEY kaldırıldı
- **Neden:** MySQL 5.5'te bazı durumlarda sorun çıkarabilir
- **Alternatif:** Uygulama seviyesinde veri bütünlüğü kontrolü

### 4. JSON Veri Tipi
- **Yöntem:** LONGTEXT olarak saklanıyor
- **Kodlama:** PHP'de `json_encode()` ve `json_decode()`
- **Neden:** MySQL 5.5'te JSON data type yok (5.7.8'den sonra var)

### 5. ALTER TABLE Kontrolü
- Prepared statement ile dinamik kontrol eklendi
- Mevcut kolonlar tekrar eklenmez
- Mevcut indexler tekrar oluşturulmaz

## 📋 Sistem Gereksinimleri

- **MySQL:** 5.5.x veya üzeri
- **PHP:** 5.6 veya üzeri (JSON desteği için)
- **InnoDB:** Engine desteği (tablo kilitlemesi için)
- **Karakter Seti:** utf8_turkish_ci

## 🔧 Kurulum

### 1. Veritabanı Kurulumu
```bash
setup_database.bat
```

Script otomatik olarak:
- Bağlantı testi yapar
- Tabloları oluşturur
- Kolonları kontrol eder
- Varsayılan kayıtları ekler

### 2. Manuel Kurulum (Gerekirse)

Eğer script çalışmazsa:

1. phpMyAdmin'e girin: `http://192.168.181.16/phpmyadmin`
2. ANABILIM veritabanını seçin
3. SQL sekmesine gidin
4. `database/schema.sql` dosyasını açın
5. İçeriği kopyalayıp SQL alanına yapıştırın
6. "Çalıştır" butonuna tıklayın

## ⚠️ Bilinen Sınırlamalar

### 1. Otomatik Tarih Güncelleme
MySQL 5.5'te UpdatedDate otomatik güncellenmez. PHP kodunda `NOW()` ile manuel güncellenir.

**Örnek:**
```php
UPDATE PROJECTS SET Name = ?, UpdatedDate = NOW() WHERE id = ?
```

### 2. JSON Sorgulama
LONGTEXT olarak saklanan JSON veriler üzerinde direkt SQL sorgusu yapılamaz.

**Çözüm:** PHP'de json_decode() ile işleyin:
```php
$template = json_decode($row['TemplateContent'], true);
```

### 3. Emoji Desteği
utf8 (3-byte) kullanıldığı için emoji desteği yoktur.

**Çözüm:** Emoji kullanmayın veya MySQL 5.6+ sürümüne yükseltin.

## 🚀 Performans İpuçları

### 1. Index Kullanımı
Tüm önemli sorgulama alanlarına index eklendi:
- PROJECTS: Active, CreatedDate
- MESSAGE_TEMPLATES: Active
- IMPORT_HISTORY: ProjectId, Status, ScheduledTime
- CALLS: ProjectId, ImportId

### 2. Query Optimizasyonu
```sql
-- İyi
SELECT * FROM CALLS WHERE ProjectId = 1 AND Status = 1;

-- Kötü (Slow query)
SELECT * FROM CALLS WHERE YEAR(RecordDate) = 2026;
```

### 3. EXPLAIN Kullanımı
Yavaş sorguları analiz edin:
```sql
EXPLAIN SELECT * FROM IMPORT_HISTORY WHERE ProjectId = 1;
```

## 📊 Test Sorguları

### Kurulumu Kontrol Et
```sql
-- Tabloları kontrol et
SHOW TABLES FROM ANABILIM LIKE '%PROJECT%';
SHOW TABLES FROM ANABILIM LIKE '%TEMPLATE%';
SHOW TABLES FROM ANABILIM LIKE '%IMPORT%';

-- Kolonları kontrol et
DESCRIBE ANABILIM.CALLS;

-- Varsayılan kayıtları kontrol et
SELECT * FROM ANABILIM.PROJECTS;
SELECT * FROM ANABILIM.MESSAGE_TEMPLATES;
```

### Karakter Seti Kontrolü
```sql
-- Tablo karakter setlerini göster
SELECT 
    TABLE_NAME,
    TABLE_COLLATION
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = 'ANABILIM'
AND TABLE_NAME IN ('PROJECTS', 'MESSAGE_TEMPLATES', 'IMPORT_HISTORY');
```

## 🆘 Sorun Giderme

### Hata: "Table already exists"
**Çözüm:** `CREATE TABLE IF NOT EXISTS` kullanılıyor, sorun olmamalı. Eğer hata alırsanız:
```sql
DROP TABLE IF EXISTS ANABILIM.PROJECTS;
DROP TABLE IF EXISTS ANABILIM.MESSAGE_TEMPLATES;
DROP TABLE IF EXISTS ANABILIM.IMPORT_HISTORY;
```
Sonra tekrar çalıştırın.

### Hata: "Column already exists"
**Çözüm:** Script otomatik kontrol ediyor. Eğer sorun devam ederse:
```sql
ALTER TABLE ANABILIM.CALLS DROP COLUMN ProjectId;
ALTER TABLE ANABILIM.CALLS DROP COLUMN ImportId;
```
Sonra tekrar çalıştırın.

### Hata: "Access denied"
**Çözüm:** MySQL kullanıcı izinlerini kontrol edin:
```sql
GRANT ALL PRIVILEGES ON ANABILIM.* TO 'astera'@'%';
FLUSH PRIVILEGES;
```

### Türkçe Karakter Sorunu
**Çözüm:** Bağlantıda charset belirtin:
```php
$pdo = new PDO(
    "mysql:host=192.168.181.16;dbname=ANABILIM;charset=utf8",
    "astera",
    "astera2012"
);
```

## 📝 Versiyon Notları

### v1.0 (MySQL 5.5 Uyumlu)
- utf8_turkish_ci karakter seti
- DATETIME alanları
- LONGTEXT ile JSON desteği
- Dinamik ALTER TABLE kontrolü
- InnoDB engine
- Index optimizasyonu

## 🔄 Gelecek Güncellemeler

MySQL 5.6+ veya 5.7+ sürümüne geçerseniz:
- utf8mb4 karakterlere geçiş (emoji desteği)
- JSON data type kullanımı
- Generated columns (virtual columns)
- Full-text search
- Spatial data types

## 📞 Destek

Sorun yaşarsanız:
1. `setup_database.bat` çıktısını kontrol edin
2. phpMyAdmin'de SQL sorguları manuel çalıştırın
3. MySQL error log'larını inceleyin: `/var/log/mysql/error.log`
4. PHP error log'larını inceleyin: `D:\xampp\apache\logs\error.log`
