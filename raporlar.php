<?php
require_once 'config.php';
require_once 'includes/ProjectManager.php';
require_once 'includes/ImportManager.php';

$projectManager = new ProjectManager($pdo);
$importManager = new ImportManager($pdo);

// Filtreler
$projectId = $_GET['project_id'] ?? null;
$importId = $_GET['import_id'] ?? null;
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');

// Genel istatistikler
$statsQuery = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN Status = 1 THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN Status = 2 THEN 1 ELSE 0 END) as calling,
    SUM(CASE WHEN Status = 3 THEN 1 ELSE 0 END) as completed,
    SUM(CASE WHEN Status = 4 THEN 1 ELSE 0 END) as failed,
    SUM(CASE WHEN tus1 IS NOT NULL THEN 1 ELSE 0 END) as pressed_1,
    SUM(CASE WHEN tus2 IS NOT NULL THEN 1 ELSE 0 END) as pressed_2,
    SUM(CASE WHEN TransferNo IS NOT NULL THEN 1 ELSE 0 END) as transferred,
    AVG(TIMESTAMPDIFF(SECOND, CallTime, EndCallTime)) as avg_duration
FROM " . table('CALLS') . " 
WHERE RecordDate BETWEEN ? AND ?";

$params = [$startDate . ' 00:00:00', $endDate . ' 23:59:59'];

if ($projectId) {
    $statsQuery .= " AND ProjectId = ?";
    $params[] = $projectId;
}

if ($importId) {
    $statsQuery .= " AND ImportId = ?";
    $params[] = $importId;
}

$stmt = $pdo->prepare($statsQuery);
$stmt->execute($params);
$stats = $stmt->fetch();

$projects = $projectManager->getProjects();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Raporlar</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 1400px; margin: 0 auto; }
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
            grid-template-columns: repeat(5, 1fr);
            gap: 20px;
            margin-top: 20px;
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 25px;
            border-radius: 12px;
            color: white;
            text-align: center;
        }
        .stat-card.green { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); }
        .stat-card.orange { background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%); }
        .stat-card.red { background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); }
        .stat-card.blue { background: linear-gradient(135deg, #17a2b8 0%, #138496 100%); }
        .stat-value {
            font-size: 42px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .stat-label {
            font-size: 14px;
            opacity: 0.9;
        }
        .filter-form {
            display: grid;
            grid-template-columns: repeat(4, 1fr) auto;
            gap: 15px;
            align-items: end;
        }
        .form-group { margin-bottom: 0; }
        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }
        input, select {
            width: 100%;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
        }
        .btn {
            padding: 10px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            transition: all 0.3s;
            white-space: nowrap;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-success {
            background: #28a745;
            color: white;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .chart-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 30px;
        }
        .chart-card {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Arama Raporları</h1>
            <p class="subtitle">Detaylı arama istatistikleri ve raporlar</p>
            
            <div class="nav-links">
                <a href="otomatikarama.php">🏠 Ana Sayfa</a>
                <a href="projeler.php">📁 Projeler</a>
                <a href="sablonlar.php">📝 Şablonlar</a>
                <a href="import.php">📥 İçe Aktarma</a>
            </div>
        </div>

        <!-- Filtreler -->
        <div class="card">
            <h3 style="margin-bottom: 20px; color: #667eea;">🔍 Filtreler</h3>
            
            <form method="get" class="filter-form">
                <div class="form-group">
                    <label>Proje</label>
                    <select name="project_id">
                        <option value="">Tüm Projeler</option>
                        <?php foreach ($projects as $proj): ?>
                        <option value="<?= $proj['id'] ?>" <?= $projectId == $proj['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($proj['ProjectName']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Başlangıç Tarihi</label>
                    <input type="date" name="start_date" value="<?= $startDate ?>">
                </div>
                
                <div class="form-group">
                    <label>Bitiş Tarihi</label>
                    <input type="date" name="end_date" value="<?= $endDate ?>">
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">🔍 Filtrele</button>
                </div>
                
                <div class="form-group">
                    <a href="rapor_excel.php?<?= http_build_query($_GET) ?>" class="btn btn-success" style="text-decoration: none; display: inline-block;">
                        📥 Excel İndir
                    </a>
                </div>
            </form>
        </div>

        <!-- İstatistikler -->
        <div class="card">
            <h3 style="margin-bottom: 20px; color: #667eea;">📈 Genel İstatistikler</h3>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?= number_format($stats['total']) ?></div>
                    <div class="stat-label">Toplam Arama</div>
                </div>
                
                <div class="stat-card green">
                    <div class="stat-value"><?= number_format($stats['completed']) ?></div>
                    <div class="stat-label">Başarılı</div>
                    <div style="font-size: 12px; margin-top: 5px;">
                        <?= $stats['total'] > 0 ? round($stats['completed'] / $stats['total'] * 100, 1) : 0 ?>%
                    </div>
                </div>
                
                <div class="stat-card orange">
                    <div class="stat-value"><?= number_format($stats['calling']) ?></div>
                    <div class="stat-label">Aranıyor</div>
                </div>
                
                <div class="stat-card red">
                    <div class="stat-value"><?= number_format($stats['failed']) ?></div>
                    <div class="stat-label">Başarısız</div>
                </div>
                
                <div class="stat-card blue">
                    <div class="stat-value"><?= number_format($stats['pending']) ?></div>
                    <div class="stat-label">Beklemede</div>
                </div>
            </div>

            <!-- DTMF İstatistikleri -->
            <div class="chart-container">
                <div class="chart-card">
                    <h4 style="color: #667eea; margin-bottom: 15px;">🔢 Tuş Basma İstatistikleri</h4>
                    <div style="padding: 20px;">
                        <div style="margin-bottom: 15px;">
                            <strong>Tuş 1 (Tekrar Dinle):</strong>
                            <span style="float: right; font-size: 20px; color: #667eea;"><?= $stats['pressed_1'] ?></span>
                        </div>
                        <div style="margin-bottom: 15px;">
                            <strong>Tuş 2 (Operatöre Aktar):</strong>
                            <span style="float: right; font-size: 20px; color: #28a745;"><?= $stats['pressed_2'] ?></span>
                        </div>
                        <div>
                            <strong>Transfer Edildi:</strong>
                            <span style="float: right; font-size: 20px; color: #ffc107;"><?= $stats['transferred'] ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="chart-card">
                    <h4 style="color: #667eea; margin-bottom: 15px;">⏱️ Arama Süreleri</h4>
                    <div style="padding: 20px; text-align: center;">
                        <div style="font-size: 48px; font-weight: 700; color: #667eea;">
                            <?= round($stats['avg_duration'] ?? 0) ?>s
                        </div>
                        <div style="color: #666; margin-top: 10px;">Ortalama Arama Süresi</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
