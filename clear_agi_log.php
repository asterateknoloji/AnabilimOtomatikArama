<?php
/**
 * AGI Log Temizleyici
 */

session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    die(json_encode(['success' => false, 'message' => 'Login gerekli']));
}

header('Content-Type: application/json');

// SSH ile log dosyasını temizle
$host = '192.168.181.16';
$port = 45555;
$user = 'root';
$pass = 'KKAstera*2o21@.!';
$logFile = '/tmp/agi_call_end.log';

$command = "tools\\plink.exe -P $port -pw \"$pass\" -batch $user@$host \"echo '' > $logFile\"";
$output = shell_exec($command);

echo json_encode(['success' => true, 'message' => 'Log temizlendi']);
