<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('memory_limit', '256M');

require_once 'includes/check_auth.php';
requirePermission('import.view');

require_once 'config.php';
require_once 'includes/ImportManager.php';
require_once 'includes/ProjectManager.php';

$importManager = new ImportManager($pdo);
$projectManager = new ProjectManager($pdo);

// Tüm importları getir
$imports = $importManager->getAllImports();
$projects = $projectManager->getAllProjects();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Arama Yönetimi (Lite)</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f0f0f0; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background: #667eea; color: white; }
        .btn { padding: 8px 15px; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📞 Arama Yönetimi (Lite Version)</h1>
        <p>Kullanıcı: <strong><?= htmlspecialchars($currentUser['username']) ?></strong></p>
        
        <p><a href="arama_yonetimi.php" class="btn">🚀 Full Version'a Geç</a></p>
        <p><a href="projeler.php" class="btn" style="background: #2196F3;">📁 Projeler</a></p>
        
        <h2>📊 Import Geçmişi</h2>
        
        <?php if (count($imports) > 0): ?>
        <table>
            <tr>
                <th>ID</th>
                <th>Proje</th>
                <th>Dosya</th>
                <th>Kayıt</th>
                <th>Durum</th>
                <th>Tarih</th>
            </tr>
            <?php foreach ($imports as $imp): ?>
            <tr>
                <td><?= $imp['id'] ?></td>
                <td><?= $imp['ProjectId'] ?></td>
                <td><?= htmlspecialchars($imp['FileName']) ?></td>
                <td><?= $imp['RecordCount'] ?></td>
                <td><?= $imp['Status'] ?></td>
                <td><?= $imp['ImportDate'] ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php else: ?>
        <p>Henüz import yok.</p>
        <?php endif; ?>
        
        <h3>ℹ️ Bilgi</h3>
        <p>Bu lite versiyondur. Tam özellikler için yukarıdaki butona tıklayın.</p>
        <p>Eğer full version açılmıyorsa, PHP memory veya execution time sorunu olabilir.</p>
    </div>
</body>
</html>
