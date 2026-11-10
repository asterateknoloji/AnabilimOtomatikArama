#!/usr/bin/php -q
<?php
/**
 * Asterisk AGI Script - Arama bitişini kaydet
 * PHP 5.4+ uyumlu
 */

// AGI protokolü - Asterisk'ten gelen environment değişkenlerini oku
while (!feof(STDIN)) {
    $line = trim(fgets(STDIN));
    if ($line === '') break;
}

// Veritabanı bağlantısı (.env'den oku)
$envFile = '/var/www/html/.env';
if (!file_exists($envFile)) {
    $envFile = __DIR__ . '/../../.env';
}

$host = 'localhost';
$dbname = 'ANABILIMNEW';
$user = 'astera';
$pass = 'astera2012';

if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, '"\'');
            if ($key === 'DB_HOST') $host = $value;
            elseif ($key === 'DB_NAME') $dbname = $value;
            elseif ($key === 'DB_USER') $user = $value;
            elseif ($key === 'DB_PASS') $pass = $value;
        }
    }
}

// AGI sınıfı
class AGI {
    public function verbose($msg, $level = 1) {
        echo "VERBOSE \"$msg\" $level\n";
    }
}

$agi = new AGI();

// LOG DOSYASI
$logFile = '/tmp/agi_call_end.log';
$logTime = date('Y-m-d H:i:s');

// Parametreleri al
$callId = isset($argv[1]) ? $argv[1] : null;
$param2 = isset($argv[2]) ? $argv[2] : 'completed';

file_put_contents($logFile, "[$logTime] AGI BAŞLADI - CallId: $callId, Param2: $param2\n", FILE_APPEND);

if (!$callId) {
    $agi->verbose("Hata: Eksik call_id", 1);
    file_put_contents($logFile, "[$logTime] HATA: Eksik call_id\n", FILE_APPEND);
    exit(1);
}

try {
    // PDO bağlantısı
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8",
        $user,
        $pass,
        array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
        )
    );
    
    // Tuşlama yapıldı mı kontrol et ve Status al
    $checkStmt = $pdo->prepare("SELECT tus1, tus2, tus3, Durum, ImportId, Status, CallCount, TryCallCount, SipCode, PickupTime FROM {$dbname}.CALLS WHERE id = ?");
    $checkStmt->execute(array($callId));
    $callData = $checkStmt->fetch(PDO::FETCH_ASSOC);
    $checkStmt->closeCursor();
    
    // ÖNEMLI: Eğer zaten SipCode = 200 (başarılı) ise, değiştirme!
    // update_call_status.php zaten 'answered' için SipCode=200 set etmiş olabilir
    if ($callData['SipCode'] == 200) {
        $agi->verbose("SipCode zaten 200 (başarılı), değiştirilmiyor", 2);
        file_put_contents($logFile, "[$logTime] SipCode=200 (başarılı), update_call_end.php hiçbir şey yapmıyor\n", FILE_APPEND);
        exit(0); // Başarılı aramayı bozmayalım!
    }
    
    // SipCode: Sadece bilgi amaçlı (önemli değil!)
    // Asıl kriter: Status = 2 ise BAŞARILI!
    $sipCode = $callData['SipCode'] ?? 404; // Mevcut SipCode'u koru, yoksa 404
    $durumSonuc = "Cevapsız"; // Varsayılan durum mesajı
    $isSuccessful = false; // Başarı kriteri: Hat açıldı mı?
    
    // PickupTime varsa kesinlikle başarılı!
    if ($callData['PickupTime']) {
        $sipCode = 200;
        $isSuccessful = true;
        $durumSonuc = "Cevaplandı";
        $agi->verbose("PickupTime mevcut - Başarılı arama!", 2);
    }
    
    // Tuşlama yapıldıysa başarılı
    if (!empty($callData['tus1']) || !empty($callData['tus2']) || !empty($callData['tus3'])) {
        $sipCode = 200;
        $durumSonuc = "Tuşlama yapıldı";
        $isSuccessful = true; // BAŞARILI!
    }
    
    // param2 değerine göre durum belirle
    if (is_numeric($param2)) {
        // Duration kontrolü
        $duration = intval($param2);
        if ($duration >= 5) {
            // Hat açıldı ve 5+ saniye dinledi = BAŞARILI!
            $sipCode = 200;
            $durumSonuc = "Dinledi";
            $isSuccessful = true; // BAŞARILI!
            $agi->verbose("Call ID $callId - Hat açıldı! Süre: {$duration}sn → BAŞARILI (Status=2)", 2);
        } else {
            // Çok kısa süre = Cevapsız
            $sipCode = 404;
            $durumSonuc = "Cevapsız";
            $isSuccessful = false;
            $agi->verbose("Call ID $callId - Çok kısa! Süre: {$duration}sn → Başarısız (retry)", 2);
        }
    } else {
        // String param2 değerleri
        switch (strtolower($param2)) {
            case 'noanswer':
                $sipCode = 404;
                $durumSonuc = "Cevapsız";
                $isSuccessful = false;
                break;
            case 'busy':
                $sipCode = 486;
                $durumSonuc = "Meşgul";
                $isSuccessful = false;
                break;
            case 'congestion':
                $sipCode = 503;
                $durumSonuc = "Tıkanıklık";
                $isSuccessful = false;
                break;
            case 'completed':
                // Tuşlama kontrolü ile durum belirlenmiş
                if ($isSuccessful) {
                    $durumSonuc = "Kapattı";
                }
                break;
            default:
                $agi->verbose("Call ID $callId - Bilinmeyen param2: $param2", 2);
        }
        $agi->verbose("Call ID $callId - SipCode: $sipCode (bilgi amaçlı), Durum: $durumSonuc", 2);
    }
    
    // Durum mesajını oluştur
    $durumMesaji = $callData['Durum'];
    $hasTuslama = !empty($callData['tus1']) || !empty($callData['tus2']) || !empty($callData['tus3']);
    
    if (empty($durumMesaji)) {
        // Hiç mesaj yok
        $durumMesaji = $durumSonuc;
    } elseif ($durumMesaji == "Cevaplandı" && !$hasTuslama) {
        // "Cevaplandı" var ama tuşlama yok → "Cevaplandı, Dinledi/Kapattı" yap
        $durumMesaji = "Cevaplandı, " . $durumSonuc;
    } elseif ($hasTuslama) {
        // Tuşlama var, mesajı koruyalım (update_call_dtmf.php zaten yazdı)
        // Hiçbir şey yapma, COALESCE ile korunacak
    } else {
        // Başka durum, üzerine yaz
        $durumMesaji = $durumSonuc;
    }
    
    // ÖNEMLI: Status = 2 ise (zaten arandı), hiçbir şey yapma!
    if ($callData['Status'] == 2) {
        $agi->verbose("Status = 2 (Arandı) - update_call_end.php atlanıyor", 2);
        file_put_contents($logFile, "[$logTime] Status = 2, hiçbir işlem yapılmadı\n", FILE_APPEND);
        exit(0); // Çık!
    }
    
    // Status mantığı: Sadece isSuccessful'a bak, SipCode'a değil!
    $newCallTime = null; // CallTime için (retry varsa gelecek zaman)
    $newStatus = $callData['Status']; // Mevcut Status'u koru
    $incrementCallCount = false; // CallCount artırılacak mı?
    
    if (!$isSuccessful && $callData['ImportId']) {
        // Başarısız arama (hat açılmadı) - retry kontrol et
        $importStmt = $pdo->prepare("SELECT RetryIntervalMinutes, RetryCount FROM {$dbname}.IMPORT_HISTORY WHERE id = ?");
        $importStmt->execute(array($callData['ImportId']));
        $import = $importStmt->fetch(PDO::FETCH_ASSOC);
        $importStmt->closeCursor();
        
        // CallCount artır (busy, noanswer, congestion için)
        $incrementCallCount = true;
        $newCallCount = $callData['CallCount'] + 1;
        
        // Retry kontrolü
        if ($import && $import['RetryIntervalMinutes'] && $newCallCount < $callData['TryCallCount']) {
            $retryMinutes = intval($import['RetryIntervalMinutes']);
            $agi->verbose("Başarısız arama - CallCount=$newCallCount, Status=1 (tekrar aranacak), CallTime $retryMinutes dk öteleniyor", 2);
            $newCallTime = "DATE_ADD(NOW(), INTERVAL $retryMinutes SECOND)"; // Bir sonraki parti/deneme zamanı
            $newStatus = 1; // Tekrar aranacak
        } else {
            $agi->verbose("Başarısız arama - CallCount=$newCallCount, Status=2 (retry limiti doldu veya yok)", 2);
            $newStatus = 2;
        }
    } else {
        // Başarılı (hat açıldı) veya import yok
        $agi->verbose("Başarılı arama - Status=2 (bitti)", 2);
        $newStatus = 2;
        $incrementCallCount = false; // Zaten answered, CallCount artırıldı
    }
    
    // Status güncelle:
    // - Başarılı (answered): Status=2, CallCount zaten artırıldı (update_call_status.php'de)
    // - Başarısız (busy/noanswer/congestion): Status=1/2, CallCount artır (burada)
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
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array($newStatus, $sipCode, $durumMesaji, $callId));
        $affected = $stmt->rowCount();
        file_put_contents($logFile, "[$logTime] UPDATE BAŞARILI (retry+CallCount++) - CallId: $callId, Status: $newStatus, SipCode: $sipCode, Affected: $affected\n", FILE_APPEND);
    } elseif ($incrementCallCount) {
        // Başarısız + Retry yok
        $sql = "UPDATE {$dbname}.CALLS 
                SET Status = ?,
                    SipCode = ?,
                    EndCallTime = NOW(),
                    LastCallTime = NOW(),
                    CallTime = NOW(),
                    CallCount = CallCount + 1,
                    Locked = 0,
                    Durum = COALESCE(?, Durum)
                WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array($newStatus, $sipCode, $durumMesaji, $callId));
        $affected = $stmt->rowCount();
        file_put_contents($logFile, "[$logTime] UPDATE BAŞARILI (CallCount++) - CallId: $callId, Status: $newStatus, SipCode: $sipCode, Affected: $affected\n", FILE_APPEND);
    } elseif ($newCallTime) {
        // Başarılı ama retry var (nadiren)
        $sql = "UPDATE {$dbname}.CALLS 
                SET Status = ?,
                    SipCode = ?,
                    EndCallTime = NOW(),
                    LastCallTime = NOW(),
                    CallTime = $newCallTime,
                    Locked = 0,
                    Durum = COALESCE(?, Durum)
                WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array($newStatus, $sipCode, $durumMesaji, $callId));
        $affected = $stmt->rowCount();
        file_put_contents($logFile, "[$logTime] UPDATE BAŞARILI (retry) - CallId: $callId, Status: $newStatus, SipCode: $sipCode, Affected: $affected\n", FILE_APPEND);
    } else {
        // Başarılı + Retry yok
        $sql = "UPDATE {$dbname}.CALLS 
                SET Status = ?,
                    SipCode = ?,
                    EndCallTime = NOW(),
                    LastCallTime = NOW(),
                    CallTime = NOW(),
                    Locked = 0,
                    Durum = COALESCE(?, Durum)
                WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array($newStatus, $sipCode, $durumMesaji, $callId));
        $affected = $stmt->rowCount();
        file_put_contents($logFile, "[$logTime] UPDATE BAŞARILI - CallId: $callId, Status: $newStatus, SipCode: $sipCode, Affected: $affected\n", FILE_APPEND);
    }
    
    // Aramalar bittiyse (tüm import Status=2) Aktif=0 yap
    if ($newStatus == 2 && !empty($callData['ImportId'])) {
        $chk = $pdo->prepare("SELECT COUNT(*) FROM {$dbname}.CALLS WHERE ImportId = ? AND Status != 2");
        $chk->execute(array($callData['ImportId']));
        if ($chk->fetchColumn() == 0) {
            $pdo->prepare("UPDATE {$dbname}.CALLS SET Aktif = 0 WHERE ImportId = ?")->execute(array($callData['ImportId']));
            file_put_contents($logFile, "[$logTime] Import {$callData['ImportId']} - Tüm aramalar bitti, Aktif=0 yapıldı\n", FILE_APPEND);
        }
    }
    
    // CDR tablosunda dst alanını güncelle
    try {
        $cdrPdo = new PDO(
            "mysql:host=$host;dbname=asteriskcdrdb;charset=utf8",
            $user,
            $pass,
            array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
        );
        
        // CALLS tablosundan telefon numarasını al
        $phoneStmt = $pdo->prepare("SELECT PhoneNumber FROM {$dbname}.CALLS WHERE id = ?");
        $phoneStmt->execute(array($callId));
        $phoneData = $phoneStmt->fetch(PDO::FETCH_ASSOC);
        $phoneStmt->closeCursor();
        
        if ($phoneData && !empty($phoneData['PhoneNumber'])) {
            $phoneNumber = $phoneData['PhoneNumber'];
            
            // CDR'da userfield = callId olan kaydı bul ve dst'yi güncelle
            $updateCdrStmt = $cdrPdo->prepare("
                UPDATE asteriskcdrdb.cdr 
                SET dst = ? 
                WHERE userfield = ? 
                ORDER BY calldate DESC 
                LIMIT 1
            ");
            $updateCdrStmt->execute(array($phoneNumber, $callId));
            
            $cdrAffected = $updateCdrStmt->rowCount();
            if ($cdrAffected > 0) {
                $agi->verbose("CDR güncellendi - dst = $phoneNumber (userfield=$callId)", 2);
                file_put_contents($logFile, "[$logTime] CDR UPDATE - dst güncellendi: $phoneNumber (userfield=$callId, affected=$cdrAffected)\n", FILE_APPEND);
            } else {
                file_put_contents($logFile, "[$logTime] CDR UPDATE - Kayıt bulunamadı (userfield=$callId)\n", FILE_APPEND);
            }
        }
    } catch (Exception $cdrError) {
        // CDR güncellemesi hata verse bile devam et
        $agi->verbose("CDR UPDATE HATA: " . $cdrError->getMessage(), 1);
        file_put_contents($logFile, "[$logTime] CDR UPDATE HATA: " . $cdrError->getMessage() . "\n", FILE_APPEND);
    }
    
} catch (Exception $e) {
    $agi->verbose("Hata: " . $e->getMessage(), 1);
    file_put_contents($logFile, "[$logTime] HATA: " . $e->getMessage() . "\n", FILE_APPEND);
    exit(1);
}

exit(0);
