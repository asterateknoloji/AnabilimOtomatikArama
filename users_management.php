<?php
// UTF-8 encoding ayarla
header('Content-Type: text/html; charset=utf-8');

require_once 'includes/check_auth.php';

// Veritabanı karakter setini ayarla
$pdo->exec("SET NAMES utf8");
$pdo->exec("SET CHARACTER SET utf8");

// Sadece superadmin ve admin erişebilir
requirePermission('user.view');

// Sayfa görüntüleme logu
logActivity('view', 'user', 'Kullanıcı Yönetimi sayfası görüntülendi');

$pageTitle = 'Kullanıcı Yönetimi';

// Tüm kullanıcıları çek
$stmt = $pdo->query("
    SELECT 
        u.*,
        GROUP_CONCAT(DISTINCT r.role_name ORDER BY r.role_name SEPARATOR ', ') as roles
    FROM users u
    LEFT JOIN USER_ROLES ur ON u.id = ur.user_id
    LEFT JOIN ROLES r ON ur.role_id = r.id
    GROUP BY u.id
    ORDER BY u.id
");
$users = $stmt->fetchAll();

// Tüm rolleri çek
$rolesStmt = $pdo->query("SELECT * FROM ROLES WHERE is_active = 1 ORDER BY id");
$allRoles = $rolesStmt->fetchAll();
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
            max-width: 1400px;
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
        .actions {
            margin-bottom: 20px;
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
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .btn-secondary {
            background: #95a5a6;
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
        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }
        .card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .table-container {
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        thead {
            background: #f8f9fa;
        }
        th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #2c3e50;
            border-bottom: 2px solid #e0e0e0;
        }
        td {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
        }
        tr:hover {
            background: #f8f9fa;
        }
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            margin-right: 5px;
        }
        .badge-superadmin {
            background: #e74c3c;
            color: white;
        }
        .badge-admin {
            background: #3498db;
            color: white;
        }
        .badge-operator {
            background: #27ae60;
            color: white;
        }
        .badge-viewer {
            background: #95a5a6;
            color: white;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            z-index: 1000;
            overflow: auto;
        }
        .modal-content {
            background: white;
            max-width: 600px;
            margin: 50px auto;
            border-radius: 15px;
            padding: 30px;
            position: relative;
        }
        .modal-close {
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 28px;
            cursor: pointer;
            color: #999;
        }
        .modal-close:hover {
            color: #333;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
        }
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }
        .checkbox-group {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .checkbox-item {
            flex: 0 0 calc(50% - 5px);
        }
        .checkbox-item label {
            display: flex;
            align-items: center;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .checkbox-item label:hover {
            background: #e9ecef;
        }
        .checkbox-item input {
            width: auto;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1><i class="fas fa-users"></i> <?= $pageTitle ?></h1>
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

        <div class="actions">
            <?php if (hasPermission('user.create')): ?>
            <button class="btn btn-primary" onclick="openModal('addUserModal')">
                <i class="fas fa-plus"></i> Yeni Kullanıcı Ekle
            </button>
            <?php endif; ?>
            <a href="activity_logs.php" class="btn btn-secondary">
                <i class="fas fa-history"></i> Aktivite Logları
            </a>
        </div>

        <div class="card">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Kullanıcı Adı</th>
                            <th>Ad Soyad</th>
                            <th>Dahili</th>
                            <th>Roller</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= $user['id'] ?></td>
                            <td><strong><?= htmlspecialchars($user['username']) ?></strong></td>
                            <td><?= htmlspecialchars($user['adi'] . ' ' . $user['soyadi']) ?></td>
                            <td><?= htmlspecialchars($user['dahili'] ?? '-') ?></td>
                            <td>
                                <?php 
                                try {
                                    if (isset($auth)) {
                                        $userRoles = $auth->getUserRoles($user['id']);
                                        foreach ($userRoles as $roleCode) {
                                            $badgeClass = 'badge-' . $roleCode;
                                            echo "<span class='badge $badgeClass'>$roleCode</span> ";
                                        }
                                        if (empty($userRoles)) {
                                            echo '<span style="color: #999;">Rol atanmamış</span>';
                                        }
                                    } else {
                                        echo '<span style="color: red;">Auth yok!</span>';
                                    }
                                } catch (Exception $e) {
                                    echo '<span style="color: red;">Hata: ' . htmlspecialchars($e->getMessage()) . '</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <?php if (hasPermission('user.assign_role')): ?>
                                <button class="btn btn-success btn-sm" onclick="editRoles(<?= $user['id'] ?>, '<?= htmlspecialchars($user['username']) ?>')">
                                    <i class="fas fa-user-tag"></i> Rol Düzenle
                                </button>
                                <?php endif; ?>
                                <?php if (hasPermission('user.edit')): ?>
                                <button class="btn btn-primary btn-sm" onclick="editUser(<?= $user['id'] ?>)">
                                    <i class="fas fa-edit"></i> Düzenle
                                </button>
                                <?php endif; ?>
                                <?php if (hasPermission('user.delete') && $user['id'] != $currentUser['id']): ?>
                                <button class="btn btn-danger btn-sm" onclick="deleteUser(<?= $user['id'] ?>, '<?= htmlspecialchars($user['username'], ENT_QUOTES) ?>')">
                                    <i class="fas fa-trash"></i> Sil
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal: Rol Düzenleme -->
    <div id="roleModal" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeModal('roleModal')">&times;</span>
            <h2><i class="fas fa-user-tag"></i> Rol Düzenleme</h2>
            <p style="color: #666; margin-bottom: 20px;">Kullanıcı: <strong id="roleUsername"></strong></p>
            
            <form id="roleForm" method="POST" action="api/update_user_roles.php">
                <input type="hidden" name="user_id" id="roleUserId">
                
                <div class="checkbox-group">
                    <?php foreach ($allRoles as $role): ?>
                    <div class="checkbox-item">
                        <label>
                            <input type="checkbox" name="roles[]" value="<?= $role['id'] ?>">
                            <?= htmlspecialchars($role['role_name']) ?> (<?= $role['role_code'] ?>)
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div style="margin-top: 25px; text-align: right;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('roleModal')">İptal</button>
                    <button type="submit" class="btn btn-primary">Kaydet</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Yeni Kullanıcı Ekle -->
    <div id="addUserModal" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeModal('addUserModal')">&times;</span>
            <h2><i class="fas fa-user-plus"></i> Yeni Kullanıcı Ekle</h2>
            
            <form id="addUserForm" method="POST" action="api/create_user.php">
                <div class="form-group">
                    <label>Kullanıcı Adı *</label>
                    <input type="text" name="username" required>
                </div>
                
                <div class="form-group">
                    <label>Şifre *</label>
                    <input type="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label>Ad *</label>
                    <input type="text" name="adi" required>
                </div>
                
                <div class="form-group">
                    <label>Soyad *</label>
                    <input type="text" name="soyadi" required>
                </div>
                
                <div class="form-group">
                    <label>Dahili</label>
                    <input type="text" name="dahili">
                </div>
                
                <div class="form-group">
                    <label>Roller *</label>
                    <div class="checkbox-group">
                        <?php foreach ($allRoles as $role): ?>
                        <div class="checkbox-item">
                            <label>
                                <input type="checkbox" name="roles[]" value="<?= $role['id'] ?>">
                                <?= htmlspecialchars($role['role_name']) ?> (<?= $role['role_code'] ?>)
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div style="margin-top: 25px; text-align: right;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addUserModal')">İptal</button>
                    <button type="submit" class="btn btn-primary">Kaydet</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Kullanıcı Düzenle -->
    <div id="editUserModal" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeModal('editUserModal')">&times;</span>
            <h2><i class="fas fa-user-edit"></i> Kullanıcı Düzenle</h2>
            
            <form id="editUserForm" method="POST" action="api/update_user.php">
                <input type="hidden" name="user_id" id="editUserId">
                
                <div class="form-group">
                    <label>Kullanıcı Adı</label>
                    <input type="text" id="editUsername" readonly style="background: #f5f5f5;">
                </div>
                
                <div class="form-group">
                    <label>Yeni Şifre (Değiştirmek için doldurun)</label>
                    <input type="password" name="password" id="editPassword">
                </div>
                
                <div class="form-group">
                    <label>Ad *</label>
                    <input type="text" name="adi" id="editAdi" required>
                </div>
                
                <div class="form-group">
                    <label>Soyad *</label>
                    <input type="text" name="soyadi" id="editSoyadi" required>
                </div>
                
                <div class="form-group">
                    <label>Dahili</label>
                    <input type="text" name="dahili" id="editDahili">
                </div>
                
                <div class="form-group">
                    <label>Roller *</label>
                    <div class="checkbox-group">
                        <?php foreach ($allRoles as $role): ?>
                        <div class="checkbox-item">
                            <label>
                                <input type="checkbox" name="roles[]" value="<?= $role['id'] ?>" class="edit-role-checkbox">
                                <?= htmlspecialchars($role['role_name']) ?> (<?= $role['role_code'] ?>)
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div style="margin-top: 25px; text-align: right;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editUserModal')">İptal</button>
                    <button type="submit" class="btn btn-primary">Kaydet</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function editRoles(userId, username) {
            document.getElementById('roleUserId').value = userId;
            document.getElementById('roleUsername').textContent = username;
            
            // Mevcut rolleri fetch et
            fetch('api/get_user_roles.php?user_id=' + userId)
                .then(res => res.json())
                .then(data => {
                    console.log('Roles data:', data);
                    
                    // Tüm checkbox'ları temizle
                    document.querySelectorAll('#roleForm input[type="checkbox"]').forEach(cb => {
                        cb.checked = false;
                    });
                    
                    // Kullanıcının rollerini seç
                    if (data.success && data.roles) {
                        data.roles.forEach(roleId => {
                            const checkbox = document.querySelector(`#roleForm input[value="${roleId}"]`);
                            if (checkbox) checkbox.checked = true;
                        });
                    }
                    
                    openModal('roleModal');
                })
                .catch(err => {
                    console.error('Fetch error:', err);
                    alert('Hata: ' + err);
                });
        }
        
        function editUser(userId) {
            // Kullanıcı bilgilerini fetch et
            fetch('api/get_user.php?user_id=' + userId)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('editUserId').value = data.user.id;
                        document.getElementById('editUsername').value = data.user.username;
                        document.getElementById('editAdi').value = data.user.adi;
                        document.getElementById('editSoyadi').value = data.user.soyadi;
                        document.getElementById('editDahili').value = data.user.dahili || '';
                        document.getElementById('editPassword').value = '';
                        
                        // Rolleri fetch et ve checkbox'ları işaretle
                        fetch('api/get_user_roles.php?user_id=' + userId)
                            .then(res => res.json())
                            .then(rolesData => {
                                // Tüm checkbox'ları temizle
                                document.querySelectorAll('.edit-role-checkbox').forEach(cb => {
                                    cb.checked = false;
                                });
                                
                                // Kullanıcının rollerini işaretle
                                if (rolesData.success && rolesData.roles) {
                                    rolesData.roles.forEach(roleId => {
                                        const checkbox = document.querySelector(`.edit-role-checkbox[value="${roleId}"]`);
                                        if (checkbox) checkbox.checked = true;
                                    });
                                }
                                
                                openModal('editUserModal');
                            });
                    } else {
                        alert('Hata: ' + data.message);
                    }
                })
                .catch(err => {
                    console.error('Fetch error:', err);
                    alert('Hata: ' + err);
                });
        }

        function deleteUser(userId, username) {
            if (!confirm(`Kullanıcıyı silmek istediğinizden emin misiniz?\n\nKullanıcı: ${username}\n\n⚠️ Bu işlem geri alınamaz!`)) {
                return;
            }
            
            fetch('api/delete_user.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'user_id=' + userId
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Kullanıcı başarıyla silindi!');
                    location.reload();
                } else {
                    alert('❌ Hata: ' + data.message);
                }
            })
            .catch(err => {
                console.error('Delete error:', err);
                alert('❌ Hata: ' + err);
            });
        }

        // Form submit
        document.getElementById('roleForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('api/update_user_roles.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('Roller güncellendi!');
                    location.reload();
                } else {
                    alert('Hata: ' + data.message);
                }
            })
            .catch(err => alert('Hata: ' + err));
        });

        // Yeni kullanıcı form submit
        document.getElementById('addUserForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('api/create_user.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('Kullanıcı başarıyla oluşturuldu!');
                    location.reload();
                } else {
                    alert('Hata: ' + data.message);
                }
            })
            .catch(err => {
                console.error('Fetch error:', err);
                alert('Hata: ' + err);
            });
        });

        // Kullanıcı düzenle form submit
        document.getElementById('editUserForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('api/update_user.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('Kullanıcı başarıyla güncellendi!');
                    location.reload();
                } else {
                    alert('Hata: ' + data.message);
                }
            })
            .catch(err => {
                console.error('Fetch error:', err);
                alert('Hata: ' + err);
            });
        });

        // Modal dışına tıklayınca kapat
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>
