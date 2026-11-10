#!/usr/bin/php -q
<?php
/**
 * Asterisk AGI Script - DTMF tuş güncelle
 * PHP 5.4+ uyumlu
 */

// Log dosyası
$logFile = '/tmp/agi_dtmf.log';
file_put_contents($logFile, date('Y-m-d H:i:s') . " - SCRIPT STARTED\n", FILE_APPEND);

// AGI protokolü - Asterisk'ten gelen environment değişkenlerini oku
while (!feof(STDIN)) {
    $line = trim(fgets(STDIN));
    if ($line === '') break;
}
file_put_contents($logFile, date('Y-m-d H:i:s') . " - AGI ENV READ\n", FILE_APPEND);

// Veritabanı bağlantısı (.env'den oku)
$envFile = '/var/www/html/.env'; // Asterisk sunucusundaki .env yolu
if (!file_exists($envFile)) {
    $envFile = __DIR__ . '/../../.env'; // Alternatif yol
}

// Varsayılan değerler
$host = 'localhost';
$dbname = 'ANABILIMNEW';
$user = 'astera';
$pass = 'astera2012';

// .env dosyasını oku (varsa)
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            $value = trim($value, '"\'');
            
            if ($key === 'DB_HOST') $host = $value;
            elseif ($key === 'DB_NAME') $dbname = $value;
            elseif ($key === 'DB_USER') $user = $value;
            elseif ($key === 'DB_PASS') $pass = $value;
        }
    }
}

// AGI sınıfı (basit)
class AGI {
    public function verbose($msg, $level = 1) {
        echo "VERBOSE \"$msg\" $level\n";
    }
}

$agi = new AGI();

// Parametreleri al - Asterisk AGI format
// AGI environment değişkenlerinden al
$env = array();
foreach ($_ENV as $key => $value) {
    if (strpos($key, 'agi_') === 0) {
        $env[$key] = $value;
    }
}

// argv'den al
$callId = isset($argv[1]) ? $argv[1] : null;
$key = isset($argv[2]) ? $argv[2] : null;

file_put_contents($logFile, date('Y-m-d H:i:s') . " - RAW ARGV: " . print_r($argv, true) . "\n", FILE_APPEND);
file_put_contents($logFile, date('Y-m-d H:i:s') . " - PARAMS: CallID=$callId, Key=$key\n", FILE_APPEND);

if (!$callId || !$key) {
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - ERROR: Missing params\n", FILE_APPEND);
    $agi->verbose("Hata: Eksik parametreler", 1);
    exit(1);
}

try {
    $agi->verbose("DEBUG: Baslangic - CallID: $callId, Key: $key", 2);
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - After verbose\n", FILE_APPEND);
    
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
    
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - PDO connected\n", FILE_APPEND);
    $agi->verbose("DEBUG: PDO baglantisi basarili", 2);
    
    // Önce mevcut tuşlamaları, Durum ve ImportId'yi kontrol et
    $checkStmt = $pdo->prepare("SELECT tus1, tus2, tus3, Durum, ImportId FROM {$dbname}.CALLS WHERE id = ?");
    $checkStmt->execute(array($callId));
    $existingKeys = $checkStmt->fetch(PDO::FETCH_ASSOC);
    $checkStmt->closeCursor();
    
    // Durum mesajını hazırla - mevcut duruma ekleme yap
    $durumMesaji = trim($existingKeys['Durum']);
    
    // Yeni tuşlama mesajı
    $tuslamaMesaji = "Tuş " . $key . " basıldı";
    
    // Yeni tuşlamayı ekle
    if (!empty($durumMesaji)) {
        // Zaten mesaj var, ekle
        $durumMesaji .= ", " . $tuslamaMesaji;
    } else {
        // İlk mesaj
        $durumMesaji = $tuslamaMesaji;
    }
    
    // Transfer numarasını şablondan al
    $transferNo = null;
    if ($existingKeys['ImportId']) {
        // Import'un TemplateId'sini al
        $importStmt = $pdo->prepare("SELECT TemplateId FROM {$dbname}.IMPORT_HISTORY WHERE id = ?");
        $importStmt->execute(array($existingKeys['ImportId']));
        $import = $importStmt->fetch(PDO::FETCH_ASSOC);
        $importStmt->closeCursor();
        
        if ($import && $import['TemplateId']) {
            // Template'den transfer numarasını al
            $transferField = 'Key' . $key . 'TransferNumber';
            $templateStmt = $pdo->prepare("SELECT Key1TransferNumber, Key2TransferNumber FROM {$dbname}.MESSAGE_TEMPLATES WHERE id = ?");
            $templateStmt->execute(array($import['TemplateId']));
            $template = $templateStmt->fetch(PDO::FETCH_ASSOC);
            $templateStmt->closeCursor();
            
            if ($template) {
                if ($key == '1' && !empty($template['Key1TransferNumber'])) {
                    $transferNo = $template['Key1TransferNumber'];
                } elseif ($key == '2' && !empty($template['Key2TransferNumber'])) {
                    $transferNo = $template['Key2TransferNumber'];
                }
            }
        }
    }
    
    $agi->verbose("DEBUG: Transfer numarası: " . ($transferNo ? $transferNo : 'YOK'), 2);
    
    // Tuş bilgisini güncelle - tuş numarası, Status=2 (Arandı), SipCode=200 (Başarılı)
    $tusField = "tus" . $key;
    
    $sql = "UPDATE {$dbname}.CALLS 
            SET $tusField = ?, 
                Status = 2,
                SipCode = 200,
                EndCallTime = NOW(),
                Locked = 0,
                Durum = ?,
                TransferNo = ?
            WHERE id = ?";
    
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - SQL: $sql\n", FILE_APPEND);
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Params: Key=$key, Durum=$durumMesaji, TransferNo=$transferNo, CallID=$callId\n", FILE_APPEND);
    
    $agi->verbose("DEBUG: SQL: $sql", 2);
    $agi->verbose("DEBUG: Tuş $key - Durum: $durumMesaji - TransferNo: $transferNo - SipCode: 200 - CallID $callId", 2);
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute(array($key, $durumMesaji, $transferNo, $callId));
    $affected = $stmt->rowCount();
    
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Result: $result, Affected: $affected\n", FILE_APPEND);
    
    $agi->verbose("DEBUG: SQL Result: " . ($result ? 'true' : 'false') . ", Affected: $affected", 2);
    $agi->verbose("Call ID $callId tus $key basıldı", 2);
    
} catch (Exception $e) {
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - ERROR: " . $e->getMessage() . " at line " . $e->getLine() . "\n", FILE_APPEND);
    $agi->verbose("HATA: " . $e->getMessage(), 1);
    $agi->verbose("HATA Line: " . $e->getLine(), 1);
    exit(1);
}

file_put_contents($logFile, date('Y-m-d H:i:s') . " - END\n", FILE_APPEND);

exit(0);
