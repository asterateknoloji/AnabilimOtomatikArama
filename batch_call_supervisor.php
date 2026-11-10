<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Bu dosya yalnızca komut satırından çalıştırılır.');
}

// Aynı Windows oturumunda yalnızca bir supervisor çalışsın.
$lockPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'auto_call_batch_supervisor.lock';
$lockHandle = fopen($lockPath, 'c');
if (!$lockHandle || !flock($lockHandle, LOCK_EX | LOCK_NB)) {
    exit(0);
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/BatchWorkerLauncher.php';

try {
    while (true) {
        try {
            $activeImports = $pdo->query("
                SELECT DISTINCT ImportId
                FROM " . table('CALLS') . "
                WHERE ImportId IS NOT NULL
                  AND Aktif = 1
                  AND Status IN (0, 1)
                  AND CallCount < TryCallCount
            ")->fetchAll(PDO::FETCH_COLUMN);

            foreach ($activeImports as $importId) {
                // İşçi zaten çalışıyorsa kendi flock kilidi nedeniyle ikinci kopya hemen çıkar.
                launchBatchCallWorker((int)$importId, false);
            }
        } catch (Throwable $e) {
            error_log('Batch supervisor kontrol hatası: ' . $e->getMessage());
        }

        sleep(15);
    }
} finally {
    flock($lockHandle, LOCK_UN);
    fclose($lockHandle);
}
