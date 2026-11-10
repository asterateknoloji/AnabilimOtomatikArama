<?php
/**
 * Asterisk SIP Code Updater
 * 
 * Asterisk sunucusunda çalışır
 * outgoing_done klasöründeki call file'ları kontrol eder
 * Gerçek SIP kodlarını database'e yazar
 * 
 * KURULUM (Asterisk sunucusunda):
 * 1. Bu dosyayı /var/www/html/asterisk_sip_updater.php olarak kaydedin
 * 2. .env dosyasını da /var/www/html/.env olarak kaydedin (DB bilgileri için)
 * 3. Crontab'a ekleyin:
 *    */5 * * * * /usr/bin/php /var/www/html/asterisk_sip_updater.php >> /var/log/asterisk/sip_updater.log 2>&1
 */

// .env dosyasını oku
$envFile = __DIR__ . '/.env';
if (!file_exists($envFile)) {
    die("ERROR: .env file not found at {$envFile}\n");
}

$envLines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($envLines as $line) {
    if (strpos(trim($line), '#') === 0) continue;
    
    list($key, $value) = explode('=', $line, 2);
    $key = trim($key);
    $value = trim($value);
    $_ENV[$key] = $value;
    putenv("{$key}={$value}");
}

// Database bağlantısı
$dbHost = getenv('DB_HOST');
$dbPort = getenv('DB_PORT') ?: 3306;
$dbName = getenv('DB_NAME');
$dbUser = getenv('DB_USER');
$dbPass = getenv('DB_PASS');

try {
    $pdo = new PDO(
        "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (Exception $e) {
    die("ERROR: Database connection failed: " . $e->getMessage() . "\n");
}

// Tablo adı
$callsTable = $dbName . '.CALLS';
$importTable = $dbName . '.IMPORT_HISTORY';

echo "=== ASTERISK SIP UPDATER ===\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n\n";

// outgoing_done klasöründeki son 100 call file'ı kontrol et
$outgoingDone = '/var/spool/asterisk/outgoing_done';
$files = glob($outgoingDone . '/call_*.call');

// Son değiştirilme tarihine göre sırala (en yeni önce)
usort($files, function($a, $b) {
    return filemtime($b) - filemtime($a);
});

// Son 100 dosyayı kontrol et
$files = array_slice($files, 0, 100);

$updated = 0;
$skipped = 0;
$errors = 0;

foreach ($files as $file) {
    // Call ID'yi dosya adından al
    $basename = basename($file);
    if (!preg_match('/call_(\d+)_/', $basename, $matches)) {
        continue;
    }
    
    $callId = $matches[1];
    
    // Database'de bu call'ın durumunu kontrol et
    $stmt = $pdo->prepare("SELECT id, Locked, SipCode, LastCallTime FROM {$callsTable} WHERE id = ?");
    $stmt->execute([$callId]);
    $call = $stmt->fetch();
    
    if (!$call) {
        continue; // Call bulunamadı
    }
    
    // Eğer SipCode zaten varsa ve Locked=0 ise, skip
    if (!empty($call['SipCode']) && $call['Locked'] == 0) {
        $skipped++;
        continue;
    }
    
    // Call file'ı oku
    $content = file_get_contents($file);
    
    // Status satırını bul
    if (!preg_match('/Status:\s*(.+)/i', $content, $statusMatch)) {
        continue;
    }
    
    $status = trim($statusMatch[1]);
    
    // Call ID değişkenini al
    preg_match('/Setvar:\s*CALL_ID=(\d+)/i', $content, $callIdMatch);
    
    // Dosya değiştirme zamanını al
    $fileTime = filemtime($file);
    $fileDateTime = date('Y-m-d H:i:s', $fileTime);
    
    // Status'e göre SIP code ve durum belirle
    $sipCode = null;
    $durum = null;
    $newStatus = null;
    $newLocked = 0;
    
    switch (strtolower($status)) {
        case 'completed':
            // Başarılı - muhtemelen answered
            continue 2; // Bu dosyayı atla, zaten answered olan AGI ile güncellenir
            
        case 'expired':
            // Timeout - cevap verilmedi
            $sipCode = 480;
            $durum = 'Cevapsız (Timeout)';
            $newStatus = 1; // Tekrar aranacak
            break;
            
        case 'failed':
            // Başarısız - muhtemelen busy veya unreachable
            // Asterisk log'undan gerçek nedeni bulmaya çalış
            $sipCode = 503;
            $durum = 'Başarısız';
            $newStatus = 1;
            
            // Log'dan SIP response kodunu bul
            $logFile = '/var/log/asterisk/full';
            if (file_exists($logFile)) {
                $logLines = [];
                $handle = fopen($logFile, 'r');
                if ($handle) {
                    // Son 1000 satırı oku
                    fseek($handle, -50000, SEEK_END);
                    while (!feof($handle)) {
                        $logLines[] = fgets($handle);
                    }
                    fclose($handle);
                    
                    // Call ID ile ilgili satırları bul
                    foreach ($logLines as $line) {
                        if (strpos($line, "call_{$callId}") !== false || strpos($line, "CALL_ID={$callId}") !== false) {
                            // SIP response kodunu bul
                            if (preg_match('/SIP response (\d+)/', $line, $sipMatch)) {
                                $sipCode = (int)$sipMatch[1];
                                
                                // Durumu belirle
                                switch ($sipCode) {
                                    case 486:
                                        $durum = 'Meşgul';
                                        break;
                                    case 480:
                                        $durum = 'Cevapsız';
                                        break;
                                    case 487:
                                        $durum = 'İptal Edildi';
                                        break;
                                    case 503:
                                        $durum = 'Ulaşılamıyor';
                                        break;
                                    case 603:
                                        $durum = 'Reddedildi';
                                        break;
                                    default:
                                        $durum = "Başarısız ({$sipCode})";
                                }
                                break;
                            }
                        }
                    }
                }
            }
            break;
            
        default:
            continue 2; // Bilinmeyen status
    }
    
    // Import ayarlarını al
    $stmt = $pdo->prepare("
        SELECT i.RetryIntervalMinutes, c.CallCount, c.TryCallCount, c.ImportId
        FROM {$callsTable} c
        LEFT JOIN {$importTable} i ON c.ImportId = i.id
        WHERE c.id = ?
    ");
    $stmt->execute([$callId]);
    $callData = $stmt->fetch();
    
    $retryInterval = $callData['RetryIntervalMinutes'] ?? 60;
    $callCount = $callData['CallCount'] ?? 0;
    $tryCallCount = $callData['TryCallCount'] ?? 3;
    
    // CallCount'u artır
    $newCallCount = $callCount + 1;
    
    // Kalan deneme var mı kontrol et
    if ($newCallCount >= $tryCallCount) {
        $newStatus = 2; // Tamamlandı
        $durum .= " (Deneme tamamlandı)";
    }
    
    // Database'i güncelle
    try {
        if ($newStatus == 1) {
            // Tekrar aranacak
            $stmt = $pdo->prepare("
                UPDATE {$callsTable}
                SET 
                    Locked = 0,
                    CallCount = CallCount + 1,
                    SipCode = ?,
                    Durum = ?,
                    LastCallTime = ?,
                    CallTime = DATE_ADD(?, INTERVAL ? MINUTE)
                WHERE id = ?
            ");
            $stmt->execute([$sipCode, $durum, $fileDateTime, $fileDateTime, $retryInterval, $callId]);
        } else {
            // Tamamlandı
            $stmt = $pdo->prepare("
                UPDATE {$callsTable}
                SET 
                    Locked = 0,
                    Status = 2,
                    CallCount = CallCount + 1,
                    SipCode = ?,
                    Durum = ?,
                    LastCallTime = ?,
                    CallTime = ?
                WHERE id = ?
            ");
            $stmt->execute([$sipCode, $durum, $fileDateTime, $fileDateTime, $callId]);
        }
        
        // Aramalar bittiyse (tüm import Status=2) Aktif=0 yap
        if ($newStatus == 2 && !empty($callData['ImportId'])) {
            $chk = $pdo->prepare("SELECT COUNT(*) FROM {$callsTable} WHERE ImportId = ? AND Status != 2");
            $chk->execute([$callData['ImportId']]);
            if ($chk->fetchColumn() == 0) {
                $pdo->prepare("UPDATE {$callsTable} SET Aktif = 0 WHERE ImportId = ?")->execute([$callData['ImportId']]);
            }
        }
        
        echo "✅ Call {$callId}: SipCode={$sipCode}, Status={$status}, Durum={$durum}\n";
        $updated++;
        
    } catch (Exception $e) {
        echo "❌ Call {$callId}: Error - " . $e->getMessage() . "\n";
        $errors++;
    }
}

echo "\n";
echo "Summary:\n";
echo "  Updated: {$updated}\n";
echo "  Skipped: {$skipped}\n";
echo "  Errors: {$errors}\n";
echo "========================\n\n";
