<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Bu dosya yalnızca komut satırından çalıştırılır.');
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/AutoCallManager.php';

$importId = isset($argv[1]) ? (int)$argv[1] : 0;
$waitAfterExistingBatch = isset($argv[2]) && (int)$argv[2] === 1;
if ($importId <= 0) {
    exit(1);
}

// Aynı import için ikinci bir işçinin başlamasını engelle.
$lockPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'auto_call_batch_' . $importId . '.lock';
$lockHandle = fopen($lockPath, 'c');
if (!$lockHandle || !flock($lockHandle, LOCK_EX | LOCK_NB)) {
    exit(0);
}

$manager = new AutoCallManager($pdo);
$nextBatchAt = null;
$dbLockName = 'auto_call_batch_import_' . $importId;
$dbLockStmt = $pdo->prepare("SELECT GET_LOCK(?, 0)");
$dbLockStmt->execute([$dbLockName]);
$dbLockAcquired = (int)$dbLockStmt->fetchColumn() === 1;

if (!$dbLockAcquired) {
    flock($lockHandle, LOCK_UN);
    fclose($lockHandle);
    exit(0);
}

try {
    while (true) {
        // AGI/Asterisk sonucu gelmeyen çağrılar sonsuza kadar Locked=1 kalmasın.
        // 3 dakikayı aşan kilitleri yeniden bekleyen duruma al.
        $staleStmt = $pdo->prepare("
            UPDATE " . table('CALLS') . "
            SET Locked = 0,
                Status = 0,
                SipCode = NULL,
                EndCallTime = NOW(),
                Durum = 'Zaman aşımı sonrası yeniden kuyruğa alındı'
            WHERE ImportId = ?
              AND Aktif = 1
              AND Locked = 1
              AND CallCount < TryCallCount
              AND LastCallTime IS NOT NULL
              AND LastCallTime <= DATE_SUB(NOW(), INTERVAL 3 MINUTE)
        ");
        $staleStmt->execute([$importId]);
        if ($staleStmt->rowCount() > 0) {
            error_log("Batch worker Import #{$importId}: {$staleStmt->rowCount()} takılı kayıt yeniden kuyruğa alındı.");
        }

        $stateStmt = $pdo->prepare("
            SELECT
                ih.RetryIntervalMinutes AS BatchIntervalSeconds,
                SUM(CASE WHEN c.Aktif = 1 AND c.Status IN (0, 1)
                          AND c.CallCount < c.TryCallCount THEN 1 ELSE 0 END) AS remaining,
                MAX(CASE WHEN c.Aktif = 1 THEN c.LastCallTime ELSE NULL END) AS last_batch_time
            FROM " . table('IMPORT_HISTORY') . " ih
            LEFT JOIN " . table('CALLS') . " c ON c.ImportId = ih.id
            WHERE ih.id = ?
            GROUP BY ih.id, ih.RetryIntervalMinutes
        ");
        $stateStmt->execute([$importId]);
        $state = $stateStmt->fetch();

        if (!$state || (int)$state['remaining'] === 0) {
            $pdo->prepare("
                UPDATE " . table('CALLS') . "
                SET Aktif = 0
                WHERE ImportId = ?
            ")->execute([$importId]);
            break;
        }

        $intervalSeconds = max(1, (int)$state['BatchIntervalSeconds']);
        if ($nextBatchAt === null) {
            if ($waitAfterExistingBatch) {
                $nextBatchAt = time() + $intervalSeconds;
                $waitAfterExistingBatch = false;
            } else {
                $lastBatchTimestamp = !empty($state['last_batch_time'])
                    ? strtotime($state['last_batch_time'])
                    : 0;
                $nextBatchAt = $lastBatchTimestamp > 0
                    ? $lastBatchTimestamp + $intervalSeconds
                    : time();
            }
        }

        $secondsUntilNextBatch = $nextBatchAt - time();

        if ($secondsUntilNextBatch > 0) {
            sleep(min($secondsUntilNextBatch, 5));
            continue;
        }

        $started = $manager->startAllImportCalls($importId);

        // Hazır kayıt yoksa (ör. aktif çağrı henüz kapanmadıysa) kısa süre sonra tekrar kontrol et.
        if ($started === 0) {
            sleep(5);
        } else {
            $nextBatchAt = time() + $intervalSeconds;
            sleep(min($intervalSeconds, 5));
        }
    }
} catch (Throwable $e) {
    error_log("Batch worker hata (Import #{$importId}): " . $e->getMessage());
} finally {
    if ($dbLockAcquired) {
        $releaseStmt = $pdo->prepare("SELECT RELEASE_LOCK(?)");
        $releaseStmt->execute([$dbLockName]);
    }
    flock($lockHandle, LOCK_UN);
    fclose($lockHandle);
}
