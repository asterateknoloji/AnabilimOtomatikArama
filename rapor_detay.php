<?php
require_once 'config.php';
require_once 'includes/ImportManager.php';

$importManager = new ImportManager($pdo);

$importId = $_GET['import_id'] ?? null;

if (!$importId) {
    die('Import ID gerekli');
}

$importDetails = $importManager->getImportDetails($importId);

// Detay kayıtları
$stmt = $pdo->prepare("
    SELECT * FROM " . table('CALLS') . " 
    WHERE ImportId = ?
    ORDER BY RecordDate DESC
");
$stmt->execute([$importId]);
$calls = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Detay Rapor</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 1600px; margin: 0 auto; }
        .header {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 30px;
        }
        h1 { color: #667eea; font-size: 32px; margin-bottom: 10px; }
        .nav-links { display: flex; gap: 15px; margin-top: 20px; }
        .nav-links a {
            padding: 10px 20px;
            background: #f0f0f0;
            border-radius: 8px;
            text-decoration: none;
            color: #333;
        }
        .nav-links a:hover { background: #667eea; color: white; }
        .card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 30px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 15px;
            margin-top: 20px;
        }
        .stat-item {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            border-left: 4px solid #667eea;
        }
        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #667eea;
        }
        .stat-label {
            font-size: 13px;
            color: #666;
            margin-top: 8px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 13px;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        th {
            background: #f8f9fa;
            color: #667eea;
            font-weight: 600;
            position: sticky;
            top: 0;
        }
        tr:hover { background: #f8f9ff; }
        .badge {
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
        }
        .badge-pending { background: #cfe2ff; color: #084298; }
        .badge-calling { background: #fff3cd; color: #856404; }
        .badge-completed { background: #d4edda; color: #155724; }
        .badge-failed { background: #f8d7da; color: #721c24; }
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
        }
        .btn-success {
            background: #28a745;
            color: white;
        }
        .btn-success:hover {
            background: #218838;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 Detay Rapor</h1>
            <p class="subtitle">
                <strong>Proje:</strong> <?= htmlspecialchars($importDetails['ProjectName']) ?> | 
                <strong>Dosya:</strong> <?= htmlspecialchars($importDetails['FileName']) ?> |
                <strong>İçe Aktarma:</strong> <?= date('d.m.Y H:i', strtotime($importDetails['ImportDate'])) ?>
            </p>
            
            <div class="nav-links">
                <a href="raporlar.php">← Raporlara Dön</a>
                <a href="rapor_excel.php?import_id=<?= $importId ?>" class="btn-success">📥 Excel İndir</a>
            </div>
        </div>

        <!-- Özet İstatistikler -->
        <div class="card">
            <h3 style="color: #667eea; margin-bottom: 15px;">📊 Özet İstatistikler</h3>
            
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-value"><?= $importDetails['CallCount'] ?></div>
                    <div class="stat-label">Toplam Kayıt</div>
                </div>
                <div class="stat-item" style="border-left-color: #17a2b8;">
                    <div class="stat-value" style="color: #17a2b8;"><?= $importDetails['PendingCount'] ?></div>
                    <div class="stat-label">Beklemede</div>
                </div>
                <div class="stat-item" style="border-left-color: #ffc107;">
                    <div class="stat-value" style="color: #ffc107;"><?= $importDetails['CallingCount'] ?></div>
                    <div class="stat-label">Aranıyor</div>
                </div>
                <div class="stat-item" style="border-left-color: #28a745;">
                    <div class="stat-value" style="color: #28a745;"><?= $importDetails['CompletedCount'] ?></div>
                    <div class="stat-label">Başarılı</div>
                </div>
                <div class="stat-item" style="border-left-color: #dc3545;">
                    <div class="stat-value" style="color: #dc3545;"><?= $importDetails['FailedCount'] ?></div>
                    <div class="stat-label">Başarısız</div>
                </div>
                <div class="stat-item" style="border-left-color: #6c757d;">
                    <div class="stat-value" style="color: #6c757d;">
                        <?= $importDetails['CallCount'] > 0 ? round($importDetails['CompletedCount'] / $importDetails['CallCount'] * 100, 1) : 0 ?>%
                    </div>
                    <div class="stat-label">Başarı Oranı</div>
                </div>
            </div>
        </div>

        <!-- Detay Tablo -->
        <div class="card">
            <h3 style="color: #667eea; margin-bottom: 20px;">📞 Arama Detayları</h3>
            
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Telefon</th>
                            <th>Müşteri</th>
                            <th>Tutar</th>
                            <th>Arama Tarihi</th>
                            <th>Cevap</th>
                            <th>Bitiş</th>
                            <th>Süre(s)</th>
                            <th>Tuş 1</th>
                            <th>Tuş 2</th>
                            <th>Transfer</th>
                            <th>Deneme</th>
                            <th>Durum</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($calls as $call): ?>
                        <?php
                        $duration = 0;
                        if ($call['CallTime'] && $call['EndCallTime']) {
                            $duration = strtotime($call['EndCallTime']) - strtotime($call['CallTime']);
                        }
                        
                        $statusLabels = [
                            1 => 'Beklemede',
                            2 => 'Aranıyor',
                            3 => 'Tamamlandı',
                            4 => 'Başarısız'
                        ];
                        $statusClass = [
                            1 => 'pending',
                            2 => 'calling',
                            3 => 'completed',
                            4 => 'failed'
                        ];
                        ?>
                        <tr>
                            <td><?= $call['id'] ?></td>
                            <td><?= htmlspecialchars($call['PhoneNumber']) ?></td>
                            <td><?= htmlspecialchars($call['Adi']) ?></td>
                            <td><?= number_format($call['Tutar'], 2, ',', '.') ?></td>
                            <td><?= $call['CallTime'] ? date('d.m H:i', strtotime($call['CallTime'])) : '-' ?></td>
                            <td><?= $call['PickupTime'] ? date('H:i:s', strtotime($call['PickupTime'])) : '-' ?></td>
                            <td><?= $call['EndCallTime'] ? date('H:i:s', strtotime($call['EndCallTime'])) : '-' ?></td>
                            <td><?= $duration > 0 ? $duration : '-' ?></td>
                            <td><?= $call['tus1'] ? '✅' : '-' ?></td>
                            <td><?= $call['tus2'] ? '✅' : '-' ?></td>
                            <td><?= $call['TransferNo'] ? '📞' : '-' ?></td>
                            <td><?= $call['CallCount'] ?> / <?= $call['TryCallCount'] ?></td>
                            <td>
                                <span class="badge badge-<?= $statusClass[$call['Status']] ?>">
                                    <?= $statusLabels[$call['Status']] ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
