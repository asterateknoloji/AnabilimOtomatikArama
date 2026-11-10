<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Bu dosya yalnızca komut satırından çalıştırılır.');
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/SshHelper.php';

$ssh = new SshHelper(
    EnvLoader::get('ASTERISK_SSH_HOST'),
    EnvLoader::get('ASTERISK_SSH_PORT', 22),
    EnvLoader::get('ASTERISK_SSH_USER'),
    EnvLoader::get('ASTERISK_SSH_PASS')
);

$files = [
    'update_call_status.php',
    'update_call_end.php',
];

foreach ($files as $file) {
    $local = __DIR__ . '/asterisk/agi/' . $file;
    $remote = '/var/lib/asterisk/agi-bin/' . $file;
    $backup = $remote . '.backup.' . date('YmdHis');

    $ssh->exec("cp " . escapeshellarg($remote) . " " . escapeshellarg($backup) . " 2>/dev/null || true");
    $ssh->sendFile($local, $remote);
    $ssh->exec("chmod +x " . escapeshellarg($remote));
    $ssh->exec("chown asterisk:asterisk " . escapeshellarg($remote));

    echo $file . " yüklendi.\n";
}
