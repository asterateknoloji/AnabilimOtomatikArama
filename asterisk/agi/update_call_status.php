#!/usr/bin/php -q
<?php
/**
 * Asterisk AGI Script - Arama durumu güncelle
 * TÜMU BURADA: answered, busy, noanswer, congestion
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
$logFile = '/tmp/agi_call_status.log';
$logTime = date('Y-m-d H:i:s');

// Parametreleri al
$callId = isset($argv[1]) ? $argv[1] : null;
$statusStr = isset($argv[2]) ? $argv[2] : null;

file_put_contents($logFile, "[$logTime] START - CallId: $callId, Status: $statusStr\n", FILE_APPEND);

if (!$callId || !$statusStr) {
    $agi->verbose("Hata: Eksik parametreler", 1);
    file_put_contents($logFile, "[$logTime] ERROR: Missing params\n", FILE_APPEND);
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
    
    // Call bilgilerini al (ImportId, TryCallCount, CallCount gerekli)
    $checkStmt = $pdo->prepare("SELECT ImportId, TryCallCount, CallCount, Status FROM {$dbname}.CALLS WHERE id = ?");
    $checkStmt->execute(array($callId));
    $callData = $checkStmt->fetch(PDO::FETCH_ASSOC);
    $checkStmt->closeCursor();
    
    if (!$callData) {
        $agi->verbose("Hata: Call ID $callId bulunamadı", 1);
        file_put_contents($logFile, "[$logTime] ERROR: Call not found\n", FILE_APPEND);
        exit(1);
    }
    
    // EĞER ZATEN Status=2 (başarılı) İSE, TEKRAR GÜNCELLEME YAPMA!
    if ($callData['Status'] == 2 && $statusStr !== 'answered') {
        $agi->verbose("Call ID $callId zaten başarılı (Status=2), güncelleme atlandı", 2);
        file_put_contents($logFile, "[$logTime] SKIP - Already Status=2, not updating for $statusStr\n", FILE_APPEND);
        exit(0);
    }
    
    // Durum mesajları
    $durumMap = array(
        'answered' => 'Cevaplandı',
        'noanswer' => 'Cevapsız',
        'busy' => 'Meşgul',
        'congestion' => 'Tıkanıklık'
    );
    
    $durumMesaji = isset($durumMap[$statusStr]) ? $durumMap[$statusStr] : 'Bilinmeyen';
    
    // Başarılı mı?
    $isSuccessful = ($statusStr === 'answered');
    
    // SIP kodları
    $sipCodeMap = array(
        'answered' => 200,
        'busy' => 486,       // Busy Here
        'noanswer' => 480,   // Temporarily Unavailable
        'congestion' => 503  // Service Unavailable
    );
    $sipCode = isset($sipCodeMap[$statusStr]) ? $sipCodeMap[$statusStr] : 503;
    
    // Status ve retry mantığı
    $newStatus = 2; // Varsayılan: Arandı (bitti)
    $newCallTime = null;
    
    // CallCount sadece BAŞARILI aramalar için artacak!
    // Başarısız aramalar için update_call_end.php artıracak
    $newCallCount = $isSuccessful ? ($callData['CallCount'] + 1) : $callData['CallCount'];
    
    if (!$isSuccessful && $callData['ImportId']) {
        // Başarısız arama - retry kontrol et
        $importStmt = $pdo->prepare("SELECT RetryIntervalMinutes, RetryCount FROM {$dbname}.IMPORT_HISTORY WHERE id = ?");
        $importStmt->execute(array($callData['ImportId']));
        $import = $importStmt->fetch(PDO::FETCH_ASSOC);
        $importStmt->closeCursor();
        
        // Retry limiti kontrol
        if ($import && $import['RetryIntervalMinutes'] && $newCallCount < $callData['TryCallCount']) {
            // Retry var!
            $retryMinutes = intval($import['RetryIntervalMinutes']);
            $newStatus = 1; // Tekrar aranacak
            $newCallTime = "DATE_ADD(NOW(), INTERVAL $retryMinutes SECOND)"; // Bir sonraki parti/deneme zamanı
            $agi->verbose("Call ID $callId - Başarısız ($statusStr), Status=1 (retry), CallCount=$newCallCount, RetryMinutes=$retryMinutes", 2);
            file_put_contents($logFile, "[$logTime] RETRY - CallCount: $newCallCount, Status: 1, RetryMinutes: $retryMinutes, NextCallTime: +$retryMinutes min\n", FILE_APPEND);
        } else {
            // Retry yok veya limit doldu
            $newStatus = 2; // Bitti
            $agi->verbose("Call ID $callId - Başarısız ($statusStr), Status=2 (bitti), CallCount=$newCallCount", 2);
            file_put_contents($logFile, "[$logTime] NO_RETRY - CallCount: $newCallCount, Status: 2\n", FILE_APPEND);
        }
    } else {
        // Başarılı (answered) veya ImportId yok
        $newStatus = 2;
        $agi->verbose("Call ID $callId - Başarılı ($statusStr), Status=2, CallCount=$newCallCount", 2);
        file_put_contents($logFile, "[$logTime] SUCCESS - CallCount: $newCallCount, Status: 2\n", FILE_APPEND);
    }
    
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
        $sql .= ", PickupTime = COALESCE(PickupTime, NOW())"; // Zaten varsa dokunma
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
    
    // Execute
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $affected = $stmt->rowCount();
    
    $agi->verbose("Call ID $callId güncellendi - Status: $newStatus, Durum: $durumMesaji, CallCount: $newCallCount", 2);
    file_put_contents($logFile, "[$logTime] UPDATE OK - Status: $newStatus, Durum: $durumMesaji, CallCount: $newCallCount, Affected: $affected\n", FILE_APPEND);
    
    // Aramalar bittiyse (tüm import Status=2) Aktif=0 yap
    if ($newStatus == 2 && !empty($callData['ImportId'])) {
        $chk = $pdo->prepare("SELECT COUNT(*) FROM {$dbname}.CALLS WHERE ImportId = ? AND Status != 2");
        $chk->execute(array($callData['ImportId']));
        if ($chk->fetchColumn() == 0) {
            $pdo->prepare("UPDATE {$dbname}.CALLS SET Aktif = 0 WHERE ImportId = ?")->execute(array($callData['ImportId']));
            file_put_contents($logFile, "[$logTime] Import {$callData['ImportId']} - Tüm aramalar bitti, Aktif=0 yapıldı\n", FILE_APPEND);
        }
    }
    
} catch (Exception $e) {
    $agi->verbose("Hata: " . $e->getMessage(), 1);
    file_put_contents($logFile, "[$logTime] ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
    exit(1);
}

file_put_contents($logFile, "[$logTime] END\n", FILE_APPEND);

exit(0);
