<?php
require_once __DIR__ . '/config.php';

$call_id = 26773;

echo "Call 26773 Full Debug:\n";
echo "======================\n\n";

// 1. Call bilgisi
$stmt = $pdo->prepare("SELECT * FROM CALLS WHERE id = ?");
$stmt->execute([$call_id]);
$call = $stmt->fetch();

echo "1. CALL BİLGİSİ:\n";
echo "   ID: {$call['id']}\n";
echo "   Phone: {$call['PhoneNumber']}\n";
echo "   ImportId: {$call['ImportId']}\n";
echo "   ProjectId: {$call['ProjectId']}\n";
echo "   Status: {$call['Status']}\n\n";

// 2. PROJECTS tablosu var mı?
try {
    $stmt = $pdo->prepare("SELECT * FROM PROJECTS WHERE id = ?");
    $stmt->execute([$call['ProjectId']]);
    $project = $stmt->fetch();
    
    if ($project) {
        echo "2. PROJECT BİLGİSİ:\n";
        print_r($project);
        echo "\n";
    } else {
        echo "2. PROJECT bulunamadı (ID: {$call['ProjectId']})\n\n";
    }
} catch (Exception $e) {
    echo "2. PROJECTS tablosu yok veya erişilemiyor\n\n";
}

// 3. Son aramalara bak
echo "3. SON ARAMALAR (Template bilgisi ile):\n";
$stmt = $pdo->query("SELECT id, PhoneNumber, ProjectId, ImportId, Status FROM CALLS WHERE id >= 26770 ORDER BY id");
while ($row = $stmt->fetch()) {
    echo "   Call {$row['id']}: ProjectId={$row['ProjectId']}, ImportId={$row['ImportId']}, Status={$row['Status']}\n";
}
