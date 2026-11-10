#!/usr/bin/php -q
<?php
/**
 * AGI Script: Call ID'yi CDR userfield'a yaz
 * Asterisk'te arama başladığında çalışır ve Call ID'yi CDR'a kaydeder
 * 
 * Dialplan kullanımı:
 * exten => s,1,AGI(/var/www/html/asterisk/agi/set_cdr_userfield.php,${CALL_ID})
 */

// AGI protokolü - Asterisk'ten gelen environment değişkenlerini oku
$env = [];
while (!feof(STDIN)) {
    $line = trim(fgets(STDIN));
    if ($line === '') break;
    
    if (strpos($line, ':') !== false) {
        list($key, $value) = explode(':', $line, 2);
        $env[trim($key)] = trim($value);
    }
}

// AGI sınıfı
class AGI {
    public function verbose($msg, $level = 1) {
        echo "VERBOSE \"$msg\" $level\n";
    }
    
    public function setCDRUserField($value) {
        echo "SET VARIABLE CDR(userfield) \"$value\"\n";
        fgets(STDIN); // Response oku
    }
}

$agi = new AGI();

// Parametreleri al
$callId = isset($argv[1]) ? $argv[1] : null;

if (!$callId) {
    $agi->verbose("Hata: Call ID parametresi eksik", 1);
    exit(1);
}

// CDR userfield'a Call ID yaz
$agi->setCDRUserField($callId);
$agi->verbose("CDR userfield set edildi: Call ID = $callId", 2);

exit(0);
