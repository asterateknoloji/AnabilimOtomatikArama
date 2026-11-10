<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/plain; charset=utf-8');

require_once 'config.php';

try {
    // En son import ID'yi al
    $importId = $pdo->query('SELECT MAX(id) FROM ' . table('IMPORT_HISTORY'))->fetchColumn();
    
    echo "=== EN SON IMPORT ID: $importId ===\n\n";
    
    // Tüm kayıtların detayını göster
    $stmt = $pdo->prepare('
        SELECT id, PhoneNumber, Status, Locked, Aktif, CallTime,
               LastCallTime, CallCount, TryCallCount, SipCode, Durum,
               CASE 
                   WHEN CallTime IS NULL THEN "NULL"
                   WHEN CallTime <= NOW() THEN "GEÇTİ/ŞİMDİ"
                   ELSE "GELECEK"
               END as CallTimeStatus,
               TIMESTAMPDIFF(SECOND, NOW(), CallTime) as saniye_fark
        FROM ' . table('CALLS') . ' 
        WHERE ImportId = ?
        ORDER BY Status, id
    ');
    $stmt->execute([$importId]);
    
    echo "=== KAYITLARIN DETAYI ===\n";
    while ($row = $stmt->fetch()) {
        echo sprintf(
            "ID=%d  Phone=%s  Status=%d  Locked=%d  Aktif=%d\n",
            $row['id'],
            $row['PhoneNumber'],
            $row['Status'],
            $row['Locked'],
            $row['Aktif']
        );
        echo sprintf(
            "  CallTime: %s  (%s)",
            $row['CallTime'] ?? 'NULL',
            $row['CallTimeStatus']
        );
        if ($row['saniye_fark'] !== null) {
            echo sprintf(" [%+d saniye]", $row['saniye_fark']);
        }
        echo "\n";
        echo sprintf(
            "  LastCallTime=%s  CallCount=%d/%d  SipCode=%s  Durum=%s\n\n",
            $row['LastCallTime'] ?? 'NULL',
            $row['CallCount'],
            $row['TryCallCount'],
            $row['SipCode'] ?? 'NULL',
            $row['Durum'] ?? 'NULL'
        );
    }
    
    echo "\n=== START KOŞULU KONTROLÜ ===\n";
    $checkStmt = $pdo->prepare("
        SELECT COUNT(*) as ready_count
        FROM " . table('CALLS') . " 
        WHERE ImportId = ? 
        AND Aktif = 1 
        AND Status IN (0, 1)
        AND (CallTime IS NULL OR CallTime <= NOW())
    ");
    $checkStmt->execute([$importId]);
    $result = $checkStmt->fetch();
    
    echo "START edilebilecek kayıt sayısı: " . $result['ready_count'] . "\n";
    
} catch (Exception $e) {
    echo "❌ Hata: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
