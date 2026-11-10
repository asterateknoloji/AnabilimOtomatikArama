<?php
require_once 'includes/check_auth.php';
require_once 'includes/ConfigManager.php';

// İzin kontrolü
requirePermission('config.view');

// Sayfa görüntüleme logu
logActivity('view', 'config', 'Sistem Ayarları sayfası görüntülendi');

$pageTitle = 'Sistem Ayarları';

$configManager = new ConfigManager($pdo);

// Config güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && hasPermission('config.edit')) {
    try {
        $configKey = $_POST['config_key'] ?? '';
        $configValue = $_POST['config_value'] ?? '';
        
        if ($configKey && $configValue !== '') {
            // Mevcut config'i al
            $stmt = $pdo->prepare("SELECT config_group, is_encrypted FROM SYSTEM_CONFIG WHERE config_key = ?");
            $stmt->execute([$configKey]);
            $config = $stmt->fetch();
            
            if ($config) {
                $configManager->set(
                    $configKey,
                    $configValue,
                    $config['config_group'],
                    $config['is_encrypted'] == 1,
                    null,
                    $currentUser['id']
                );
                
                // Log
                logActivity('update', 'config', "Sistem ayarı güncellendi: $configKey");
                
                $success = "Ayar başarıyla güncellendi!";
            }
        }
    } catch (Exception $e) {
        $error = "Hata: " . $e->getMessage();
    }
}

// Tüm config'leri çek
$stmt = $pdo->query("
    SELECT * FROM SYSTEM_CONFIG 
    ORDER BY config_group, config_key
");
$configs = $stmt->fetchAll();

// Gruplara ayır
$groupedConfigs = [];
foreach ($configs as $config) {
    $groupedConfigs[$config['config_group']][] = $config;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .header {
            background: white;
            padding: 25px 30px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        h1 {
            color: #2c3e50;
            font-size: 28px;
        }
        .user-info {
            color: #666;
            font-size: 14px;
        }
        .user-info strong {
            color: #667eea;
        }
        .alert {
            padding: 15px;
            border-radius: 10px;
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
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border-left: 4px solid #ffc107;
        }
        .card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 20px;
        }
        .card-header {
            background: #f8f9fa;
            padding: 20px;
            border-bottom: 2px solid #e0e0e0;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .card-header h2 {
            color: #2c3e50;
            font-size: 20px;
            flex: 1;
        }
        .card-body {
            padding: 25px;
        }
        .config-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }
        .config-item {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            border: 2px solid #e0e0e0;
        }
        .config-label {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        .config-label strong {
            color: #2c3e50;
            font-size: 14px;
        }
        .config-label .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
        }
        .badge-encrypted {
            background: #e74c3c;
            color: white;
        }
        .badge-plain {
            background: #95a5a6;
            color: white;
        }
        .config-description {
            color: #999;
            font-size: 12px;
            margin-bottom: 10px;
        }
        .config-value {
            display: flex;
            gap: 10px;
        }
        .config-value input {
            flex: 1;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            font-family: 'Courier New', monospace;
        }
        .config-value input:focus {
            outline: none;
            border-color: #667eea;
        }
        .config-value input[readonly] {
            background: #f8f9fa;
            color: #999;
        }
        .btn {
            padding: 10px 15px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        .btn-sm {
            padding: 8px 12px;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1><i class="fas fa-cogs"></i> <?= $pageTitle ?></h1>
            </div>
            <div class="user-info">
                <i class="fas fa-user"></i> <strong><?= htmlspecialchars($currentUser['username']) ?></strong> |
                <a href="otomatikarama.php" style="color: #667eea; text-decoration: none;">
                    <i class="fas fa-home"></i> Ana Sayfa
                </a> |
                <a href="logout.php" style="color: #e74c3c; text-decoration: none;">
                    <i class="fas fa-sign-out-alt"></i> Çıkış
                </a>
            </div>
        </div>

        <?php if (isset($success)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
        </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <?php if (!hasPermission('config.edit')): ?>
        <div class="alert alert-warning">
            <i class="fas fa-info-circle"></i> Sistem ayarlarını görüntüleme izniniz var, ancak düzenleme yetkiniz bulunmuyor.
        </div>
        <?php endif; ?>

        <?php foreach ($groupedConfigs as $group => $groupConfigs): ?>
        <div class="card">
            <div class="card-header">
                <i class="fas fa-<?= $group === 'database' ? 'database' : ($group === 'ssh' ? 'terminal' : ($group === 'asterisk' ? 'phone' : 'cog')) ?>"></i>
                <h2><?= ucfirst($group) ?></h2>
            </div>
            <div class="card-body">
                <div class="config-grid">
                    <?php foreach ($groupConfigs as $config): ?>
                    <div class="config-item">
                        <div class="config-label">
                            <strong><?= htmlspecialchars($config['config_key']) ?></strong>
                            <span class="badge badge-<?= $config['is_encrypted'] ? 'encrypted' : 'plain' ?>">
                                <?= $config['is_encrypted'] ? '🔒 Şifreli' : '📄 Düz Metin' ?>
                            </span>
                        </div>
                        <?php if ($config['description']): ?>
                        <div class="config-description">
                            <?= htmlspecialchars($config['description']) ?>
                        </div>
                        <?php endif; ?>
                        <form method="POST" class="config-value">
                            <input 
                                type="<?= $config['is_encrypted'] ? 'password' : 'text' ?>" 
                                name="config_value" 
                                value="<?= htmlspecialchars($configManager->get($config['config_key'])) ?>"
                                <?= hasPermission('config.edit') ? '' : 'readonly' ?>
                            >
                            <?php if (hasPermission('config.edit')): ?>
                            <input type="hidden" name="config_key" value="<?= htmlspecialchars($config['config_key']) ?>">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fas fa-save"></i>
                            </button>
                            <?php endif; ?>
                        </form>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</body>
</html>
