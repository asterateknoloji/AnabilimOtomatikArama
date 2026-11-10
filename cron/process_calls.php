<?php
/**
 * Zamanlanmış Görev: Otomatik Arama İşleyici
 * 
 * Bu script periyodik olarak çalışarak bekleyen aramaları başlatır.
 * 
 * Windows Task Scheduler ile çalıştırma:
 * C:\xampp\php\php.exe -f "d:\xampp\htdocs\AsteraWeb\cron\process_calls.php"
 * 
 * Linux Cron ile çalıştırma:
 * */5 * * * * /usr/bin/php /var/www/html/AsteraWeb/cron/process_calls.php
 */

// Zaman aşımını kaldır
set_time_limit(0);

// Hata raporlamayı aç
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log dosyası
$logFile = __DIR__ . '/../logs/autocall_' . date('Y-m-d') . '.log';
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

writeLog("=== Otomatik Arama İşleyici Başlatıldı ===");

try {
    // Config ve manager'ı yükle
    require_once __DIR__ . '/../config.php';
    require_once __DIR__ . '/../includes/AutoCallManager.php';
    
    $manager = new AutoCallManager($pdo);
    
    // İstatistikleri al
    $stats = $manager->getStats();
    writeLog("Toplam kayıt: {$stats['total']}, Bekleyen: {$stats['pending']}, Aranıyor: {$stats['calling']}, Tamamlanan: {$stats['completed']}");
    
    // Bekleyen aramalar varsa başlat
    if ($stats['pending'] > 0) {
        writeLog("Aramaları başlatıyor...");
        $count = $manager->startPendingCalls();
        writeLog("{$count} arama başlatıldı");
    } else {
        writeLog("Bekleyen arama yok");
    }
    
    // Kilitli kalan kayıtları kontrol et (30 dakikadan eski)
    $stmt = $pdo->prepare("
        UPDATE " . table('CALLS') . " 
        SET Locked = 0, Status = ? 
        WHERE Locked = 1 
        AND CallTime < DATE_SUB(NOW(), INTERVAL 30 MINUTE)
    ");
    $stmt->execute([AutoCallManager::STATUS_PENDING]);
    $unlockedCount = $stmt->rowCount();
    
    if ($unlockedCount > 0) {
        writeLog("{$unlockedCount} kilitli kayıt serbest bırakıldı");
    }
    
    writeLog("İşlem tamamlandı");
    
    // CDR → SipCode Sync (Bonus: Aramalardan sonra otomatik güncelle)
    writeLog("CDR senkronizasyonu başlatılıyor...");
    try {
        // CDR veritabanı bağlantısı
        $dbHost = EnvLoader::get('DB_HOST', '192.168.181.49');
        $dbUser = EnvLoader::get('DB_USER', 'astera');
        $dbPass = EnvLoader::get('DB_PASS', 'astera2012');
        
        $pdoCdr = new PDO(
            "mysql:host={$dbHost};dbname=asteriskcdrdb;charset=utf8mb4",
            $dbUser,
            $dbPass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        $dispositionToSipCode = [
            'ANSWERED' => 200,
            'NO ANSWER' => 480,
            'BUSY' => 486,
            'FAILED' => 503,
            'CONGESTION' => 503
        ];
        
        // Son 10 dakikada SipCode NULL olan kayıtlar
        $stmt = $pdo->query("
            SELECT id, PhoneNumber, EndCallTime 
            FROM " . table('CALLS') . " 
            WHERE Status = 2 
            AND (SipCode IS NULL OR SipCode = 0)
            AND EndCallTime >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)
            LIMIT 20
        ");
        $calls = $stmt->fetchAll();
        
        $syncCount = 0;
        foreach ($calls as $call) {
            try {
                // ÇÖZÜM 1: userfield (Call ID) ile direkt eşleştir
                $cdrStmt = $pdoCdr->prepare("
                    SELECT disposition 
                    FROM cdr 
                    WHERE userfield = ?
                    AND (dcontext LIKE '%auto-calls%' OR dcontext LIKE '%template%' OR dcontext = '')
                    AND calldate >= DATE_SUB(?, INTERVAL 5 MINUTE)
                    ORDER BY calldate DESC 
                    LIMIT 1
                ");
                $cdrStmt->execute([
                    $call['id'],  // userfield = Call ID
                    $call['EndCallTime']
                ]);
                $cdr = $cdrStmt->fetch();
                
                // ÇÖZÜM 2: Template için context + zaman ile eşleştir
                if (!$cdr) {
                    $cdrStmt = $pdoCdr->prepare("
                        SELECT disposition 
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
                        SELECT disposition 
                        FROM cdr 
                        WHERE (
                            dst = ? OR 
                            src = ? OR 
                            lastdata LIKE CONCAT('%', ?, '%')
                        )
                        AND calldate >= DATE_SUB(?, INTERVAL 5 MINUTE)
                        ORDER BY calldate DESC 
                        LIMIT 1
                    ");
                    $cdrStmt->execute([
                        $call['PhoneNumber'],
                        $call['PhoneNumber'],
                        $call['PhoneNumber'],
                        $call['EndCallTime']
                    ]);
                    $cdr = $cdrStmt->fetch();
                }
                
                if ($cdr) {
                    $disposition = strtoupper($cdr['disposition']);
                    $newSipCode = $dispositionToSipCode[$disposition] ?? 503;
                    
                    $updateStmt = $pdo->prepare("UPDATE " . table('CALLS') . " SET SipCode = ? WHERE id = ?");
                    $updateStmt->execute([$newSipCode, $call['id']]);
                    $syncCount++;
                }
            } catch (Exception $e) {
                // Sessizce devam et
            }
        }
        
        if ($syncCount > 0) {
            writeLog("CDR sync: {$syncCount} kayıt güncellendi");
        }
        
    } catch (Exception $e) {
        writeLog("CDR sync hatası (önemli değil): " . $e->getMessage());
    }
    
} catch (Exception $e) {
    writeLog("HATA: " . $e->getMessage());
    writeLog("Stack trace: " . $e->getTraceAsString());
    exit(1);
}

writeLog("=== İşleyici Sonlandırıldı ===\n");
exit(0);
