# 🗄️ Veritabanı Bilgileri ve Backup Kılavuzu

Bu dosya sistemin kullandığı tüm veritabanlarını ve tablolarını içerir.

---

## 📊 Kullanılan Veritabanları

### 1️⃣ asteriskcdrdb (Ana Veritabanı)

**Bağlantı Bilgileri:**
```
Host: 192.168.181.16
Database: asteriskcdrdb
User: astera
Pass: (Şifrelenmiş - .env dosyasında)
```

**Tablolar:**

#### CALLS (Arama Kayıtları)
```sql
-- Tüm arama kayıtları burada
SELECT COUNT(*) FROM asteriskcdrdb.CALLS;

-- Sütunlar:
id              INT (Primary Key, Auto Increment)
PhoneNumber     VARCHAR(20)     -- Telefon numarası
Adi             VARCHAR(100)    -- Ad Soyad
Tutar           DECIMAL(10,2)   -- Tutar
Metin1          TEXT            -- Ekstra metin 1
Metin2          TEXT            -- Ekstra metin 2
Metin3          TEXT            -- Ekstra metin 3
ProjectId       INT             -- Proje ID
ImportId        INT             -- Import ID
TemplateId      INT             -- Şablon ID
Status          INT             -- Durum (1=Bekliyor, 2=Arandı)
SipCode         VARCHAR(10)     -- SIP durum kodu (200, 404, etc.)
CallCount       INT             -- Aranma sayısı
TryCallCount    INT             -- Max deneme sayısı
Locked          INT             -- Kilit (0/1)
CallTime        DATETIME        -- İlk arama zamanı
LastCallTime    DATETIME        -- Son/Sonraki arama zamanı
EndCallTime     DATETIME        -- Bitiş zamanı
RecordDate      DATETIME        -- Kayıt tarihi
Aramaadi        VARCHAR(100)    -- Arama adı
Aktif           INT             -- Aktif (0/1)
tus1            VARCHAR(10)     -- Tuş 1 bilgisi
tus2            VARCHAR(10)     -- Tuş 2 bilgisi
tus3            VARCHAR(10)     -- Tuş 3 bilgisi (kullanılmıyor)
Durum           VARCHAR(255)    -- Detaylı durum bilgisi
```

#### IMPORT_HISTORY (İçe Aktarma Geçmişi)
```sql
-- Import kayıtları
SELECT COUNT(*) FROM asteriskcdrdb.IMPORT_HISTORY;

-- Sütunlar:
id                      INT (Primary Key, Auto Increment)
ProjectId               INT             -- Proje ID
FileName                VARCHAR(255)    -- Dosya adı (formatlanmış)
OriginalFileName        VARCHAR(255)    -- Orijinal dosya adı
TemplateId              INT             -- Şablon ID
ScheduledTime           DATETIME        -- Zamanlanmış saat
Status                  VARCHAR(50)     -- Durum (pending, running, completed)
ImportDate              DATETIME        -- Import tarihi
RecordCount             INT             -- Kayıt sayısı
RetryCount              INT             -- Tekrar sayısı
RetryIntervalMinutes    INT             -- Tekrar aralığı (dakika)
MaxConcurrentCalls      INT             -- Eşzamanlı arama limiti
```

#### MESSAGE_TEMPLATES (Mesaj Şablonları)
```sql
-- Mesaj şablonları
SELECT COUNT(*) FROM asteriskcdrdb.MESSAGE_TEMPLATES;

-- Sütunlar:
id                      INT (Primary Key, Auto Increment)
TemplateName            VARCHAR(100)    -- Şablon adı
Description             TEXT            -- Açıklama
TemplateContent         TEXT            -- İçerik (JSON)
Key1Action              VARCHAR(50)     -- 1 tuşu aksiyonu
Key1TransferNumber      VARCHAR(20)     -- 1 tuşu transfer numarası
Key1AudioFile           VARCHAR(255)    -- 1 tuşu ses dosyası
Key2Action              VARCHAR(50)     -- 2 tuşu aksiyonu
Key2TransferNumber      VARCHAR(20)     -- 2 tuşu transfer numarası
Key2AudioFile           VARCHAR(255)    -- 2 tuşu ses dosyası
InvalidKeyAction        VARCHAR(50)     -- Geçersiz tuş aksiyonu
InvalidKeyAudioFile     VARCHAR(255)    -- Geçersiz tuş ses dosyası
TimeoutAction           VARCHAR(50)     -- Timeout aksiyonu
TimeoutAudioFile        VARCHAR(255)    -- Timeout ses dosyası
TimeoutSeconds          INT             -- Timeout süresi (saniye)
DialplanContext         VARCHAR(100)    -- Dialplan context adı
Active                  INT             -- Aktif (0/1)
CreatedDate             DATETIME        -- Oluşturma tarihi
UpdatedDate             DATETIME        -- Güncelleme tarihi
```

#### PROJECTS (Projeler)
```sql
-- Projeler
SELECT COUNT(*) FROM asteriskcdrdb.PROJECTS;

-- Sütunlar:
id              INT (Primary Key, Auto Increment)
ProjectName     VARCHAR(100)    -- Proje adı
Description     TEXT            -- Açıklama
CreatedDate     DATETIME        -- Oluşturma tarihi
Active          INT             -- Aktif (0/1)
```

---

### 2️⃣ asterisk (Asterisk Veritabanı - Opsiyonel)

**Bağlantı Bilgileri:**
```
Host: 192.168.181.16
Database: asterisk
User: astera
Pass: (Şifrelenmiş - .env dosyasında)
```

**Tablolar:**

#### queue_log (Queue Logları)
```sql
-- Queue logları (opsiyonel)
SELECT COUNT(*) FROM asterisk.queue_log;

-- Sütunlar:
id          INT (Primary Key, Auto Increment)
time        VARCHAR(26)     -- Zaman
callid      VARCHAR(32)     -- Call ID
queuename   VARCHAR(32)     -- Queue adı
agent       VARCHAR(32)     -- Agent
event       VARCHAR(32)     -- Event
data1       VARCHAR(100)    -- Veri 1
data2       VARCHAR(100)    -- Veri 2
data3       VARCHAR(100)    -- Veri 3
data4       VARCHAR(100)    -- Veri 4
data5       VARCHAR(100)    -- Veri 5
```

---

## 💾 Veritabanı Boyutları

### Boyut Kontrolü

```sql
-- asteriskcdrdb boyutu
SELECT 
    table_schema AS 'Database',
    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'Size (MB)'
FROM information_schema.tables
WHERE table_schema = 'asteriskcdrdb'
GROUP BY table_schema;

-- Tablo bazında boyutlar
SELECT 
    table_name AS 'Table',
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)',
    table_rows AS 'Rows'
FROM information_schema.tables
WHERE table_schema = 'asteriskcdrdb'
ORDER BY (data_length + index_length) DESC;
```

---

## 📋 Hızlı Kontrol SQL'leri

### Kayıt Sayıları

```sql
-- Toplam arama sayısı
SELECT COUNT(*) AS TotalCalls FROM asteriskcdrdb.CALLS;

-- Durum bazında
SELECT 
    CASE 
        WHEN Status = 1 THEN 'Bekliyor'
        WHEN Status = 2 THEN 'Arandı'
        ELSE 'Diğer'
    END AS Durum,
    COUNT(*) AS Adet
FROM asteriskcdrdb.CALLS
GROUP BY Status;

-- Proje bazında
SELECT 
    p.ProjectName,
    COUNT(c.id) AS CallCount
FROM asteriskcdrdb.CALLS c
LEFT JOIN asteriskcdrdb.PROJECTS p ON c.ProjectId = p.id
GROUP BY p.ProjectName;

-- Son 7 günün importları
SELECT 
    ImportDate,
    OriginalFileName,
    RecordCount,
    Status
FROM asteriskcdrdb.IMPORT_HISTORY
WHERE ImportDate >= DATE_SUB(NOW(), INTERVAL 7 DAY)
ORDER BY ImportDate DESC;

-- Aktif şablonlar
SELECT 
    id,
    TemplateName,
    CreatedDate
FROM asteriskcdrdb.MESSAGE_TEMPLATES
WHERE Active = 1
ORDER BY id;
```

---

## 🔍 Veri Bütünlüğü Kontrolleri

### Orphan Kayıtlar (Yetim Kayıtlar)

```sql
-- ProjectId'si olmayan aramalar
SELECT COUNT(*) AS OrphanCalls
FROM asteriskcdrdb.CALLS
WHERE ProjectId IS NULL OR ProjectId NOT IN (SELECT id FROM asteriskcdrdb.PROJECTS);

-- ImportId'si olmayan aramalar
SELECT COUNT(*) AS OrphanCalls
FROM asteriskcdrdb.CALLS
WHERE ImportId IS NULL OR ImportId NOT IN (SELECT id FROM asteriskcdrdb.IMPORT_HISTORY);

-- TemplateId'si olmayan aramalar (NULL olabilir)
SELECT COUNT(*) AS CallsWithoutTemplate
FROM asteriskcdrdb.CALLS
WHERE TemplateId IS NOT NULL 
AND TemplateId NOT IN (SELECT id FROM asteriskcdrdb.MESSAGE_TEMPLATES);
```

### Veri Tutarlılığı

```sql
-- Locked ama tamamlanmamış aramalar (stuck calls)
SELECT COUNT(*) AS StuckCalls
FROM asteriskcdrdb.CALLS
WHERE Locked = 1 
AND Status = 1
AND TIMESTAMPDIFF(MINUTE, LastCallTime, NOW()) > 10;

-- CallCount > TryCallCount (hatalı)
SELECT COUNT(*) AS InvalidCallCounts
FROM asteriskcdrdb.CALLS
WHERE CallCount > TryCallCount;

-- Geçmişte zamanlanmış ama çalışmayan importlar
SELECT COUNT(*) AS MissedSchedules
FROM asteriskcdrdb.IMPORT_HISTORY
WHERE Status = 'scheduled'
AND ScheduledTime < NOW();
```

---

## 📊 İstatistikler

### Genel İstatistikler

```sql
-- Sistem özeti
SELECT 
    (SELECT COUNT(*) FROM asteriskcdrdb.CALLS) AS TotalCalls,
    (SELECT COUNT(*) FROM asteriskcdrdb.CALLS WHERE Status = 1) AS PendingCalls,
    (SELECT COUNT(*) FROM asteriskcdrdb.CALLS WHERE Status = 2) AS CompletedCalls,
    (SELECT COUNT(*) FROM asteriskcdrdb.CALLS WHERE SipCode = '200') AS SuccessfulCalls,
    (SELECT COUNT(*) FROM asteriskcdrdb.PROJECTS WHERE Active = 1) AS ActiveProjects,
    (SELECT COUNT(*) FROM asteriskcdrdb.MESSAGE_TEMPLATES WHERE Active = 1) AS ActiveTemplates,
    (SELECT COUNT(*) FROM asteriskcdrdb.IMPORT_HISTORY) AS TotalImports;
```

### Performans Metrikleri

```sql
-- Başarı oranı
SELECT 
    COUNT(*) AS TotalCalls,
    SUM(CASE WHEN SipCode = '200' THEN 1 ELSE 0 END) AS SuccessfulCalls,
    ROUND(SUM(CASE WHEN SipCode = '200' THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) AS SuccessRate
FROM asteriskcdrdb.CALLS
WHERE Status = 2;

-- Günlük arama istatistikleri (son 30 gün)
SELECT 
    DATE(RecordDate) AS Date,
    COUNT(*) AS TotalCalls,
    SUM(CASE WHEN SipCode = '200' THEN 1 ELSE 0 END) AS Successful,
    SUM(CASE WHEN Status = 1 THEN 1 ELSE 0 END) AS Pending
FROM asteriskcdrdb.CALLS
WHERE RecordDate >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY DATE(RecordDate)
ORDER BY Date DESC;
```

---

## 🔗 İlişkiler (Foreign Keys)

```
CALLS
├── ProjectId → PROJECTS.id
├── ImportId → IMPORT_HISTORY.id
└── TemplateId → MESSAGE_TEMPLATES.id

IMPORT_HISTORY
├── ProjectId → PROJECTS.id
└── TemplateId → MESSAGE_TEMPLATES.id
```

**Not:** Foreign key constraint'ler tanımlı değil (MySQL 5.5 uyumluluk için).

---

## 📝 Tablo Şemaları

### CALLS Tablosu (Detaylı)

```sql
CREATE TABLE IF NOT EXISTS `CALLS` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `PhoneNumber` varchar(20) DEFAULT NULL,
  `Adi` varchar(100) DEFAULT NULL,
  `Tutar` decimal(10,2) DEFAULT NULL,
  `Metin1` text,
  `Metin2` text,
  `Metin3` text,
  `ProjectId` int(11) DEFAULT NULL,
  `ImportId` int(11) DEFAULT NULL,
  `TemplateId` int(11) DEFAULT NULL,
  `Status` int(11) DEFAULT 1,
  `SipCode` varchar(10) DEFAULT NULL,
  `CallCount` int(11) DEFAULT 0,
  `TryCallCount` int(11) DEFAULT 3,
  `Locked` int(11) DEFAULT 0,
  `CallTime` datetime DEFAULT NULL,
  `LastCallTime` datetime DEFAULT NULL,
  `EndCallTime` datetime DEFAULT NULL,
  `RecordDate` datetime DEFAULT NULL,
  `Aramaadi` varchar(100) DEFAULT NULL,
  `Aktif` int(11) DEFAULT 1,
  `tus1` varchar(10) DEFAULT NULL,
  `tus2` varchar(10) DEFAULT NULL,
  `tus3` varchar(10) DEFAULT NULL,
  `Durum` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`Status`),
  KEY `idx_locked` (`Locked`),
  KEY `idx_project` (`ProjectId`),
  KEY `idx_import` (`ImportId`),
  KEY `idx_phone` (`PhoneNumber`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 🗑️ Temizleme SQL'leri

### Eski Kayıtları Temizle

```sql
-- 6 ay önceki tamamlanmış aramaları sil
DELETE FROM asteriskcdrdb.CALLS
WHERE Status = 2 
AND RecordDate < DATE_SUB(NOW(), INTERVAL 6 MONTH);

-- 1 yıl önceki import kayıtlarını sil
DELETE FROM asteriskcdrdb.IMPORT_HISTORY
WHERE ImportDate < DATE_SUB(NOW(), INTERVAL 1 YEAR);

-- Orphan kayıtları temizle
DELETE FROM asteriskcdrdb.CALLS
WHERE ProjectId NOT IN (SELECT id FROM asteriskcdrdb.PROJECTS);
```

### Stuck Calls Temizleme

```sql
-- 10 dakikadan eski locked kayıtları unlock et
UPDATE asteriskcdrdb.CALLS
SET Locked = 0, Status = 1
WHERE Locked = 1
AND TIMESTAMPDIFF(MINUTE, LastCallTime, NOW()) > 10;
```

---

## 💡 Önemli Notlar

```
⚠️  CALLS tablosu en büyük tablo (zamanla büyür)
⚠️  Düzenli temizlik yapılmalı
⚠️  Foreign key yok, manuel kontrol gerekli
⚠️  Index'ler performans için kritik
⚠️  MySQL 5.5 uyumlu yapıda
✅  UTF-8 charset kullanılıyor
✅  InnoDB engine (transaction support)
```

---

**Veritabanı yapısı basit ve anlaşılır!** 🗄️✨

**Backup için:** `backup_database.bat` kullanın! 💾
