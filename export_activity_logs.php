<?php
/**
 * Aktivite Loglarını Excel'e Aktar
 */

require_once 'includes/check_auth.php';

// Export yetkisi kontrolü
requirePermission('report.export');

// Log kaydı
logActivity('export', 'log', 'Aktivite logları Excel olarak indirildi');

// Filtreleme parametreleri
$filterUser = $_GET['user'] ?? '';
$filterAction = $_GET['action'] ?? '';
$filterModule = $_GET['module'] ?? '';
$filterDate = $_GET['date'] ?? date('Y-m-d'); // Default: Bugün
$filterSearch = $_GET['search'] ?? ''; // Genel arama
$limit = $_GET['limit'] ?? 100; // Default: 100

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
        ual.created_at as tarih,
        u.username as kullanici_adi,
        CONCAT(u.adi, ' ', u.soyadi) as ad_soyad,
        ual.action_type as islem_tipi,
        ual.action_module as modul,
        ual.action_description as aciklama,
        ual.target_id as hedef_id,
        ual.ip_address as ip_adresi,
        ual.user_agent as tarayici
    FROM USER_ACTIVITY_LOG ual
    LEFT JOIN users u ON ual.user_id = u.id
    WHERE 1=1
";
$params = [];

// Hiyerarşik filtreleme
if (hasPermission('log.view.all')) {
    // log.view.all yetkisi varsa tüm logları göster
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

$sql .= " ORDER BY ual.created_at DESC LIMIT ?";
$params[] = (int)$limit;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Excel headers
header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="aktivite_loglari_' . date('Y-m-d_H-i-s') . '.xls"');
header('Pragma: no-cache');
header('Expires: 0');

// BOM ekle (UTF-8 için)
echo "\xEF\xBB\xBF";

// Tablo başlıkları
echo "<table border='1'>";
echo "<thead>";
echo "<tr style='background-color: #667eea; color: white; font-weight: bold;'>";
echo "<th>ID</th>";
echo "<th>Tarih/Saat</th>";
echo "<th>Kullanıcı Adı</th>";
echo "<th>Ad Soyad</th>";
echo "<th>İşlem Tipi</th>";
echo "<th>Modül</th>";
echo "<th>Açıklama</th>";
echo "<th>Hedef ID</th>";
echo "<th>IP Adresi</th>";
echo "<th>Tarayıcı</th>";
echo "</tr>";
echo "</thead>";
echo "<tbody>";

// Verileri yazdır
foreach ($logs as $log) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($log['id']) . "</td>";
    echo "<td>" . htmlspecialchars($log['tarih']) . "</td>";
    echo "<td>" . htmlspecialchars($log['kullanici_adi'] ?? 'System') . "</td>";
    echo "<td>" . htmlspecialchars($log['ad_soyad']) . "</td>";
    echo "<td>" . htmlspecialchars($log['islem_tipi']) . "</td>";
    echo "<td>" . htmlspecialchars($log['modul']) . "</td>";
    echo "<td>" . htmlspecialchars($log['aciklama']) . "</td>";
    echo "<td>" . htmlspecialchars($log['hedef_id'] ?? '-') . "</td>";
    echo "<td>" . htmlspecialchars($log['ip_adresi'] ?? '-') . "</td>";
    echo "<td>" . htmlspecialchars($log['tarayici'] ?? '-') . "</td>";
    echo "</tr>";
}

echo "</tbody>";
echo "</table>";

// İstatistikler
echo "<br><br>";
echo "<table border='1'>";
echo "<tr><td><strong>Toplam Kayıt:</strong></td><td>" . count($logs) . "</td></tr>";
echo "<tr><td><strong>İndirme Tarihi:</strong></td><td>" . date('d.m.Y H:i:s') . "</td></tr>";
echo "<tr><td><strong>İndiren Kullanıcı:</strong></td><td>" . htmlspecialchars($currentUser['username']) . "</td></tr>";
if ($filterUser) echo "<tr><td><strong>Filtre - Kullanıcı:</strong></td><td>" . htmlspecialchars($filterUser) . "</td></tr>";
if ($filterAction) echo "<tr><td><strong>Filtre - İşlem:</strong></td><td>" . htmlspecialchars($filterAction) . "</td></tr>";
if ($filterModule) echo "<tr><td><strong>Filtre - Modül:</strong></td><td>" . htmlspecialchars($filterModule) . "</td></tr>";
if ($filterDate) echo "<tr><td><strong>Filtre - Tarih:</strong></td><td>" . htmlspecialchars($filterDate) . "</td></tr>";
echo "</table>";

exit;
