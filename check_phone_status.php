<?php
require_once 'config.php';

$phone = '05307711714';

echo "=== TELEFON DURUMU: {$phone} ===\n\n";

$stmt = $pdo->prepare("
    SELECT 
        id,
        PhoneNumber,
        Adi,
        Status,
        Locked,
        CallCount,
        TryCallCount,
        CallTime,
        LastCallTime,
        RecordDate,
        SipCode,
        Durum,
        ImportId,
        TIMESTAMPDIFF(SECOND, LastCallTime, NOW()) as SecondsSinceLastCall,
        TIMESTAMPDIFF(SECOND, NOW(), CallTime) as SecondsUntilNextCall
    FROM " . table('CALLS') . "
    WHERE PhoneNumber LIKE ?
    ORDER BY id DESC
    LIMIT 5
");

$stmt->execute(["%{$phone}%"]);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: {$row['id']}\n";
    echo "Ad: {$row['Adi']}\n";
    echo "Status: {$row['Status']} (" . ($row['Status'] == 1 ? 'Bekliyor' : 'Bitti') . ")\n";
    echo "Locked: {$row['Locked']} (" . ($row['Locked'] == 1 ? 'Kilitli' : 'Açık') . ")\n";
    echo "CallCount: {$row['CallCount']}/{$row['TryCallCount']}\n";
    echo "SipCode: " . ($row['SipCode'] ?? '-') . "\n";
    echo "Durum: " . ($row['Durum'] ?? '-') . "\n";
    echo "CallTime: {$row['CallTime']}\n";
    echo "LastCallTime: {$row['LastCallTime']}\n";
    echo "RecordDate: {$row['RecordDate']}\n\n";
    
    $minSinceLastCall = round($row['SecondsSinceLastCall'] / 60, 1);
    echo "SON ARAMADAN BERİ: {$row['SecondsSinceLastCall']} saniye ({$minSinceLastCall} dakika)\n";
    
    if ($row['SecondsUntilNextCall'] > 0) {
        $minUntilNextCall = round($row['SecondsUntilNextCall'] / 60, 1);
        echo "SONRAKİ ARAMAYA: {$row['SecondsUntilNextCall']} saniye ({$minUntilNextCall} dakika)\n";
    } else {
        echo "SONRAKİ ARAMAYA: ZAMANLANMIŞ ZAMAN GEÇTİ! ({$row['SecondsUntilNextCall']} saniye)\n";
    }
    
    echo "\n" . str_repeat('-', 60) . "\n\n";
}

// Import ayarlarını kontrol et
echo "IMPORT AYARLARI:\n";
$stmt = $pdo->query("
    SELECT DISTINCT
        i.id,
        i.ImportFileName,
        i.RetryCount,
        i.RetryIntervalMinutes
    FROM " . table('CALLS') . " c
    JOIN " . table('IMPORT_HISTORY') . " i ON c.ImportId = i.id
    WHERE c.PhoneNumber LIKE '%{$phone}%'
");

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "Import ID: {$row['id']}\n";
    echo "Dosya: {$row['ImportFileName']}\n";
    echo "RetryCount: {$row['RetryCount']}\n";
    echo "RetryInterval: {$row['RetryIntervalMinutes']} dakika\n\n";
}
