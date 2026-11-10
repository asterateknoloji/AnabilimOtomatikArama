# AGI Scriptlerini Manuel Güncelleme

## Yöntem 1: WinSCP ile Yükleme (ÖNERİLEN)

1. **WinSCP'yi indirin:** https://winscp.net/eng/download.php

2. **Bağlantı bilgileri:**
   - Host: `213.14.87.158`
   - Port: `45555`
   - User: `root`
   - Password: `.env dosyasındaki ASTERISK_SSH_PASS`

3. **Yüklenecek dosyalar:**
   ```
   Yerel: D:\xampp\htdocs\WindowsOtomatikAramason97\asterisk\agi\
   Uzak:  /var/lib/asterisk/agi-bin/
   
   Dosyalar:
   - update_call_status.php
   - update_call_dtmf.php
   - update_call_end.php
   ```

4. **Yükleme sonrası (SSH/PuTTY ile):**
   ```bash
   chmod +x /var/lib/asterisk/agi-bin/*.php
   chown asterisk:asterisk /var/lib/asterisk/agi-bin/*.php
   asterisk -rx "dialplan reload"
   ```

---

## Yöntem 2: SSH ile Doğrudan Düzenleme

### A. PuTTY ile bağlanın
- Host: `213.14.87.158`
- Port: `45555`
- User: `root`

### B. update_call_status.php düzeltmeleri

```bash
nano /var/lib/asterisk/agi-bin/update_call_status.php
```

**Düzeltme 1:** Satır 108 civarında SipCode ekle:
```php
// SIP kodları
$sipCodeMap = array(
    'answered' => 200,
    'busy' => 486,       // Busy Here
    'noanswer' => 480,   // Temporarily Unavailable
    'congestion' => 503  // Service Unavailable
);
$sipCode = isset($sipCodeMap[$statusStr]) ? $sipCodeMap[$statusStr] : 503;
```

**Düzeltme 2:** Satır 126 civarında değişken adını değiştir:
```php
// ÖNCE:
$newLastCallTime = "DATE_ADD(NOW(), INTERVAL $retryMinutes MINUTE)";

// SONRA:
$newCallTime = "DATE_ADD(NOW(), INTERVAL $retryMinutes MINUTE)";
```

**Düzeltme 3:** Satır 145 civarında SQL'i güncelle:
```php
// SQL oluştur
$sql = "UPDATE {$dbname}.CALLS 
        SET Status = ?, 
            CallCount = ?,
            Durum = ?,
            SipCode = ?,
            EndCallTime = NOW(),
            LastCallTime = NOW(),
            Locked = 0";

$params = array($newStatus, $newCallCount, $durumMesaji, $sipCode);

// Answered ise PickupTime kaydet
if ($statusStr === 'answered') {
    $sql .= ", PickupTime = NOW()";
}

// Retry varsa CallTime'ı güncelle (gelecek zaman)
if ($newCallTime) {
    $sql .= ", CallTime = $newCallTime";
} else {
    // Retry yok, CallTime'ı şimdi yap
    $sql .= ", CallTime = NOW()";
}

$sql .= " WHERE id = ?";
$params[] = $callId;
```

Kaydet: `CTRL+O`, `ENTER`, Çık: `CTRL+X`

### C. update_call_end.php düzeltmeleri

```bash
nano /var/lib/asterisk/agi-bin/update_call_end.php
```

**Düzeltme 1:** Satır 170 civarında değişken adını değiştir:
```php
// ÖNCE:
$newLastCallTime = null;

// SONRA:
$newCallTime = null; // CallTime için (retry varsa gelecek zaman)
```

**Düzeltme 2:** Satır 188 civarında:
```php
// ÖNCE:
$newLastCallTime = "DATE_ADD(NOW(), INTERVAL $retryMinutes MINUTE)";

// SONRA:
$newCallTime = "DATE_ADD(NOW(), INTERVAL $retryMinutes MINUTE)";
```

**Düzeltme 3:** Satır 205-240 civarında SQL güncellemeleri:
```php
if ($incrementCallCount && $newCallTime) {
    // Başarısız + Retry var
    $sql = "UPDATE {$dbname}.CALLS 
            SET Status = ?,
                SipCode = ?,
                EndCallTime = NOW(),
                LastCallTime = NOW(),
                CallTime = $newCallTime,
                CallCount = CallCount + 1,
                Locked = 0,
                Durum = COALESCE(?, Durum)
            WHERE id = ?";
    // ...
}
```

Her SQL bloğunda:
- `LastCallTime = NOW()` ekleyin
- `CallTime = $newCallTime` veya `CallTime = NOW()` ekleyin
- `Locked = 0` zaten var

Kaydet: `CTRL+O`, `ENTER`, Çık: `CTRL+X`

### D. Asterisk'i reload edin

```bash
asterisk -rx "dialplan reload"
```

---

## Yöntem 3: Otomatik Upload Script (Çalışmazsa)

Eğer `upload_fixed_agi.php` çalışmıyorsa, terminal'den çalıştırın:

```bash
cd D:\xampp\htdocs\WindowsOtomatikAramason97
d:\xampp\php\php.exe -f upload_fixed_agi.php
```

---

## Test

Güncellemeden sonra test edin:

1. Arama Yönetimi'nden bir arama başlatın
2. Telefonu reddedin (Busy/Reject)
3. CALLS tablosunu kontrol edin:
   - `Locked = 0` olmalı ✅
   - `CallTime` 60 dakika ileride olmalı ✅
   - `LastCallTime = NOW()` olmalı ✅
   - `SipCode = 486` (busy) olmalı ✅

```sql
SELECT id, PhoneNumber, Adi, Status, Locked, CallCount, TryCallCount,
       DATE_FORMAT(LastCallTime, '%H:%i:%s') as LastCall,
       DATE_FORMAT(CallTime, '%H:%i:%s') as NextCall,
       Durum, SipCode
FROM ANABILIMNEW.CALLS
WHERE ImportId = 205
ORDER BY LastCallTime DESC
LIMIT 5;
```
