<?php

/**
 * Import'un parti arama işçisini arka planda başlatır.
 * İşçi kendi dosya kilidini kullandığı için aynı import için yalnızca bir tane çalışır.
 */
function launchBatchCallWorker($importId, $waitAfterExistingBatch = false)
{
    $importId = (int)$importId;
    if ($importId <= 0) {
        return false;
    }

    $script = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'batch_call_worker.php';
    $xamppPhp = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'php' . DIRECTORY_SEPARATOR . 'php.exe';
    $phpBinary = is_file($xamppPhp) ? $xamppPhp : PHP_BINARY;

    if (!is_file($script) || !$phpBinary) {
        return false;
    }

    if (PHP_OS_FAMILY === 'Windows') {
        $command = 'start "" /B "' . str_replace('"', '""', $phpBinary) . '" "' .
            str_replace('"', '""', $script) . '" ' . $importId . ' ' .
            ($waitAfterExistingBatch ? '1' : '0') . ' >NUL 2>&1';
    } else {
        $command = 'nohup ' . escapeshellarg($phpBinary) . ' ' . escapeshellarg($script) .
            ' ' . $importId . ' ' . ($waitAfterExistingBatch ? '1' : '0') . ' >/dev/null 2>&1 &';
    }

    $handle = @popen($command, 'r');
    if (!is_resource($handle)) {
        return false;
    }

    @pclose($handle);
    return true;
}
