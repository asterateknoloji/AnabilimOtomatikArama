<?php
header('Content-Type: text/html; charset=utf-8');
require_once 'includes/check_auth.php';

// Sadece superadmin erişebilir
requirePermission('config.edit');

// Sayfa görüntüleme logu
logActivity('view', 'role', 'Rol Yönetimi sayfası görüntülendi');

$pageTitle = 'Rol Yönetimi';

// PDO charset ayarla
$pdo->exec("SET NAMES utf8");

// Tüm rolleri çek
$rolesStmt = $pdo->query("SELECT * FROM ROLES ORDER BY id");
$allRoles = $rolesStmt->fetchAll();

// Tüm izinleri çek (gruplandırılmış)
$permissionsStmt = $pdo->query("
    SELECT * FROM PERMISSIONS 
    ORDER BY permission_group, permission_name
");
$allPermissions = $permissionsStmt->fetchAll();

// İzinleri gruplara ayır
$groupedPermissions = [];
foreach ($allPermissions as $perm) {
    $groupedPermissions[$perm['permission_group']][] = $perm;
}

// Her rol için izinleri çek
$rolePermissions = [];
foreach ($allRoles as $role) {
    $stmt = $pdo->prepare("
        SELECT permission_id 
        FROM ROLE_PERMISSIONS 
        WHERE role_id = ?
    ");
    $stmt->execute([$role['id']]);
    $rolePermissions[$role['id']] = array_column($stmt->fetchAll(), 'permission_id');
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
            max-width: 1600px;
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
        }
        .card-header h2 {
            color: #2c3e50;
            font-size: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .role-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
        }
        .role-superadmin { background: #e74c3c; color: white; }
        .role-admin { background: #3498db; color: white; }
        .role-operator { background: #27ae60; color: white; }
        .role-viewer { background: #95a5a6; color: white; }
        
        .card-body {
            padding: 25px;
        }
        .permission-groups {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        .permission-group {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            border: 2px solid #e0e0e0;
        }
        .permission-group h3 {
            color: #2c3e50;
            font-size: 16px;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 2px solid #ddd;
            text-transform: capitalize;
        }
        .permission-item {
            padding: 8px 0;
            display: flex;
            align-items: center;
        }
        .permission-item input[type="checkbox"] {
            margin-right: 10px;
            cursor: pointer;
            width: 18px;
            height: 18px;
        }
        .permission-item label {
            cursor: pointer;
            font-size: 13px;
            color: #555;
            flex: 1;
        }
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            margin-right: 10px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-success {
            background: #27ae60;
            color: white;
        }
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        .actions {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
        }
        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .info-box i {
            color: #2196f3;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1><i class="fas fa-shield-alt"></i> <?= $pageTitle ?></h1>
            </div>
            <div class="user-info">
                <i class="fas fa-user"></i> <strong><?= htmlspecialchars($currentUser['username']) ?></strong> |
                <a href="users_management.php" style="color: #667eea; text-decoration: none;">
                    <i class="fas fa-users"></i> Kullanıcılar
                </a> |
                <a href="otomatikarama.php" style="color: #667eea; text-decoration: none;">
                    <i class="fas fa-home"></i> Ana Sayfa
                </a> |
                <a href="logout.php" style="color: #e74c3c; text-decoration: none;">
                    <i class="fas fa-sign-out-alt"></i> Çıkış
                </a>
            </div>
        </div>

        <div class="info-box">
            <i class="fas fa-info-circle"></i>
            <strong>Bilgi:</strong> Her rol için izinleri seçin ve kaydedin. Superadmin rolünün izinleri değiştirilemez.
        </div>

        <?php foreach ($allRoles as $role): ?>
        <div class="card">
            <div class="card-header">
                <h2>
                    <i class="fas fa-user-shield"></i>
                    <?= htmlspecialchars($role['role_name']) ?>
                    <span class="role-badge role-<?= $role['role_code'] ?>">
                        <?= $role['role_code'] ?>
                    </span>
                </h2>
                <?php if ($role['description']): ?>
                <p style="color: #666; font-size: 14px; margin-top: 5px;">
                    <?= htmlspecialchars($role['description']) ?>
                </p>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <form class="permission-form" data-role-id="<?= $role['id'] ?>" data-role-code="<?= $role['role_code'] ?>">
                    <div class="permission-groups">
                        <?php foreach ($groupedPermissions as $group => $permissions): ?>
                        <div class="permission-group">
                            <h3>
                                <i class="fas fa-<?= $group === 'import' ? 'file-import' : ($group === 'template' ? 'file-alt' : ($group === 'user' ? 'users' : ($group === 'report' ? 'chart-bar' : 'cog'))) ?>"></i>
                                <?= ucfirst($group) ?>
                            </h3>
                            <?php foreach ($permissions as $perm): ?>
                            <div class="permission-item">
                                <input 
                                    type="checkbox" 
                                    name="permissions[]" 
                                    value="<?= $perm['id'] ?>"
                                    id="perm_<?= $role['id'] ?>_<?= $perm['id'] ?>"
                                    <?= in_array($perm['id'], $rolePermissions[$role['id']]) ? 'checked' : '' ?>
                                    <?= $role['role_code'] === 'superadmin' ? 'disabled' : '' ?>
                                >
                                <label for="perm_<?= $role['id'] ?>_<?= $perm['id'] ?>">
                                    <?= htmlspecialchars($perm['permission_name']) ?>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if ($role['role_code'] !== 'superadmin'): ?>
                    <div style="margin-top: 20px; text-align: right;">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> İzinleri Kaydet
                        </button>
                    </div>
                    <?php else: ?>
                    <div style="margin-top: 20px; padding: 10px; background: #fff3cd; border-radius: 5px; color: #856404;">
                        <i class="fas fa-lock"></i> Superadmin rolünün izinleri değiştirilemez.
                    </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <script>
        // Form submit handler
        document.querySelectorAll('.permission-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const roleId = this.dataset.roleId;
                const roleCode = this.dataset.roleCode;
                
                if (roleCode === 'superadmin') {
                    alert('Superadmin rolü değiştirilemez!');
                    return;
                }
                
                const formData = new FormData(this);
                formData.append('role_id', roleId);
                
                fetch('api/update_role_permissions.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert('✅ İzinler başarıyla güncellendi!');
                    } else {
                        alert('❌ Hata: ' + data.message);
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    alert('❌ Bir hata oluştu!');
                });
            });
        });
    </script>
</body>
</html>
