<?php
// Hata gösterimi
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/check_auth.php';
requirePermission('log.view');

// Sayfa görüntüleme logu
logActivity('view', 'log', 'Aktivite Logları sayfası görüntülendi');

$pageTitle = 'Aktivite Logları';

// Filtreleme parametreleri
$filterUser = $_GET['user'] ?? '';
$filterAction = $_GET['action'] ?? '';
$filterModule = $_GET['module'] ?? '';
$filterDate = $_GET['date'] ?? date('Y-m-d'); // Default: Bugün
$filterSearch = $_GET['search'] ?? ''; // Genel arama
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100; // Default: 100

// Kullanıcının hiyerarşi seviyesini al
$userHierarchyStmt = $pdo->prepare("
    SELECT MIN(r.hierarchy_level) as user_level
    FROM USER_ROLES ur
    JOIN ROLES r ON ur.role_id = r.id
    WHERE ur.user_id = ?
");
$userHierarchyStmt->execute([$currentUser['id']]);
$userHierarchy = $userHierarchyStmt->fetch();
$userLevel = $userHierarchy['user_level'] ?? 99;

// Log kayıtlarını çek
$sql = "
    SELECT 
        ual.id,
        ual.created_at,
        ual.action_type,
        ual.action_module,
        ual.action_description,
        ual.target_id,
        ual.ip_address,
        ual.user_agent,
        u.username,
        u.adi,
        u.soyadi
    FROM USER_ACTIVITY_LOG ual
    LEFT JOIN users u ON ual.user_id = u.id
    WHERE 1=1
";
$params = [];

// Hiyerarşik filtreleme
if (hasPermission('log.view.all')) {
    // log.view.all yetkisi varsa tüm logları göster (backward compatibility)
    // Hiçbir ek filtre ekleme
} else {
    // Hiyerarşi bazlı filtreleme: Kendi + alt rollerdeki kullanıcıların loglarını göster
    $sql .= " AND (
        ual.user_id = ? 
        OR ual.user_id IN (
            SELECT ur2.user_id 
            FROM USER_ROLES ur2
            JOIN ROLES r2 ON ur2.role_id = r2.id
            WHERE r2.hierarchy_level > ?
        )
    )";
    $params[] = $currentUser['id'];  // Kendi ID'si
    $params[] = $userLevel;           // Kendi seviyesinden daha alt olanlar
}

if ($filterUser) {
    $sql .= " AND u.username LIKE ?";
    $params[] = "%$filterUser%";
}

if ($filterAction) {
    $sql .= " AND ual.action_type = ?";
    $params[] = $filterAction;
}

if ($filterModule) {
    $sql .= " AND ual.action_module = ?";
    $params[] = $filterModule;
}

if ($filterDate) {
    $sql .= " AND DATE(ual.created_at) = ?";
    $params[] = $filterDate;
}

// Genel arama: Tüm alanlarda ara
if ($filterSearch) {
    $sql .= " AND (
        u.username LIKE ? 
        OR CONCAT(u.adi, ' ', u.soyadi) LIKE ?
        OR ual.action_description LIKE ?
        OR ual.ip_address LIKE ?
        OR ual.target_id LIKE ?
    )";
    $searchTerm = "%{$filterSearch}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$sql .= " ORDER BY ual.created_at DESC LIMIT " . $limit;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Filtre değerleri
$actionsStmt = $pdo->query("SELECT DISTINCT action_type FROM USER_ACTIVITY_LOG ORDER BY action_type");
$actions = $actionsStmt->fetchAll(PDO::FETCH_COLUMN);

$modulesStmt = $pdo->query("SELECT DISTINCT action_module FROM USER_ACTIVITY_LOG ORDER BY action_module");
$modules = $modulesStmt->fetchAll(PDO::FETCH_COLUMN);

// Kullanıcı listesi (hiyerarşi kurallarına göre)
if (hasPermission('log.view.all')) {
    // Tüm kullanıcıları göster
    $usersStmt = $pdo->query("
        SELECT DISTINCT u.id, u.username, u.adi, u.soyadi 
        FROM users u
        INNER JOIN USER_ACTIVITY_LOG ual ON u.id = ual.user_id
        ORDER BY u.username
    ");
} else {
    // Sadece kendi ve alt seviyedeki kullanıcıları göster
    $usersStmt = $pdo->prepare("
        SELECT DISTINCT u.id, u.username, u.adi, u.soyadi 
        FROM users u
        INNER JOIN USER_ACTIVITY_LOG ual ON u.id = ual.user_id
        WHERE u.id = ? 
        OR u.id IN (
            SELECT ur2.user_id 
            FROM USER_ROLES ur2
            JOIN ROLES r2 ON ur2.role_id = r2.id
            WHERE r2.hierarchy_level > ?
        )
        ORDER BY u.username
    ");
    $usersStmt->execute([$currentUser['id'], $userLevel]);
}
$users = $usersStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            padding: 25px 30px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        h1 { color: #2c3e50; font-size: 28px; margin-bottom: 10px; }
        .nav-links { margin-top: 15px; }
        .nav-links a {
            color: #667eea;
            text-decoration: none;
            margin-right: 20px;
            font-size: 14px;
        }
        .filters {
            background: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .filter-row {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: flex-end;
        }
        .filter-group { flex: 1; min-width: 150px; }
        .filter-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #2c3e50;
            font-size: 14px;
        }
        .filter-group input,
        .filter-group select {
            width: 100%;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            color: white;
        }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .btn-secondary { background: #95a5a6; }
        .btn-success { background: #27ae60; }
        .card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .card-header {
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        thead { background: #f8f9fa; }
        th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #2c3e50;
            border-bottom: 2px solid #e0e0e0;
        }
        td {
            padding: 12px 15px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 13px;
        }
        tr:hover { background: #f8f9fa; }
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            color: white;
        }
        .badge-login { background: #27ae60; }
        .badge-logout { background: #e74c3c; }
        .badge-create { background: #3498db; }
        .badge-update { background: #f39c12; }
        .badge-delete { background: #c0392b; }
        .badge-view { background: #95a5a6; }
        .badge-export { background: #16a085; }
        .text-muted { color: #999; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-history"></i> <?php echo htmlspecialchars($pageTitle); ?></h1>
            <div class="nav-links">
                <strong>Kullanıcı: <?php echo htmlspecialchars($currentUser['username']); ?></strong> |
                <?php if (hasPermission('user.view')): ?>
                <a href="users_management.php"><i class="fas fa-users"></i> Kullanıcılar</a> |
                <?php endif; ?>
                <a href="otomatikarama.php"><i class="fas fa-home"></i> Ana Sayfa</a> |
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Çıkış</a>
            </div>
        </div>

        <?php if (!hasPermission('log.view.all')): ?>
        <div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 15px; border-radius: 10px; margin: 20px 0; text-align: center;">
            <i class="fas fa-info-circle"></i> <strong>Bilgi:</strong> Kendi ve alt kullanıcıların aktivite loglarını görüntüleyebilirsiniz.
            <?php
            // Kullanıcının rolünü göster
            $roleNames = [1 => 'Superadmin', 2 => 'Admin', 3 => 'Operator', 4 => 'Viewer'];
            if (isset($roleNames[$userLevel])) {
                echo " (Seviye: {$roleNames[$userLevel]})";
            }
            ?>
        </div>
        <?php endif; ?>

        <div class="filters">
            <form method="GET" action="">
                <div class="filter-row">
                    <div class="filter-group">
                        <label>Kullanıcı</label>
                        <select name="user">
                            <option value="">Tümü</option>
                            <?php foreach ($users as $user): ?>
                            <option value="<?php echo htmlspecialchars($user['username']); ?>" <?php echo $filterUser === $user['username'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($user['username']); ?> (<?php echo htmlspecialchars($user['adi'] . ' ' . $user['soyadi']); ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>İşlem Tipi</label>
                        <select name="action">
                            <option value="">Tümü</option>
                            <?php foreach ($actions as $action): ?>
                            <option value="<?php echo htmlspecialchars($action); ?>" <?php echo $filterAction === $action ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($action); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>Modül</label>
                        <select name="module">
                            <option value="">Tümü</option>
                            <?php foreach ($modules as $module): ?>
                            <option value="<?php echo htmlspecialchars($module); ?>" <?php echo $filterModule === $module ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($module); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>Tarih</label>
                        <input type="date" name="date" value="<?php echo htmlspecialchars($filterDate); ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label>Genel Arama</label>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($filterSearch); ?>" placeholder="Kullanıcı, açıklama, IP...">
                    </div>
                    
                    <div class="filter-group">
                        <label>Limit</label>
                        <select name="limit">
                            <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50</option>
                            <option value="100" <?php echo $limit == 100 ? 'selected' : ''; ?>>100</option>
                            <option value="500" <?php echo $limit == 500 ? 'selected' : ''; ?>>500</option>
                            <option value="1000" <?php echo $limit == 1000 ? 'selected' : ''; ?>>1000</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Filtrele
                        </button>
                        <a href="activity_logs.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Temizle
                        </a>
                        <?php if (hasPermission('report.export')): ?>
                        <button type="button" class="btn btn-success" onclick="window.location.href='export_activity_logs.php?<?php echo http_build_query($_GET); ?>'">
                            <i class="fas fa-file-excel"></i> Excel
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>

        <div class="card">
            <div class="card-header">
                <strong><?php echo count($logs); ?></strong> kayıt gösteriliyor
            </div>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tarih/Saat</th>
                        <th>Kullanıcı</th>
                        <th>İşlem</th>
                        <th>Modül</th>
                        <th>Açıklama</th>
                        <th>IP Adresi</th>
                        <th>Tarayıcı</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 40px; color: #999;">
                            <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 10px; display: block;"></i>
                            Kayıt bulunamadı
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo $log['id']; ?></td>
                            <td>
                                <?php echo date('d.m.Y H:i:s', strtotime($log['created_at'])); ?><br>
                                <span class="text-muted"><?php echo date('D', strtotime($log['created_at'])); ?></span>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($log['username'] ?? 'System'); ?></strong><br>
                                <span class="text-muted"><?php echo htmlspecialchars($log['adi'] . ' ' . $log['soyadi']); ?></span>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo $log['action_type']; ?>">
                                    <?php echo htmlspecialchars($log['action_type']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($log['action_module']); ?></td>
                            <td>
                                <?php echo htmlspecialchars($log['action_description']); ?>
                                <?php if ($log['target_id']): ?>
                                    <br><span class="text-muted">ID: <?php echo $log['target_id']; ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($log['ip_address']): ?>
                                    <span style="background: #ecf0f1; color: #2c3e50; padding: 4px 10px; border-radius: 5px; font-family: monospace; font-size: 12px; display: inline-block;">
                                        <?php echo htmlspecialchars($log['ip_address']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted" style="font-size: 12px; max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo htmlspecialchars($log['user_agent'] ?? ''); ?>">
                                <?php 
                                $userAgent = $log['user_agent'] ?? '';
                                if (empty($userAgent)) {
                                    echo '-';
                                } elseif (strpos($userAgent, 'Chrome') !== false) {
                                    echo '🌐 Chrome';
                                } elseif (strpos($userAgent, 'Firefox') !== false) {
                                    echo '🦊 Firefox';
                                } elseif (strpos($userAgent, 'Safari') !== false) {
                                    echo '🧭 Safari';
                                } elseif (strpos($userAgent, 'Edge') !== false) {
                                    echo '🌊 Edge';
                                } else {
                                    echo '🖥️ ' . substr($userAgent, 0, 20) . '...';
                                }
                                ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
