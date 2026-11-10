<?php
require_once 'config.php';
require_once 'includes/ProjectManager.php';
require_once 'includes/AutoCallManager.php';

$projectManager = new ProjectManager($pdo);
$autoCallManager = new AutoCallManager($pdo);

// Arama başlat - Proje bazlı
if (isset($_POST['start_project_calls'])) {
    try {
        $projectId = $_POST['project_id'];
        $limit = (int)$_POST['call_limit'];
        
        $count = $autoCallManager->startProjectCalls($projectId, $limit);
        $message = "✅ {$count} arama başlatıldı!";
        $messageType = 'success';
    } catch (Exception $e) {
        $message = "❌ Hata: " . $e->getMessage();
        $messageType = 'error';
    }
}

// Aramayı durdur - Proje bazlı
if (isset($_POST['stop_project_calls'])) {
    try {
        $projectId = $_POST['project_id'];
        $count = $autoCallManager->stopProjectCalls($projectId);
        $message = "✅ {$count} arama durduruldu!";
        $messageType = 'success';
    } catch (Exception $e) {
        $message = "❌ Hata: " . $e->getMessage();
        $messageType = 'error';
    }
}

$projects = $projectManager->getProjects(true);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proje Aramaları</title>
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
            transition: all 0.3s;
        }
        .nav-links a:hover { background: #667eea; color: white; }
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            background: white;
        }
        .alert-success { border-left: 4px solid #28a745; color: #155724; }
        .alert-error { border-left: 4px solid #dc3545; color: #721c24; }
        .project-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 30px;
        }
        .project-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e0e0e0;
        }
        .project-name {
            font-size: 24px;
            font-weight: 700;
            color: #667eea;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        .stat-box {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            border: 2px solid #dee2e6;
        }
        .stat-value {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        .stat-value.pending { color: #ffc107; }
        .stat-value.calling { color: #17a2b8; }
        .stat-value.completed { color: #28a745; }
        .stat-value.failed { color: #dc3545; }
        .stat-label { font-size: 13px; color: #666; font-weight: 600; }
        .controls {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .control-section {
            margin-bottom: 25px;
        }
        .control-section h3 {
            color: #667eea;
            font-size: 18px;
            margin-bottom: 15px;
        }
        .button-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 10px;
        }
        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            transition: all 0.3s;
            text-align: center;
        }
        .btn-start {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }
        .btn-start:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
        }
        .btn-stop {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
        }
        .btn-stop:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.4);
        }
        .btn-all {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            grid-column: span 2;
        }
        .btn-all:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .speed-control {
            display: flex;
            align-items: center;
            gap: 15px;
            background: white;
            padding: 15px;
            border-radius: 8px;
        }
        .speed-control label { font-weight: 600; color: #333; }
        .speed-control input {
            flex: 1;
            padding: 8px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            max-width: 100px;
        }
        .speed-info {
            font-size: 13px;
            color: #666;
            margin-top: 5px;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        .empty-icon { font-size: 64px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎯 Proje Bazlı Arama Yönetimi</h1>
            <p class="subtitle">Her proje için ayrı arama kontrolü yapın</p>
            
            <div class="nav-links">
                <a href="otomatikarama.php">🏠 Ana Sayfa</a>
                <a href="projeler.php">📁 Projeler</a>
                <a href="import.php">📥 Veri İçe Aktar</a>
                <a href="raporlar.php">📊 Raporlar</a>
            </div>
        </div>

        <?php if (isset($message)): ?>
        <div class="alert alert-<?= $messageType ?>">
            <?= $message ?>
        </div>
        <?php endif; ?>

        <?php if (empty($projects)): ?>
        <div class="project-card">
            <div class="empty-state">
                <div class="empty-icon">📁</div>
                <h3>Henüz proje yok</h3>
                <p>Önce <a href="projeler.php">Proje Yönetimi</a> sayfasından proje oluşturun</p>
            </div>
        </div>
        <?php else: ?>
            <?php foreach ($projects as $project): ?>
                <?php 
                $stats = $projectManager->getProjectStats($project['id']);
                $imports = $projectManager->getProjectImports($project['id']);
                ?>
                
                <div class="project-card">
                    <div class="project-header">
                        <div>
                            <div class="project-name"><?= htmlspecialchars($project['ProjectName']) ?></div>
                            <div style="color: #666; font-size: 14px; margin-top: 5px;">
                                <?= htmlspecialchars($project['Description']) ?>
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 13px; color: #666;">Toplam Import</div>
                            <div style="font-size: 24px; font-weight: 700; color: #667eea;">
                                <?= count($imports) ?>
                            </div>
                        </div>
                    </div>

                    <!-- İstatistikler -->
                    <div class="stats-grid">
                        <div class="stat-box">
                            <div class="stat-value pending"><?= $stats['pending'] ?></div>
                            <div class="stat-label">🟡 Bekleyen</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-value calling"><?= $stats['calling'] ?></div>
                            <div class="stat-label">🔵 Aranıyor</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-value completed"><?= $stats['completed'] ?></div>
                            <div class="stat-label">🟢 Tamamlandı</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-value failed"><?= $stats['failed'] ?></div>
                            <div class="stat-label">🔴 Başarısız</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-value" style="color: #667eea;"><?= $stats['total'] ?></div>
                            <div class="stat-label">📊 Toplam</div>
                        </div>
                    </div>

                    <!-- Kontroller -->
                    <?php if ($stats['pending'] > 0 || $stats['calling'] > 0): ?>
                    <div class="controls">
                        <!-- Arama Başlat -->
                        <?php if ($stats['pending'] > 0): ?>
                        <div class="control-section">
                            <h3>🚀 Arama Başlat (<?= $stats['pending'] ?> adet bekliyor)</h3>
                            
                            <div class="button-grid">
                                <form method="post" style="display: contents;">
                                    <input type="hidden" name="project_id" value="<?= $project['id'] ?>">
                                    <input type="hidden" name="call_limit" value="1">
                                    <button type="submit" name="start_project_calls" class="btn btn-start">
                                        ▶️ 1 Adet
                                    </button>
                                </form>
                                
                                <form method="post" style="display: contents;">
                                    <input type="hidden" name="project_id" value="<?= $project['id'] ?>">
                                    <input type="hidden" name="call_limit" value="5">
                                    <button type="submit" name="start_project_calls" class="btn btn-start">
                                        ▶️ 5 Adet
                                    </button>
                                </form>
                                
                                <form method="post" style="display: contents;">
                                    <input type="hidden" name="project_id" value="<?= $project['id'] ?>">
                                    <input type="hidden" name="call_limit" value="10">
                                    <button type="submit" name="start_project_calls" class="btn btn-start">
                                        ▶️ 10 Adet
                                    </button>
                                </form>
                                
                                <form method="post" style="display: contents;">
                                    <input type="hidden" name="project_id" value="<?= $project['id'] ?>">
                                    <input type="hidden" name="call_limit" value="20">
                                    <button type="submit" name="start_project_calls" class="btn btn-start">
                                        ▶️ 20 Adet
                                    </button>
                                </form>
                                
                                <form method="post" style="display: contents;">
                                    <input type="hidden" name="project_id" value="<?= $project['id'] ?>">
                                    <input type="hidden" name="call_limit" value="50">
                                    <button type="submit" name="start_project_calls" class="btn btn-start">
                                        ▶️ 50 Adet
                                    </button>
                                </form>
                                
                                <form method="post" style="display: contents;">
                                    <input type="hidden" name="project_id" value="<?= $project['id'] ?>">
                                    <input type="hidden" name="call_limit" value="100">
                                    <button type="submit" name="start_project_calls" class="btn btn-start">
                                        ▶️ 100 Adet
                                    </button>
                                </form>
                                
                                <form method="post" style="display: contents;" onsubmit="return confirm('TÜM bekleyen aramaları başlatmak istediğinizden emin misiniz?');">
                                    <input type="hidden" name="project_id" value="<?= $project['id'] ?>">
                                    <input type="hidden" name="call_limit" value="999999">
                                    <button type="submit" name="start_project_calls" class="btn btn-all">
                                        ⚡ TÜM ARAMALARI BAŞLAT
                                    </button>
                                </form>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Aramayı Durdur -->
                        <?php if ($stats['calling'] > 0): ?>
                        <div class="control-section">
                            <h3>⏸️ Devam Eden Aramalar (<?= $stats['calling'] ?> adet)</h3>
                            
                            <form method="post" onsubmit="return confirm('<?= $stats['calling'] ?> adet devam eden aramayı durdurmak istediğinizden emin misiniz?');">
                                <input type="hidden" name="project_id" value="<?= $project['id'] ?>">
                                <button type="submit" name="stop_project_calls" class="btn btn-stop" style="width: 100%;">
                                    ⏹️ TÜM ARAMALARI DURDUR
                                </button>
                            </form>
                            
                            <p class="speed-info" style="margin-top: 10px; text-align: center;">
                                ⚠️ Şu anda devam eden aramalar sonlandırılacak
                            </p>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <div class="controls">
                        <div style="text-align: center; padding: 30px; color: #999;">
                            <div style="font-size: 48px; margin-bottom: 15px;">✅</div>
                            <div style="font-size: 18px; font-weight: 600;">Tüm Aramalar Tamamlandı!</div>
                            <p style="margin-top: 10px; font-size: 14px;">
                                Yeni arama yapmak için <a href="import.php" style="color: #667eea; text-decoration: none; font-weight: 600;">veri içe aktarın</a>
                            </p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Son Import'lar -->
                    <?php if (!empty($imports)): ?>
                    <div style="margin-top: 25px; padding-top: 25px; border-top: 2px solid #e0e0e0;">
                        <h3 style="color: #667eea; margin-bottom: 15px;">📋 Son İçe Aktarılanlar</h3>
                        <div style="overflow-x: auto;">
                            <table style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <tr style="background: #f8f9fa;">
                                        <th style="padding: 10px; text-align: left; border-bottom: 2px solid #dee2e6;">Tarih</th>
                                        <th style="padding: 10px; text-align: left; border-bottom: 2px solid #dee2e6;">Dosya</th>
                                        <th style="padding: 10px; text-align: center; border-bottom: 2px solid #dee2e6;">Kayıt</th>
                                        <th style="padding: 10px; text-align: center; border-bottom: 2px solid #dee2e6;">Durum</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($imports, 0, 5) as $import): ?>
                                    <tr style="border-bottom: 1px solid #e0e0e0;">
                                        <td style="padding: 10px; font-size: 13px;">
                                            <?= date('d.m.Y H:i', strtotime($import['ImportDate'])) ?>
                                        </td>
                                        <td style="padding: 10px; font-size: 13px;">
                                            <?= htmlspecialchars($import['OriginalFileName']) ?>
                                        </td>
                                        <td style="padding: 10px; text-align: center; font-weight: 600;">
                                            <?= $import['RecordCount'] ?>
                                        </td>
                                        <td style="padding: 10px; text-align: center;">
                                            <?php
                                            $statusColors = [
                                                'pending' => ['🟡', '#ffc107'],
                                                'in_progress' => ['🔵', '#17a2b8'],
                                                'completed' => ['🟢', '#28a745'],
                                                'failed' => ['🔴', '#dc3545']
                                            ];
                                            $status = $statusColors[$import['Status']] ?? ['⚪', '#6c757d'];
                                            echo "<span style='color: {$status[1]}; font-weight: 600;'>{$status[0]} " . ucfirst($import['Status']) . "</span>";
                                            ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
        // Sayfa otomatik yenileme (30 saniyede bir)
        setTimeout(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html>
