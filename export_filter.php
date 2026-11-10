<?php
/**
 * Filtrelenmiş Excel Export - Auth YOK (Test)
 */

// session_start(); // KAPALI
require_once 'config.php';

if (!isset($_GET['import_id'])) {
    die('❌ Import ID belirtilmedi');
}

$importId = (int)$_GET['import_id'];
$filterType = $_GET['filter'] ?? 'all'; // all, aktif, pasif, basarili, basarisiz, bekleyen, aranan, aranmayan

try {
    // Import bilgilerini al
    $importStmt = $pdo->prepare("
        SELECT ih.OriginalFileName, p.ProjectName 
        FROM " . table('IMPORT_HISTORY') . " ih
        LEFT JOIN " . table('PROJECTS') . " p ON ih.ProjectId = p.id
        WHERE ih.id = ?
    ");
    $importStmt->execute([$importId]);
    $import = $importStmt->fetch();
    
    if (!$import) {
        die('❌ Import kaydı bulunamadı');
    }
    
    // Filtre koşulu
    $whereConditions = ["ImportId = ?"];
    $params = [$importId];
    $filterName = 'Tüm Kayıtlar';
    
    switch ($filterType) {
        case 'aktif':
            $whereConditions[] = "Aktif = 1";
            $filterName = 'Aktif Kayıtlar';
            break;
        case 'pasif':
            $whereConditions[] = "Aktif = 0";
            $filterName = 'Pasif Kayıtlar';
            break;
        case 'basarili':
            $whereConditions[] = "Status = 2";
            $filterName = 'Başarılı Aramalar';
            break;
        case 'basarisiz':
            $whereConditions[] = "Status IN (3, 5, 6)";
            $filterName = 'Başarısız Aramalar';
            break;
        case 'bekleyen':
            $whereConditions[] = "Status = 1";
            $filterName = 'Bekleyen Kayıtlar';
            break;
        case 'aranan':
            $whereConditions[] = "CallCount > 0";
            $filterName = 'Aranan Kayıtlar';
            break;
        case 'aranmayan':
            $whereConditions[] = "CallCount = 0";
            $filterName = 'Aranmayan Kayıtlar';
            break;
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    // Kayıtları al
    $callsStmt = $pdo->prepare("
        SELECT 
            PhoneNumber,
            Adi,
            
            Metin1,
            Metin2,
            Metin3,
            CASE 
                WHEN Status = 1 THEN 'Bekliyor'
                WHEN Status = 2 THEN 'Başarılı'
                WHEN Status = 3 THEN 'Başarısız'
                WHEN Status = 4 THEN 'Arıyor'
                WHEN Status = 5 THEN 'Cevap Yok'
                WHEN Status = 6 THEN 'Meşgul'
                ELSE 'Bilinmiyor'
            END as StatusText,
            CASE WHEN Aktif = 1 THEN 'Aktif' ELSE 'Pasif' END as AktifText,
            CallCount,
            DATE_FORMAT(RecordDate, '%d.%m.%Y %H:%i') as RecordDateFormatted,
            DATE_FORMAT(CallTime, '%d.%m.%Y %H:%i') as CallTimeFormatted,
            DATE_FORMAT(LastCallTime, '%d.%m.%Y %H:%i') as LastCallTimeFormatted,
            SipCode,
            Durum,
            tus1,
            tus2
        FROM " . table('CALLS') . "
        WHERE $whereClause
        ORDER BY id ASC
    ");
    $callsStmt->execute($params);
    $calls = $callsStmt->fetchAll();
    
    if (empty($calls)) {
        die('❌ Seçilen filtreye uygun kayıt bulunamadı');
    }
    
    // Dosya adı
    $cleanName = preg_replace('/[^A-Za-z0-9_\-]/', '_', pathinfo($import['OriginalFileName'], PATHINFO_FILENAME));
    $fileName = $cleanName . '_' . strtolower(str_replace(' ', '_', $filterName)) . '_' . date('YmdHis') . '.xls';
    
    // Excel headers
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // UTF-8 BOM
    echo "\xEF\xBB\xBF";
    
} catch (Exception $e) {
    die('❌ HATA: ' . $e->getMessage());
}
?>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #000; padding: 8px; }
        th { background-color: #4CAF50; color: white; font-weight: bold; }
    </style>
</head>
<body>
    <h2>📊 <?= htmlspecialchars($import['OriginalFileName']) ?> - <?= $filterName ?></h2>
    <p><strong>Proje:</strong> <?= htmlspecialchars($import['ProjectName'] ?? '-') ?> | <strong>Filtre:</strong> <?= $filterName ?> | <strong>Toplam:</strong> <?= count($calls) ?> kayıt | <strong>Export:</strong> <?= date('d.m.Y H:i') ?></p>
    
    <table>
        <thead>
            <tr>
                <th>Sıra</th>
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
            <?php $index = 1; foreach ($calls as $call): ?>
            <tr>
                <td><?= $index++ ?></td>
                <td><?= htmlspecialchars($call['PhoneNumber']) ?></td>
                <td><?= htmlspecialchars($call['Adi']) ?></td>
                <td><?= htmlspecialchars($call['Metin1']) ?></td>
                <td><?= htmlspecialchars($call['Metin2']) ?></td>
                <td><?= htmlspecialchars($call['Metin3']) ?></td>
                <td><?= $call['StatusText'] ?></td>
                <td><?= $call['AktifText'] ?></td>
                <td><?= $call['CallCount'] ?></td>
                <td><?= $call['RecordDateFormatted'] ?? '-' ?></td>
                <td><?= $call['CallTimeFormatted'] ?? '-' ?></td>
                <td><?= $call['LastCallTimeFormatted'] ?? '-' ?></td>
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
