<?php
require_once 'config.php';

echo "=== CLEANUP SCRIPT TEST ===\n\n";

// Test için bir kayıt oluştur (veya mevcut bir kaydı kullan)
$phone = '05307711714';

// Önce durumu göster
$stmt = $pdo->prepare("
    SELECT id, PhoneNumber, Adi, Status, CallCount, TryCallCount, 
           CallTime, LastCallTime, Locked, SipCode, Durum
    FROM " . table('CALLS') . "
    WHERE PhoneNumber LIKE ?
    ORDER BY id DESC
    LIMIT 1
");
$stmt->execute(["%{$phone}%"]);
$call = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$call) {
    echo "❌ {$phone} bulunamadı!\n";
    exit;
}

echo "ÖNCE:\n";
echo "ID: {$call['id']}\n";
echo "Ad: {$call['Adi']}\n";
echo "Status: {$call['Status']}\n";
echo "CallCount: {$call['CallCount']}/{$call['TryCallCount']}\n";
echo "Locked: {$call['Locked']}\n";
echo "SipCode: " . ($call['SipCode'] ?? 'NULL') . "\n";
echo "CallTime: {$call['CallTime']}\n";
echo "LastCallTime: " . ($call['LastCallTime'] ?? 'NULL') . "\n";
echo "Durum: " . ($call['Durum'] ?? 'NULL') . "\n\n";

// Şimdi cleanup script'in yaptığını simüle et
echo "CLEANUP SCRIPT ÇALIŞIYOR...\n\n";

// Cleanup script mantığı
$retryMinutes = 60;
$callId = $call['id'];

$checkStmt = $pdo->prepare("SELECT CallCount, TryCallCount FROM " . table('CALLS') . " WHERE id = ?");
$checkStmt->execute([$callId]);
$currentCall = $checkStmt->fetch(PDO::FETCH_ASSOC);

$newCallCount = $currentCall['CallCount'] + 1;
$maxReached = ($newCallCount >= $currentCall['TryCallCount']);
$newStatus = $maxReached ? 2 : 1;
$durumMsg = $maxReached 
    ? "Timeout ({$newCallCount}/{$currentCall['TryCallCount']}) - Max deneme" 
    : "Timeout ({$newCallCount}/{$currentCall['TryCallCount']}) - {$retryMinutes} dk sonra tekrar";

echo "Hesaplanan değerler:\n";
echo "  - NewCallCount: {$newCallCount}\n";
echo "  - MaxReached: " . ($maxReached ? 'EVET' : 'HAYIR') . "\n";
echo "  - NewStatus: {$newStatus}\n";
echo "  - Durum: {$durumMsg}\n";
echo "  - Yeni CallTime: " . date('Y-m-d H:i:s', strtotime("+{$retryMinutes} minutes")) . "\n\n";

echo "SQL KOMUTLARINI GÖSTER (Çalıştırma):\n\n";

$sql = "
UPDATE " . table('CALLS') . "
SET 
    CallCount = {$newCallCount},
    Status = {$newStatus},
    SipCode = 503,
    EndCallTime = NOW(),
    CallTime = DATE_ADD(NOW(), INTERVAL {$retryMinutes} MINUTE),
    LastCallTime = NOW(),
    Locked = 0,
    Durum = '{$durumMsg}'
WHERE id = {$callId}
";

echo $sql . "\n\n";

echo "Bu SQL'i çalıştırmak ister misiniz? (y/n): ";
