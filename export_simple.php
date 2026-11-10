<?php
/**
 * Basit Excel Export
 */

session_start();
require_once 'config.php';
require_once 'includes/auth.php';

// Yetki kontrolü
if (!isLoggedIn()) {
    die('Yetkisiz erişim');
}

if (!hasPermission('report.view')) {
    die('Bu işlem için yetkiniz yok');
}

if (!isset($_GET['import_id'])) {
    die('Import ID belirtilmedi');
}

$importId = (int)$_GET['import_id'];

// Import bilgilerini al
try {
    $importStmt = $pdo->prepare("
        SELECT ih.*, p.ProjectName 
        FROM " . table('IMPORT_HISTORY') . " ih
        LEFT JOIN " . table('PROJECTS') . " p ON ih.ProjectId = p.id
        WHERE ih.id = ?
    ");
    $importStmt->execute([$importId]);
    $import = $importStmt->fetch();
    
    if (!$import) {
        die('Import kaydı bulunamadı');
    }
    
    // Kayıtları al
    $callsStmt = $pdo->prepare("
        SELECT 
            PhoneNumber,
            Adi,
            
            Metin1,
            Metin2,
            Metin3,
            Status,
            CallCount,
            Aktif,
            RecordDate,
            CallTime,
            LastCallTime,
            SipCode,
            Durum,
            tus1,
            tus2
        FROM " . table('CALLS') . "
        WHERE ImportId = ?
        ORDER BY id ASC
    ");
    $callsStmt->execute([$importId]);
    $calls = $callsStmt->fetchAll();
    
    if (empty($calls)) {
        die('Bu import için kayıt bulunamadı');
    }
    
    // Dosya adını temizle
    $fileName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $import['OriginalFileName']);
    $fileName = pathinfo($fileName, PATHINFO_FILENAME);
    $fileName .= '_export_' . date('YmdHis') . '.xls';
    
    // Excel header
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // UTF-8 BOM
    echo "\xEF\xBB\xBF";
    
} catch (Exception $e) {
    die('Hata: ' . $e->getMessage());
}

// Status kodunu metne çevir
function getStatusText($status) {
    switch ($status) {
        case 1: return 'Bekliyor';
        case 2: return 'Başarılı';
        case 3: return 'Başarısız';
        case 4: return 'Arıyor';
        case 5: return 'Cevap Yok';
        case 6: return 'Meşgul';
        default: return 'Bilinmiyor';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #000; padding: 5px; text-align: left; }
        th { background-color: #4CAF50; color: white; font-weight: bold; }
    </style>
</head>
<body>
    <h2><?= htmlspecialchars($import['OriginalFileName']) ?> - Export</h2>
    <p>Toplam: <?= count($calls) ?> kayıt | Export: <?= date('d.m.Y H:i') ?></p>
    
    <table>
        <thead>
            <tr>
                <th>Telefon</th>
                <th>Ad Soyad</th>
                                <th>Metin 1</th>
                <th>Metin 2</th>
                <th>Metin 3</th>
                <th>Durum</th>
                <th>Aktif</th>
                <th>Arama Sayısı</th>
                <th>Kayıt Tarihi</th>
                <th>Arama Zamanı</th>
                <th>Son Arama</th>
                <th>SIP Kod</th>
                <th>Sonuç</th>
                <th>Tuş 1</th>
                <th>Tuş 2</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($calls as $call): ?>
            <tr>
                <td><?= htmlspecialchars($call['PhoneNumber']) ?></td>
                <td><?= htmlspecialchars($call['Adi']) ?></td>
                                <td><?= htmlspecialchars($call['Metin1']) ?></td>
                <td><?= htmlspecialchars($call['Metin2']) ?></td>
                <td><?= htmlspecialchars($call['Metin3']) ?></td>
                <td><?= getStatusText($call['Status']) ?></td>
                <td><?= $call['Aktif'] == 1 ? 'Aktif' : 'Pasif' ?></td>
                <td><?= $call['CallCount'] ?></td>
                <td><?= $call['RecordDate'] ? date('d.m.Y H:i', strtotime($call['RecordDate'])) : '-' ?></td>
                <td><?= $call['CallTime'] ? date('d.m.Y H:i', strtotime($call['CallTime'])) : '-' ?></td>
                <td><?= $call['LastCallTime'] ? date('d.m.Y H:i', strtotime($call['LastCallTime'])) : '-' ?></td>
                <td><?= htmlspecialchars($call['SipCode']) ?></td>
                <td><?= htmlspecialchars($call['Durum']) ?></td>
                <td><?= htmlspecialchars($call['tus1']) ?></td>
                <td><?= htmlspecialchars($call['tus2']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
