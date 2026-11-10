<?php
/**
 * Otomatik CDR → SipCode Sync Cron Job
 * Her 5 dakikada bir çalışarak SipCode NULL olan kayıtları günceller
 * 
 * Windows Task Scheduler:
 * C:\xampp\php\php.exe -f "d:\xampp\htdocs\AnabilimOtomatikArama\cron\sync_cdr_sipcode.php"
 * 
 * Linux Cron:
 * */5 * * * * /usr/bin/php /var/www/html/AnabilimOtomatikArama/cron/sync_cdr_sipcode.php
 */

// Zaman aşımını kaldır
set_time_limit(0);

// Hata raporlamayı aç
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log dosyası
$logFile = __DIR__ . '/../logs/cdr_sync_' . date('Y-m-d') . '.log';
$logDir = dirname($logFile);

if (!file_exists($logDir)) {
    mkdir($logDir, 0777, true);
}

function writeLog($message)
{
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[{$timestamp}] {$message}\n", FILE_APPEND);
    echo "[{$timestamp}] {$message}\n";
}

writeLog("=== CDR → SipCode Sync Başlatıldı ===");

try {
    // Config yükle
    require_once __DIR__ . '/../config.php';
    
    // Disposition → SipCode mapping
    $dispositionToSipCode = [
        'ANSWERED' => 200,
        'NO ANSWER' => 480,
        'BUSY' => 486,
        'FAILED' => 503,
        'CONGESTION' => 503
    ];
    
    // CDR veritabanı bağlantısı
    $dbHost = EnvLoader::get('DB_HOST', '192.168.181.49');
    $dbUser = EnvLoader::get('DB_USER', 'astera');
    $dbPass = EnvLoader::get('DB_PASS', 'astera2012');
    
    try {
        $pdoCdr = new PDO(
            "mysql:host={$dbHost};dbname=asteriskcdrdb;charset=utf8mb4",
            $dbUser,
            $dbPass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        writeLog("CDR veritabanına bağlandı");
    } catch (Exception $e) {
        writeLog("HATA: CDR veritabanına bağlanılamadı - " . $e->getMessage());
        exit(1);
    }
    
    // SipCode NULL veya 0 olan kayıtları bul
    $stmt = $pdo->query("
        SELECT id, PhoneNumber, EndCallTime 
        FROM CALLS 
        WHERE Status = 2 
        AND (SipCode IS NULL OR SipCode = 0)
        AND EndCallTime IS NOT NULL
        AND EndCallTime >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ORDER BY id DESC
        LIMIT 50
    ");
    $calls = $stmt->fetchAll();
    
    $totalCalls = count($calls);
    writeLog("İşlenecek kayıt sayısı: {$totalCalls}");
    
    if ($totalCalls == 0) {
        writeLog("Güncelleme gereken kayıt yok");
        writeLog("=== Sync Tamamlandı ===\n");
        exit(0);
    }
    
    $updated = 0;
    $skipped = 0;
    $errors = 0;
    
    foreach ($calls as $call) {
        try {
            // ÇÖZÜM 1: userfield (Call ID) ile direkt eşleştir
            $cdrStmt = $pdoCdr->prepare("
                SELECT disposition, calldate, duration, billsec
                FROM cdr 
                WHERE userfield = ?
                AND (dcontext LIKE '%auto-calls%' OR dcontext LIKE '%template%' OR dcontext = '')
                AND calldate >= DATE_SUB(?, INTERVAL 2 HOUR)
                AND calldate <= DATE_ADD(?, INTERVAL 2 HOUR)
                ORDER BY ABS(TIMESTAMPDIFF(SECOND, calldate, ?)) ASC
                LIMIT 1
            ");
            $cdrStmt->execute([
                $call['id'],  // userfield = Call ID
                $call['EndCallTime'],
                $call['EndCallTime'],
                $call['EndCallTime']
            ]);
            $cdr = $cdrStmt->fetch();
            
            // ÇÖZÜM 2: Template için context + zaman ile eşleştir (dst=hangup için)
            if (!$cdr) {
                $cdrStmt = $pdoCdr->prepare("
                    SELECT disposition, calldate, duration, billsec
                    FROM cdr 
                    WHERE (dcontext LIKE '%auto-calls%' OR dcontext LIKE '%template%')
                    AND calldate >= DATE_SUB(?, INTERVAL 2 MINUTE)
                    AND calldate <= DATE_ADD(?, INTERVAL 2 MINUTE)
                    ORDER BY ABS(TIMESTAMPDIFF(SECOND, calldate, ?)) ASC
                    LIMIT 1
                ");
                $cdrStmt->execute([
                    $call['EndCallTime'],
                    $call['EndCallTime'],
                    $call['EndCallTime']
                ]);
                $cdr = $cdrStmt->fetch();
            }
            
            // ÇÖZÜM 3: Normal aramalar için telefon numarası ile eşleştir
            if (!$cdr) {
                $cdrStmt = $pdoCdr->prepare("
                    SELECT disposition, calldate, duration, billsec
                    FROM cdr 
                    WHERE (
                        dst = ? OR 
                        src = ? OR 
                        lastdata LIKE CONCAT('%', ?, '%')
                    )
                    AND calldate >= DATE_SUB(?, INTERVAL 2 HOUR)
                    AND calldate <= DATE_ADD(?, INTERVAL 2 HOUR)
                    ORDER BY ABS(TIMESTAMPDIFF(SECOND, calldate, ?)) ASC
                    LIMIT 1
                ");
                $cdrStmt->execute([
                    $call['PhoneNumber'],
                    $call['PhoneNumber'],
                    $call['PhoneNumber'],
                    $call['EndCallTime'],
                    $call['EndCallTime'],
                    $call['EndCallTime']
                ]);
                $cdr = $cdrStmt->fetch();
            }
            
            if ($cdr) {
                $disposition = strtoupper($cdr['disposition']);
                $newSipCode = $dispositionToSipCode[$disposition] ?? 503;
                
                // Güncelle
                $updateStmt = $pdo->prepare("UPDATE CALLS SET SipCode = ? WHERE id = ?");
                $updateStmt->execute([$newSipCode, $call['id']]);
                
                writeLog("✅ ID: {$call['id']}, Tel: {$call['PhoneNumber']}, Disposition: {$disposition} → SipCode: {$newSipCode}");
                $updated++;
            } else {
                writeLog("⚠️ ID: {$call['id']}, Tel: {$call['PhoneNumber']} - CDR'da eşleşme bulunamadı");
                $skipped++;
            }
        } catch (Exception $e) {
            writeLog("❌ ID: {$call['id']} - Hata: " . $e->getMessage());
            $errors++;
        }
    }
    
    // Özet
    writeLog("--- Özet ---");
    writeLog("Toplam: {$totalCalls}");
    writeLog("Güncellenen: {$updated}");
    writeLog("Atlanan: {$skipped}");
    writeLog("Hata: {$errors}");
    writeLog("=== Sync Tamamlandı ===\n");
    
    exit(0);
    
} catch (Exception $e) {
    writeLog("HATA: " . $e->getMessage());
    writeLog("Stack trace: " . $e->getTraceAsString());
    exit(1);
}
