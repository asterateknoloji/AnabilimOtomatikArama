<?php
require_once 'config.php';
require_once 'includes/AutoCallManager.php';
require_once 'includes/ProjectManager.php';
require_once 'includes/TemplateManager.php';
require_once 'includes/ImportManager.php';

$projectManager = new ProjectManager($pdo);
$templateManager = new TemplateManager($pdo);
$importManager = new ImportManager($pdo);
$autoCallManager = new AutoCallManager($pdo);

// Excel import
if (isset($_POST['import_excel']) && isset($_FILES['excel_file'])) {
    try {
        $file = $_FILES['excel_file'];
        $projectId = $_POST['project_id'] ?? null;
        $templateId = $_POST['template_id'] ?? null;
        $scheduledDate = $_POST['scheduled_date'] ?? null;
        $scheduledTime = $_POST['scheduled_time'] ?? null;
        
        if (!$projectId) {
            throw new Exception('Lütfen proje seçiniz');
        }
        
        // Dosya adını formatla: YIL-AY-GUN-DOSYAISMI.xlsx
        $originalFileName = $file['name'];
        $datePrefix = date('Y-m-d');
        $fileBaseName = pathinfo($originalFileName, PATHINFO_FILENAME);
        $fileExt = pathinfo($originalFileName, PATHINFO_EXTENSION);
        $newFileName = "{$datePrefix}_{$fileBaseName}.{$fileExt}";
        
        // Zamanlanmış saat varsa birleştir
        $scheduledDateTime = null;
        if ($scheduledDate && $scheduledTime) {
            $scheduledDateTime = "{$scheduledDate} {$scheduledTime}:00";
        }
        
        // Import kaydı oluştur
        $importId = $importManager->createImport(
            $projectId,
            $newFileName,
            $originalFileName,
            $templateId,
            $scheduledDateTime
        );
        
        // Excel'i içe aktar
        try {
            $result = $autoCallManager->importFromExcel($file, $projectId, $importId, $templateId);
            
            // Import durumunu güncelle
            $importManager->updateImportStatus($importId, 'completed', $result['success']);
            
            $message = "{$result['success']} kayıt başarıyla içe aktarıldı (PASIF). 'Aktif' yaparak başlatabilirsiniz.";
            if ($result['errors'] > 0) {
                $message .= " {$result['errors']} kayıtta hata oluştu.";
            }
            if (isset($result['duplicates']) && $result['duplicates'] > 0) {
                $message .= " {$result['duplicates']} tekrar eden numara atlandı.";
            }
            
            if ($scheduledDateTime) {
                $message .= " Zamanlanmış saat: {$scheduledDateTime}";
            }
            
            $messageType = 'success';
            
        } catch (Exception $importEx) {
            // Import hatası - import kaydını failed yap
            $importManager->updateImportStatus($importId, 'failed', 0);
            throw $importEx; // Ana catch bloğuna fırlat
        }
        
    } catch (Exception $e) {
        $message = 'Hata: ' . $e->getMessage();
        $messageType = 'error';
        
        // Debug için detaylı hata
        error_log("Import error: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
    }
}

$projects = $projectManager->getProjects();
$templates = $templateManager->getTemplates();
$importHistory = $importManager->getImportHistory(null, 20);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Veri İçe Aktarma</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 1200px; margin: 0 auto; }
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
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        .form-group { margin-bottom: 20px; }
        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
        }
        input, select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 15px;
        }
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        th {
            background: #f8f9fa;
            color: #667eea;
            font-weight: 600;
            font-size: 14px;
        }
        tr:hover { background: #f8f9ff; }
        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-pending { background: #fff3cd; color: #856404; }
        .badge-scheduled { background: #cfe2ff; color: #084298; }
        .badge-completed { background: #d4edda; color: #155724; }
        .badge-failed { background: #f8d7da; color: #721c24; }
        .upload-area {
            border: 3px dashed #ddd;
            padding: 40px;
            text-align: center;
            border-radius: 10px;
            background: #f8f9fa;
            cursor: pointer;
            transition: all 0.3s;
        }
        .upload-area:hover {
            border-color: #667eea;
            background: #f0f2ff;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📥 Veri İçe Aktarma</h1>
            <p class="subtitle">Excel dosyalarını proje bazlı içe aktarın ve zamanla yın</p>
            
            <div class="nav-links">
                <a href="otomatikarama.php">🏠 Ana Sayfa</a>
                <a href="projeler.php">📁 Projeler</a>
                <a href="arama_yonetimi.php">📋 Arama Yönetimi</a>
                <a href="sablonlar.php">📝 Şablonlar</a>
                <a href="raporlar.php">📊 Raporlar</a>
            </div>
        </div>

        <?php if (isset($message)): ?>
        <div class="alert alert-<?= $messageType ?>">
            <?= $message ?>
        </div>
        <?php endif; ?>

        <!-- Import Formu -->
        <div class="card">
            <h2 style="color: #667eea; margin-bottom: 25px;">📤 Yeni Import</h2>
            
            <form method="post" enctype="multipart/form-data">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Proje Seçiniz *</label>
                        <select name="project_id" required>
                            <option value="">-- Proje Seçiniz --</option>
                            <?php foreach ($projects as $proj): ?>
                            <option value="<?= $proj['id'] ?>"><?= htmlspecialchars($proj['ProjectName']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Mesaj Şablonu</label>
                        <select name="template_id">
                            <option value="">-- Varsayılan Şablon --</option>
                            <?php foreach ($templates as $tmpl): ?>
                            <option value="<?= $tmpl['id'] ?>"><?= htmlspecialchars($tmpl['TemplateName']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Zamanlanmış Tarih (Opsiyonel)</label>
                        <input type="date" name="scheduled_date" min="<?= date('Y-m-d') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Zamanlanmış Saat (Opsiyonel)</label>
                        <input type="time" name="scheduled_time">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Excel Dosyası *</label>
                    <div class="upload-area" onclick="document.getElementById('excelFile').click()">
                        <input type="file" id="excelFile" name="excel_file" accept=".xls,.xlsx" required style="display: none;" onchange="showFileName(this)">
                        <p id="fileName" style="font-size: 18px; color: #667eea; font-weight: 600;">📄 Dosya Seçmek İçin Tıklayın</p>
                        <p style="font-size: 14px; color: #999; margin-top: 10px;">Excel (.xls, .xlsx) dosyası yükleyin</p>
                    </div>
                </div>
                
                <button type="submit" name="import_excel" class="btn btn-primary">
                    📥 Excel'i İçe Aktar
                </button>
            </form>
        </div>

        <!-- Import Geçmişi -->
        <div class="card">
            <h2 style="color: #667eea; margin-bottom: 20px;">📜 İçe Aktarma Geçmişi</h2>
            
            <table>
                <thead>
                    <tr>
                        <th>Tarih/Saat</th>
                        <th>Proje</th>
                        <th>Dosya Adı</th>
                        <th>Şablon</th>
                        <th>Kayıt</th>
                        <th>Zamanlanma</th>
                        <th>Durum</th>
                        <th>İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($importHistory as $imp): ?>
                    <tr>
                        <td><?= date('d.m.Y H:i', strtotime($imp['ImportDate'])) ?></td>
                        <td><?= htmlspecialchars($imp['ProjectName']) ?></td>
                        <td><?= htmlspecialchars($imp['FileName']) ?></td>
                        <td><?= htmlspecialchars($imp['TemplateName'] ?? '-') ?></td>
                        <td><strong><?= $imp['RecordCount'] ?></strong></td>
                        <td>
                            <?php if ($imp['ScheduledTime']): ?>
                            <?= date('d.m.Y H:i', strtotime($imp['ScheduledTime'])) ?>
                            <?php else: ?>
                            -
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge badge-<?= $imp['Status'] ?>">
                                <?= ucfirst($imp['Status']) ?>
                            </span>
                        </td>
                        <td>
                            <a href="rapor_detay.php?import_id=<?= $imp['id'] ?>" class="btn btn-primary" style="padding: 8px 15px; font-size: 14px; text-decoration: none;">
                                📊 Rapor
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function showFileName(input) {
            const fileName = input.files[0]?.name || 'Dosya Seçmek İçin Tıklayın';
            document.getElementById('fileName').textContent = '📄 ' + fileName;
        }
    </script>
</body>
</html>
