<?php
/**
 * Login Geçmişini Excel'e Aktar
 */

require_once 'includes/check_auth.php';
requirePermission('report.export');

// Log kaydı
logActivity('export', 'log', 'Login geçmişi Excel olarak indirildi');

// Filtreleme parametreleri
$filterUser = $_GET['user'] ?? '';
$filterType = $_GET['type'] ?? '';
$filterDate = $_GET['date'] ?? date('Y-m-d');
$filterIp = $_GET['ip'] ?? '';
$limit = $_GET['limit'] ?? 100;

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
        ual.created_at as tarih,
        u.username as kullanici_adi,
        CONCAT(u.adi, ' ', u.soyadi) as ad_soyad,
        ual.action_type as islem_tipi,
        ual.action_description as aciklama,
        ual.ip_address as ip_adresi,
        ual.user_agent as tarayici
    FROM USER_ACTIVITY_LOG ual
    LEFT JOIN users u ON ual.user_id = u.id
    WHERE ual.action_module = 'auth'
";
$params = [];

// Hiyerarşik filtreleme
if (hasPermission('log.view.all')) {
    // Tüm logları göster
} else {
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

$sql .= " ORDER BY ual.created_at DESC LIMIT ?";
$params[] = (int)$limit;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Excel headers
header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="login_gecmisi_' . date('Y-m-d_H-i-s') . '.xls"');
header('Pragma: no-cache');
header('Expires: 0');

// BOM ekle (UTF-8 için)
echo "\xEF\xBB\xBF";

// Tablo başlıkları
echo "<table border='1'>";
echo "<thead>";
echo "<tr style='background-color: #667eea; color: white; font-weight: bold;'>";
echo "<th>Tarih/Saat</th>";
echo "<th>Kullanıcı Adı</th>";
echo "<th>Ad Soyad</th>";
echo "<th>İşlem Tipi</th>";
echo "<th>Açıklama</th>";
echo "<th>IP Adresi</th>";
echo "<th>Tarayıcı</th>";
echo "</tr>";
echo "</thead>";
echo "<tbody>";

foreach ($logs as $log) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($log['tarih']) . "</td>";
    echo "<td>" . htmlspecialchars($log['kullanici_adi']) . "</td>";
    echo "<td>" . htmlspecialchars($log['ad_soyad']) . "</td>";
    echo "<td>" . htmlspecialchars($log['islem_tipi']) . "</td>";
    echo "<td>" . htmlspecialchars($log['aciklama']) . "</td>";
    echo "<td>" . htmlspecialchars($log['ip_adresi']) . "</td>";
    echo "<td>" . htmlspecialchars($log['tarayici']) . "</td>";
    echo "</tr>";
}

echo "</tbody>";
echo "</table>";
