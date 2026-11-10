<?php
/**
 * Kullanıcı ve Rol Bilgilerini Detaylı Kontrol
 */

require_once 'config.php';
require_once 'includes/Auth.php';

$auth = new Auth($pdo);

echo "<!DOCTYPE html>";
echo "<html lang='tr'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<title>Kullanıcı ve Rol Kontrolü</title>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
    .container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    h1 { color: #333; border-bottom: 3px solid #667eea; padding-bottom: 10px; }
    h2 { color: #667eea; margin-top: 30px; }
    .user-card { background: #f9f9f9; padding: 20px; margin: 10px 0; border-left: 4px solid #667eea; border-radius: 5px; }
    .success { color: #27ae60; }
    .error { color: #e74c3c; }
    .warning { color: #f39c12; }
    table { width: 100%; border-collapse: collapse; margin: 10px 0; }
    th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
    th { background: #667eea; color: white; }
    .btn { display: inline-block; padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin: 5px; border: none; cursor: pointer; }
    .btn:hover { background: #5568d3; }
    .code { background: #f4f4f4; padding: 2px 5px; border-radius: 3px; font-family: monospace; font-size: 12px; }
</style>";
echo "</head>";
echo "<body>";
echo "<div class='container'>";

echo "<h1>🔍 Kullanıcı ve Rol Detay Kontrolü</h1>";

// Tablo yapısını kontrol et
echo "<h2>📊 Users Tablosu Yapısı</h2>";
$columns = $pdo->query("SHOW COLUMNS FROM users")->fetchAll();
echo "<table>";
echo "<tr><th>Sütun Adı</th><th>Veri Tipi</th><th>Null?</th><th>Varsayılan</th></tr>";
foreach ($columns as $col) {
    echo "<tr>";
    echo "<td><strong>{$col['Field']}</strong></td>";
    echo "<td>{$col['Type']}</td>";
    echo "<td>{$col['Null']}</td>";
    echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
    echo "</tr>";
}
echo "</table>";

// Kullanıcıları listele
echo "<h2>👥 Kullanıcı Listesi ve Rolleri</h2>";

$stmt = $pdo->query("SELECT * FROM users ORDER BY id");
$users = $stmt->fetchAll();

foreach ($users as $user) {
    echo "<div class='user-card'>";
    echo "<h3>👤 {$user['username']} (ID: {$user['id']})</h3>";
    
    // Kullanıcı bilgileri
    echo "<table style='margin-bottom: 15px;'>";
    echo "<tr><th>Alan</th><th>Değer</th></tr>";
    foreach ($user as $key => $value) {
        if ($key !== 'password') {
            $displayValue = $value ?? '<span class="warning">NULL</span>';
            echo "<tr><td><strong>$key</strong></td><td>$displayValue</td></tr>";
        }
    }
    echo "</table>";
    
    // Kullanıcının rolleri
    echo "<h4>🎭 Roller:</h4>";
    $roles = $auth->getUserRoles($user['id']);
    if (empty($roles)) {
        echo "<p class='warning'>⚠️ Bu kullanıcının hiç rolü yok!</p>";
    } else {
        echo "<ul>";
        foreach ($roles as $role) {
            echo "<li class='success'>✅ $role</li>";
        }
        echo "</ul>";
    }
    
    // Kullanıcının izinleri
    echo "<h4>🔑 İzinler:</h4>";
    $permissions = $auth->getUserPermissions($user['id']);
    if (empty($permissions)) {
        echo "<p class='warning'>⚠️ Bu kullanıcının hiç izni yok!</p>";
    } else {
        echo "<ul style='column-count: 2;'>";
        foreach ($permissions as $permission) {
            echo "<li class='success'>✅ $permission</li>";
        }
        echo "</ul>";
    }
    
    // Superadmin rolü atama butonu
    if (!in_array('superadmin', $roles)) {
        echo "<form method='POST' style='margin-top: 15px;'>";
        echo "<input type='hidden' name='assign_superadmin' value='{$user['id']}'>";
        echo "<button type='submit' class='btn'>👑 Superadmin Rolü Ata</button>";
        echo "</form>";
    }
    
    echo "</div>";
}

// Superadmin rolü atama işlemi
if (isset($_POST['assign_superadmin'])) {
    $userId = $_POST['assign_superadmin'];
    
    echo "<div style='background: #d4edda; padding: 20px; margin: 20px 0; border-radius: 5px; border-left: 4px solid #28a745;'>";
    echo "<h3>🔄 Superadmin Rolü Atama İşlemi</h3>";
    
    try {
        // Superadmin rolünü bul
        $stmt = $pdo->query("SELECT * FROM ROLES WHERE role_code = 'superadmin'");
        $superadminRole = $stmt->fetch();
        
        if (!$superadminRole) {
            echo "<p class='error'>❌ ROLES tablosunda 'superadmin' rolü bulunamadı!</p>";
            echo "<p>Lütfen önce rolleri oluşturun.</p>";
        } else {
            // Kullanıcıya rolü ata
            $stmt = $pdo->prepare("INSERT IGNORE INTO USER_ROLES (user_id, role_id, assigned_at) VALUES (?, ?, NOW())");
            $stmt->execute([$userId, $superadminRole['id']]);
            
            echo "<p class='success'>✅ Superadmin rolü başarıyla atandı!</p>";
            echo "<p>Kullanıcı ID: <strong>$userId</strong></p>";
            echo "<p><a href='check_user_roles.php' class='btn'>🔄 Sayfayı Yenile</a></p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ Hata: " . $e->getMessage() . "</p>";
    }
    
    echo "</div>";
}

// Rol ve izin tablolarını kontrol et
echo "<h2>🎭 ROLES Tablosu</h2>";
try {
    $stmt = $pdo->query("SELECT * FROM ROLES ORDER BY id");
    $roles = $stmt->fetchAll();
    
    if (empty($roles)) {
        echo "<p class='warning'>⚠️ ROLES tablosu boş!</p>";
    } else {
        echo "<table>";
        echo "<tr><th>ID</th><th>Rol Kodu</th><th>Rol Adı</th><th>Açıklama</th><th>Aktif</th></tr>";
        foreach ($roles as $role) {
            echo "<tr>";
            echo "<td>{$role['id']}</td>";
            echo "<td><strong>{$role['role_code']}</strong></td>";
            echo "<td>{$role['role_name']}</td>";
            echo "<td>{$role['description']}</td>";
            echo "<td>" . ($role['is_active'] ? '✅' : '❌') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ ROLES tablosu okunamadı: " . $e->getMessage() . "</p>";
}

echo "<h2>🔗 USER_ROLES Tablosu (Kullanıcı-Rol İlişkileri)</h2>";
try {
    $stmt = $pdo->query("
        SELECT ur.*, u.username, r.role_name 
        FROM USER_ROLES ur 
        LEFT JOIN users u ON ur.user_id = u.id 
        LEFT JOIN ROLES r ON ur.role_id = r.id 
        ORDER BY ur.user_id
    ");
    $userRoles = $stmt->fetchAll();
    
    if (empty($userRoles)) {
        echo "<p class='warning'>⚠️ Hiçbir kullanıcıya rol atanmamış!</p>";
    } else {
        echo "<table>";
        echo "<tr><th>ID</th><th>Kullanıcı</th><th>Rol</th><th>Atanma Tarihi</th></tr>";
        foreach ($userRoles as $ur) {
            echo "<tr>";
            echo "<td>{$ur['id']}</td>";
            echo "<td>{$ur['username']}</td>";
            echo "<td>{$ur['role_name']}</td>";
            echo "<td>{$ur['assigned_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ USER_ROLES tablosu okunamadı: " . $e->getMessage() . "</p>";
}

echo "<div style='margin-top: 30px; text-align: center;'>";
echo "<a href='login.php' class='btn'>🔐 Login Sayfası</a> ";
echo "<a href='otomatikarama.php' class='btn'>🏠 Ana Sayfa</a>";
echo "</div>";

echo "</div>";
echo "</body>";
echo "</html>";
