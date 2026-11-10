<?php
/**
 * Login Geçmişi
 * Kullanıcıların giriş/çıkış işlemlerini ve IP adreslerini gösterir
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/check_auth.php';
requirePermission('log.view');

// Sayfa görüntüleme logu
logActivity('view', 'log', 'Login Geçmişi sayfası görüntülendi');

$pageTitle = 'Login Geçmişi';

// Filtreleme parametreleri
$filterUser = $_GET['user'] ?? '';
$filterType = $_GET['type'] ?? ''; // login veya logout
$filterDate = $_GET['date'] ?? date('Y-m-d'); // Default: Bugün
$filterIp = $_GET['ip'] ?? '';
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

// Login kayıtlarını çek
$sql = "
    SELECT 
        ual.id,
        ual.created_at,
        ual.action_type,
        ual.action_description,
        ual.ip_address,
        ual.user_agent,
        u.username,
        u.adi,
        u.soyadi
    FROM USER_ACTIVITY_LOG ual
    LEFT JOIN users u ON ual.user_id = u.id
    WHERE ual.action_module = 'auth'
";
$params = [];

// Hiyerarşik filtreleme
if (hasPermission('log.view.all')) {
    // Tüm logları göster
} else {
    // Hiyerarşi bazlı filtreleme
    $sql .= " AND (
        ual.user_id = ? 
        OR ual.user_id IN (
            SELECT ur2.user_id 
            FROM USER_ROLES ur2
            JOIN ROLES r2 ON ur2.role_id = r2.id
            WHERE r2.hierarchy_level > ?
        )
    )";
    $params[] = $currentUser['id'];
    $params[] = $userLevel;
}

if ($filterUser) {
    $sql .= " AND u.username LIKE ?";
    $params[] = "%$filterUser%";
}

if ($filterType) {
    $sql .= " AND ual.action_type = ?";
    $params[] = $filterType;
}

if ($filterDate) {
    $sql .= " AND DATE(ual.created_at) = ?";
    $params[] = $filterDate;
}

if ($filterIp) {
    $sql .= " AND ual.ip_address LIKE ?";
    $params[] = "%$filterIp%";
}

$sql .= " ORDER BY ual.created_at DESC LIMIT " . $limit;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Filtre değerleri - Kullanıcı listesi
if (hasPermission('log.view.all')) {
    $usersStmt = $pdo->query("
        SELECT DISTINCT u.id, u.username, u.adi, u.soyadi 
        FROM users u
        INNER JOIN USER_ACTIVITY_LOG ual ON u.id = ual.user_id
        WHERE ual.action_module = 'auth'
        ORDER BY u.username
    ");
} else {
    $usersStmt = $pdo->prepare("
        SELECT DISTINCT u.id, u.username, u.adi, u.soyadi 
        FROM users u
        INNER JOIN USER_ACTIVITY_LOG ual ON u.id = ual.user_id
        WHERE ual.action_module = 'auth'
        AND (u.id = ? 
            OR u.id IN (
                SELECT ur2.user_id 
                FROM USER_ROLES ur2
                JOIN ROLES r2 ON ur2.role_id = r2.id
                WHERE r2.hierarchy_level > ?
            )
        )
        ORDER BY u.username
    ");
    $usersStmt->execute([$currentUser['id'], $userLevel]);
}
$users = $usersStmt->fetchAll(PDO::FETCH_ASSOC);

// IP adresleri listesi
$ipStmt = $pdo->query("
    SELECT DISTINCT ip_address 
    FROM USER_ACTIVITY_LOG 
    WHERE action_module = 'auth' AND ip_address IS NOT NULL
    ORDER BY ip_address
");
$ipAddresses = $ipStmt->fetchAll(PDO::FETCH_COLUMN);

// İstatistikler
$statsStmt = $pdo->query("
    SELECT 
        COUNT(CASE WHEN action_type = 'login' AND action_description LIKE 'Başarılı%' THEN 1 END) as successful_logins,
        COUNT(CASE WHEN action_type = 'login' AND action_description LIKE 'Başarısız%' THEN 1 END) as failed_logins,
        COUNT(CASE WHEN action_type = 'logout' THEN 1 END) as logouts,
        COUNT(DISTINCT ip_address) as unique_ips
    FROM USER_ACTIVITY_LOG
    WHERE action_module = 'auth'
    AND DATE(created_at) = CURDATE()
");
$stats = $statsStmt->fetch();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
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
            max-width: 1400px;
            margin: 0 auto;
        }
        .header {
            background: white;
            padding: 25px 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        h1 {
            color: #2c3e50;
            font-size: 28px;
        }
        .nav-links {
            display: flex;
            gap: 15px;
            align-items: center;
            font-size: 14px;
        }
        .nav-links a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        .stats {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 25px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        .stat-card {
            text-align: center;
            padding: 15px;
            border-radius: 10px;
        }
        .stat-card.success { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; }
        .stat-card.danger { background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%); color: white; }
        .stat-card.info { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .stat-card.warning { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; }
        .stat-number {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .stat-label {
            font-size: 14px;
            opacity: 0.9;
        }
        .filters {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 25px;
        }
        .filter-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        .filter-group label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: 600;
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
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }
        td {
            padding: 12px 15px;
            border-bottom: 1px solid #f0f0f0;
        }
        tr:hover {
            background: #f8f9fa;
        }
        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            color: white;
        }
        .badge-login-success { background: #27ae60; }
        .badge-login-fail { background: #e74c3c; }
        .badge-logout { background: #3498db; }
        .text-muted { color: #999; font-size: 12px; }
        .ip-badge {
            background: #ecf0f1;
            color: #2c3e50;
            padding: 4px 10px;
            border-radius: 5px;
            font-family: monospace;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-user-clock"></i> <?php echo htmlspecialchars($pageTitle); ?></h1>
            <div class="nav-links">
                <strong>Kullanıcı: <?php echo htmlspecialchars($currentUser['username']); ?></strong> |
                <a href="activity_logs.php"><i class="fas fa-history"></i> Aktivite Logları</a> |
                <a href="otomatikarama.php"><i class="fas fa-home"></i> Ana Sayfa</a> |
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Çıkış</a>
            </div>
        </div>

        <?php if (!hasPermission('log.view.all')): ?>
        <div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 15px; border-radius: 10px; margin-bottom: 20px; text-align: center;">
            <i class="fas fa-info-circle"></i> <strong>Bilgi:</strong> Kendi ve alt kullanıcıların login geçmişini görüntüleyebilirsiniz.
            <?php
            $roleNames = [1 => 'Superadmin', 2 => 'Admin', 3 => 'Operator', 4 => 'Viewer'];
            if (isset($roleNames[$userLevel])) {
                echo " (Seviye: {$roleNames[$userLevel]})";
            }
            ?>
        </div>
        <?php endif; ?>

        <!-- İstatistikler -->
        <div class="stats">
            <div class="stat-card success">
                <div class="stat-number"><?php echo $stats['successful_logins']; ?></div>
                <div class="stat-label">Başarılı Giriş (Bugün)</div>
            </div>
            <div class="stat-card danger">
                <div class="stat-number"><?php echo $stats['failed_logins']; ?></div>
                <div class="stat-label">Başarısız Giriş (Bugün)</div>
            </div>
            <div class="stat-card info">
                <div class="stat-number"><?php echo $stats['logouts']; ?></div>
                <div class="stat-label">Çıkış (Bugün)</div>
            </div>
            <div class="stat-card warning">
                <div class="stat-number"><?php echo $stats['unique_ips']; ?></div>
                <div class="stat-label">Farklı IP (Bugün)</div>
            </div>
        </div>

        <!-- Filtreler -->
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
                        <select name="type">
                            <option value="">Tümü</option>
                            <option value="login" <?php echo $filterType === 'login' ? 'selected' : ''; ?>>Giriş</option>
                            <option value="logout" <?php echo $filterType === 'logout' ? 'selected' : ''; ?>>Çıkış</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>IP Adresi</label>
                        <select name="ip">
                            <option value="">Tümü</option>
                            <?php foreach ($ipAddresses as $ip): ?>
                            <option value="<?php echo htmlspecialchars($ip); ?>" <?php echo $filterIp === $ip ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($ip); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>Tarih</label>
                        <input type="date" name="date" value="<?php echo htmlspecialchars($filterDate); ?>">
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
                </div>
                
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Filtrele
                    </button>
                    <a href="login_history.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Temizle
                    </a>
                    <?php if (hasPermission('report.export')): ?>
                    <button type="button" onclick="exportToExcel()" class="btn btn-success">
                        <i class="fas fa-file-excel"></i> Excel
                    </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Tablo -->
        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>Tarih/Saat</th>
                        <th>Kullanıcı</th>
                        <th>İşlem</th>
                        <th>IP Adresi</th>
                        <th>Tarayıcı</th>
                        <th>Durum</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 40px; color: #999;">
                            <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 15px; display: block;"></i>
                            Kayıt bulunamadı
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo date('d.m.Y H:i:s', strtotime($log['created_at'])); ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($log['username'] ?? 'Bilinmeyen'); ?></strong><br>
                                <span class="text-muted"><?php echo htmlspecialchars($log['adi'] . ' ' . $log['soyadi']); ?></span>
                            </td>
                            <td>
                                <?php if ($log['action_type'] === 'login'): ?>
                                    <?php if (strpos($log['action_description'], 'Başarılı') !== false): ?>
                                        <span class="badge badge-login-success">✓ Giriş</span>
                                    <?php else: ?>
                                        <span class="badge badge-login-fail">✗ Başarısız Giriş</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="badge badge-logout">← Çıkış</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="ip-badge"><?php echo htmlspecialchars($log['ip_address'] ?? 'Bilinmiyor'); ?></span></td>
                            <td class="text-muted" style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo htmlspecialchars($log['user_agent'] ?? ''); ?>">
                                <?php 
                                $userAgent = $log['user_agent'] ?? '';
                                // Basit tarayıcı tespiti
                                if (strpos($userAgent, 'Chrome') !== false) echo '🌐 Chrome';
                                elseif (strpos($userAgent, 'Firefox') !== false) echo '🦊 Firefox';
                                elseif (strpos($userAgent, 'Safari') !== false) echo '🧭 Safari';
                                elseif (strpos($userAgent, 'Edge') !== false) echo '🌊 Edge';
                                else echo '🖥️ ' . substr($userAgent, 0, 30) . '...';
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($log['action_description']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div style="text-align: center; margin-top: 20px; color: white; font-size: 14px;">
            Toplam <?php echo count($logs); ?> kayıt gösteriliyor
        </div>
    </div>

    <script>
        function exportToExcel() {
            const params = new URLSearchParams(window.location.search);
            window.location.href = 'export_login_history.php?' + params.toString();
        }
    </script>
</body>
</html>
