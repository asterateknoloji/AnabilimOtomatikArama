<?php
/**
 * FastAGI Sunucusu
 * Asterisk AGI isteklerini HTTP üzerinden karşılar
 * 
 * Kullanım:
 * php -S localhost:4573 fastagi_server.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Request URI'yi al
$requestUri = $_SERVER['REQUEST_URI'];
$scriptName = basename(parse_url($requestUri, PHP_URL_PATH));
$query = parse_url($requestUri, PHP_URL_QUERY);

// Query string'i parse et
parse_str($query, $params);

// Log
$logFile = __DIR__ . '/../../logs/fastagi_' . date('Y-m-d') . '.log';
$logMessage = date('Y-m-d H:i:s') . " - " . $scriptName . " - " . json_encode($params) . "\n";
file_put_contents($logFile, $logMessage, FILE_APPEND);

// AGI Environment okuma (gerçek AGI için gerekli)
if (php_sapi_name() === 'cli') {
    $agiEnv = [];
    while (!feof(STDIN)) {
        $line = trim(fgets(STDIN));
        if ($line === '') break;
        
        if (strpos($line, ':') !== false) {
            list($key, $value) = explode(':', $line, 2);
            $agiEnv[trim($key)] = trim($value);
        }
    }
} else {
    // HTTP üzerinden çağrı (FastAGI simülasyonu)
    $agiEnv = [
        'agi_request' => $scriptName,
        'agi_channel' => $_GET['channel'] ?? 'Unknown',
        'agi_language' => 'tr',
        'agi_type' => 'SIP',
        'agi_uniqueid' => $_GET['uniqueid'] ?? uniqid(),
        'agi_callerid' => $_GET['callerid'] ?? '',
        'agi_calleridname' => $_GET['calleridname'] ?? '',
        'agi_callingpres' => '0',
        'agi_callingani2' => '0',
        'agi_callington' => '0',
        'agi_callingtns' => '0',
        'agi_dnid' => $_GET['dnid'] ?? '',
        'agi_rdnis' => '',
        'agi_context' => $_GET['context'] ?? 'auto-calls',
        'agi_extension' => $_GET['extension'] ?? 's',
        'agi_priority' => $_GET['priority'] ?? '1',
        'agi_enhanced' => '0.0',
        'agi_accountcode' => '',
        'agi_threadid' => getmypid(),
    ];
}

// Basit AGI Response sınıfı
class AGIResponse
{
    public static function success($message = '')
    {
        echo "200 result=1 {$message}\n";
    }
    
    public static function failure($message = '')
    {
        echo "200 result=0 {$message}\n";
    }
    
    public static function verbose($message, $level = 1)
    {
        echo "VERBOSE \"{$message}\" {$level}\n";
    }
}

// Script routing
try {
    require_once __DIR__ . '/../../config.php';
    require_once __DIR__ . '/../../includes/AutoCallManager.php';
    
    $manager = new AutoCallManager($pdo);
    
    switch ($scriptName) {
        case 'update_call_status.php':
            $callId = $params['call_id'] ?? null;
            $status = $params['status'] ?? null;
            
            if (!$callId || !$status) {
                AGIResponse::failure('Missing parameters');
                break;
            }
            
            $statusMap = [
                'answered' => AutoCallManager::STATUS_CALLING,
                'noanswer' => AutoCallManager::STATUS_FAILED,
                'busy' => AutoCallManager::STATUS_FAILED,
                'congestion' => AutoCallManager::STATUS_FAILED
            ];
            
            $statusCode = $statusMap[$status] ?? AutoCallManager::STATUS_FAILED;
            
            $additionalData = [];
            if ($status === 'answered') {
                $additionalData['PickupTime'] = date('Y-m-d H:i:s');
            }
            
            $manager->updateCallStatus($callId, $statusCode, $additionalData);
            
            AGIResponse::success("Status updated: {$status}");
            AGIResponse::verbose("Call ID {$callId} status updated to {$status}", 2);
            break;
            
        case 'update_call_dtmf.php':
            $callId = $params['call_id'] ?? null;
            $key = $params['key'] ?? null;
            
            if (!$callId || !$key) {
                AGIResponse::failure('Missing parameters');
                break;
            }
            
            $tusField = "tus{$key}";
            $timestamp = date('Y-m-d H:i:s');
            
            $additionalData = [
                $tusField => $timestamp
            ];
            
            if ($key == '2') {
                $additionalData['TransferNo'] = 'OPERATOR_QUEUE';
                $manager->updateCallStatus($callId, AutoCallManager::STATUS_COMPLETED, $additionalData);
            } else {
                $stmt = $pdo->prepare("
                    UPDATE " . table('CALLS') . " 
                    SET {$tusField} = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$timestamp, $callId]);
            }
            
            AGIResponse::success("DTMF recorded: {$key}");
            AGIResponse::verbose("Call ID {$callId} key {$key} recorded", 2);
            break;
            
        case 'update_call_end.php':
            $callId = $params['call_id'] ?? null;
            $duration = $params['duration'] ?? 0;
            
            if (!$callId) {
                AGIResponse::failure('Missing call_id');
                break;
            }
            
            $additionalData = [
                'EndCallTime' => date('Y-m-d H:i:s')
            ];
            
            $status = ($duration < 10) 
                ? AutoCallManager::STATUS_FAILED 
                : AutoCallManager::STATUS_COMPLETED;
            
            $manager->updateCallStatus($callId, $status, $additionalData);
            
            AGIResponse::success("Call ended");
            AGIResponse::verbose("Call ID {$callId} ended. Duration: {$duration}s", 2);
            break;
            
        default:
            AGIResponse::failure("Unknown script: {$scriptName}");
            break;
    }
    
} catch (Exception $e) {
    AGIResponse::failure($e->getMessage());
    AGIResponse::verbose("Error: " . $e->getMessage(), 1);
    
    // Log error
    $errorLog = date('Y-m-d H:i:s') . " - ERROR: " . $e->getMessage() . "\n";
    file_put_contents($logFile, $errorLog, FILE_APPEND);
}
