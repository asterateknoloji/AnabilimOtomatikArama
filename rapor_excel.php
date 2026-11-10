<?php
require_once 'config.php';

// Filtreler
$projectId = $_GET['project_id'] ?? null;
$importId = $_GET['import_id'] ?? null;
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');

// Sorgu oluştur
$sql = "SELECT 
    c.*,
    p.ProjectName,
    CASE c.Status
        WHEN 1 THEN 'Beklemede'
        WHEN 2 THEN 'Aranıyor'
        WHEN 3 THEN 'Tamamlandı'
        WHEN 4 THEN 'Başarısız'
    END as StatusText,
    TIMESTAMPDIFF(SECOND, c.CallTime, c.EndCallTime) as Duration
FROM " . table('CALLS') . " c
LEFT JOIN " . table('PROJECTS') . " p ON c.ProjectId = p.id
WHERE c.RecordDate BETWEEN ? AND ?";

$params = [$startDate . ' 00:00:00', $endDate . ' 23:59:59'];

if ($projectId) {
    $sql .= " AND c.ProjectId = ?";
    $params[] = $projectId;
}

if ($importId) {
    $sql .= " AND c.ImportId = ?";
    $params[] = $importId;
}

$sql .= " ORDER BY c.RecordDate DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchAll();

// Excel başlıkları
header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="arama_raporu_' . date('Y-m-d_His') . '.xls"');
header('Pragma: no-cache');
header('Expires: 0');

// BOM ekle (Türkçe karakter desteği için)
echo "\xEF\xBB\xBF";
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; }
        th { background-color: #667eea; color: white; font-weight: bold; }
        .success { background-color: #d4edda; }
        .failed { background-color: #f8d7da; }
        .pending { background-color: #cfe2ff; }
        .calling { background-color: #fff3cd; }
    </style>
</head>
<body>
    <h2>Otomatik Arama Raporu</h2>
    <p>Tarih: <?= date('d.m.Y H:i') ?></p>
    <p>Dönem: <?= date('d.m.Y', strtotime($startDate)) ?> - <?= date('d.m.Y', strtotime($endDate)) ?></p>
    <?php if ($importId): ?>
    <p>Import: <?= htmlspecialchars($results[0]['ProjectName'] ?? '') ?></p>
    <?php endif; ?>
    
    <br>
    
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Proje</th>
                <th>Telefon</th>
                <th>Müşteri Adı</th>
                <th>Tutar</th>
                <th>Metin 1</th>
                <th>Metin 2</th>
                <th>Metin 3</th>
                <th>Kayıt Tarihi</th>
                <th>Arama Tarihi</th>
                <th>Cevaplama</th>
                <th>Bitiş</th>
                <th>Süre (sn)</th>
                <th>Tuş 1</th>
                <th>Tuş 2</th>
                <th>Tuş 3</th>
                <th>Transfer No</th>
                <th>Deneme</th>
                <th>Durum</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($results as $row): ?>
            <tr class="<?= strtolower($row['StatusText']) ?>">
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['ProjectName']) ?></td>
                <td><?= htmlspecialchars($row['PhoneNumber']) ?></td>
                <td><?= htmlspecialchars($row['Adi']) ?></td>
                <td><?= number_format($row['Tutar'], 2, ',', '.') ?></td>
                <td><?= htmlspecialchars($row['Metin1']) ?></td>
                <td><?= htmlspecialchars($row['Metin2']) ?></td>
                <td><?= htmlspecialchars($row['Metin3']) ?></td>
                <td><?= $row['RecordDate'] ? date('d.m.Y H:i', strtotime($row['RecordDate'])) : '' ?></td>
                <td><?= $row['CallTime'] ? date('d.m.Y H:i', strtotime($row['CallTime'])) : '' ?></td>
                <td><?= $row['PickupTime'] ? date('d.m.Y H:i:s', strtotime($row['PickupTime'])) : '' ?></td>
                <td><?= $row['EndCallTime'] ? date('d.m.Y H:i:s', strtotime($row['EndCallTime'])) : '' ?></td>
                <td><?= $row['Duration'] ?? 0 ?></td>
                <td><?= $row['tus1'] ? date('H:i:s', strtotime($row['tus1'])) : '' ?></td>
                <td><?= $row['tus2'] ? date('H:i:s', strtotime($row['tus2'])) : '' ?></td>
                <td><?= $row['tus3'] ? date('H:i:s', strtotime($row['tus3'])) : '' ?></td>
                <td><?= htmlspecialchars($row['TransferNo']) ?></td>
                <td><?= $row['CallCount'] ?> / <?= $row['TryCallCount'] ?></td>
                <td><?= $row['StatusText'] ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <br><br>
    
    <h3>İstatistik Özeti</h3>
    <table>
        <tr>
            <th>Toplam Arama</th>
            <td><?= count($results) ?></td>
        </tr>
        <tr>
            <th>Başarılı</th>
            <td><?= $importDetails['CompletedCount'] ?></td>
        </tr>
        <tr>
            <th>Başarısız</th>
            <td><?= $importDetails['FailedCount'] ?></td>
        </tr>
        <tr>
            <th>Beklemede</th>
            <td><?= $importDetails['PendingCount'] ?></td>
        </tr>
        <tr>
            <th>Başarı Oranı</th>
            <td><?= count($results) > 0 ? round($importDetails['CompletedCount'] / count($results) * 100, 2) : 0 ?>%</td>
        </tr>
    </table>
</body>
</html>
