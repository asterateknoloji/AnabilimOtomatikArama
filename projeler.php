<?php
require_once 'includes/check_auth.php';

// İzin kontrolü
requirePermission('project.view');

// Sayfa görüntüleme logu
logActivity('view', 'project', 'Projeler sayfası görüntülendi');

require_once 'config.php';
require_once 'includes/ProjectManager.php';
require_once 'includes/TemplateManager.php';

$projectManager = new ProjectManager($pdo);
$templateManager = new TemplateManager($pdo);

// Proje ekleme
if (isset($_POST['add_project'])) {
    requirePermission('project.create');
    try {
        $projectId = $projectManager->createProject(
            $_POST['project_name'],
            $_POST['description'] ?? '',
            $_POST['template_id'] ?? null,
            $_POST['caller_id'] ?? null,
            $_POST['trunk'] ?? null
        );
        logActivity('create', 'project', "Yeni proje oluşturuldu: {$_POST['project_name']}", $projectId);
        $message = 'Proje başarıyla oluşturuldu!';
        $messageType = 'success';
    } catch (Exception $e) {
        $message = 'Hata: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Proje silme
if (isset($_POST['delete_project'])) {
    requirePermission('project.delete');
    try {
        $projectId = $_POST['project_id'];
        // Önce proje bilgisini al
        $projectInfo = $projectManager->getProject($projectId);
        $projectManager->deleteProject($projectId);
        logActivity('delete', 'project', "Proje silindi: {$projectInfo['ProjectName']}", $projectId);
        $message = 'Proje silindi!';
        $messageType = 'success';
    } catch (Exception $e) {
        $message = 'Hata: ' . $e->getMessage();
        $messageType = 'error';
    }
}

$projects = $projectManager->getProjects(false);
$templates = $templateManager->getTemplates();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proje Yönetimi</title>
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
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 30px;
        }
        h1 {
            color: #667eea;
            font-size: 32px;
            margin-bottom: 10px;
        }
        .nav-links {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        .nav-links a {
            padding: 10px 20px;
            background: #f0f0f0;
            border-radius: 8px;
            text-decoration: none;
            color: #333;
            transition: all 0.3s;
        }
        .nav-links a:hover {
            background: #667eea;
            color: white;
        }
        .card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
        }
        input, select, textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 15px;
        }
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #667eea;
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
        .btn-danger {
            background: #dc3545;
            color: white;
            padding: 8px 15px;
            font-size: 14px;
        }
        .btn-danger:hover {
            background: #c82333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            table-layout: fixed;
        }
        th, td {
            padding: 10px 6px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
            font-size: 13px;
            word-wrap: break-word;
        }
        th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: 600;
            font-size: 12px;
            padding: 12px 6px;
        }
        tr:hover {
            background: #f0f4ff;
        }
        /* Responsive kolon genişlikleri */
        table th:nth-child(1), table td:nth-child(1) { width: 4%; text-align: center; }
        table th:nth-child(2), table td:nth-child(2) { width: 18%; font-weight: 600; }
        table th:nth-child(3), table td:nth-child(3) { width: 16%; }
        table th:nth-child(4), table td:nth-child(4) { width: 14%; font-family: 'Courier New', monospace; font-size: 11px; }
        table th:nth-child(5), table td:nth-child(5) { width: 14%; font-family: 'Courier New', monospace; font-size: 11px; }
        table th:nth-child(6), table td:nth-child(6) { width: 12%; font-size: 11px; }
        table th:nth-child(7), table td:nth-child(7) { width: 8%; text-align: center; }
        table th:nth-child(8), table td:nth-child(8) { width: 14%; text-align: center; padding: 6px 4px; }
        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        .badge-danger {
            background: #f8d7da;
            color: #721c24;
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
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-top: 15px;
        }
        .stat-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: #667eea;
        }
        .stat-label {
            font-size: 13px;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📁 Proje Yönetimi</h1>
            <p class="subtitle">Farklı projeler için arama kampanyaları oluşturun ve yönetin</p>
            
            <div class="nav-links">
                <a href="otomatikarama.php">🏠 Ana Sayfa</a>
                <a href="arama_yonetimi.php">📋 Arama Yönetimi</a>
                <a href="sablonlar.php">📝 Şablon Yönetimi</a>
            </div>
        </div>

        <?php if (isset($message)): ?>
        <div class="alert alert-<?= $messageType ?>">
            <?= $message ?>
        </div>
        <?php endif; ?>

        <?php if (hasPermission('project.create')): ?>
        <!-- Yeni Proje Oluştur -->
        <div class="card">
            <h2 style="color: #667eea; margin-bottom: 20px;">➕ Yeni Proje Oluştur</h2>
            
            <form method="post">
                <div class="form-group">
                    <label>Proje Adı *</label>
                    <input type="text" name="project_name" required placeholder="Örn: Ocak 2026 Tahsilat Kampanyası">
                </div>
                
                <div class="form-group">
                    <label>Açıklama</label>
                    <textarea name="description" rows="3" placeholder="Proje hakkında kısa açıklama..."></textarea>
                </div>
                
                <div class="form-group">
                    <label>Varsayılan Mesaj Şablonu</label>
                    <select name="template_id">
                        <option value="">-- Seçiniz --</option>
                        <?php foreach ($templates as $tmpl): ?>
                        <option value="<?= $tmpl['id'] ?>"><?= htmlspecialchars($tmpl['TemplateName']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>📞 Arayan Numara (Caller ID)</label>
                    <input type="text" name="caller_id" placeholder="Örn: 02129999999" maxlength="20" value="02129999999">
                    <small style="color: #666; display: block; margin-top: 5px;">
                        💡 Boş bırakırsanız varsayılan numara kullanılır (config.php)
                    </small>
                </div>
                
                <div class="form-group">
                    <label>📡 Trunk (Giden Hat)</label>
                    <input type="text" name="trunk" placeholder="Örn: 902122130686" maxlength="50">
                    <small style="color: #666; display: block; margin-top: 5px;">
                        💡 Asterisk'te kullanılacak giden hat. Boş bırakırsanız otomatik seçilir.<br>
                        📌 Örnek: <code>902122130686</code> → Call file: <code>Channel: SIP/902122130686/05307711717</code>
                    </small>
                </div>
                
                <button type="submit" name="add_project" class="btn btn-primary">
                    💾 Proje Oluştur
                </button>
            </form>
        </div>
        <?php endif; ?>

        <!-- Mevcut Projeler -->
        <div class="card">
            <h2 style="color: #667eea; margin-bottom: 20px;">📋 Mevcut Projeler</h2>
            
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Proje Adı</th>
                        <th>Şablon</th>
                        <th>📞 Caller ID</th>
                        <th>📡 Trunk</th>
                        <th>Oluşturma Tarihi</th>
                        <th>Durum</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($projects as $project): ?>
                    <?php $stats = $projectManager->getProjectStats($project['id']); ?>
                    <tr>
                        <td><?= $project['id'] ?></td>
                        <td>
                            <div style="display: flex; flex-direction: column; gap: 8px;">
                                <strong style="color: #2c3e50; font-size: 13px;">📁 <?= htmlspecialchars($project['ProjectName']) ?></strong>
                                <div style="display: flex; gap: 8px; flex-wrap: wrap; font-size: 10px;">
                                    <span style="background: #f8f9fa; padding: 2px 6px; border-radius: 3px; color: #495057;">
                                        📊 <strong><?= $stats['total'] ?></strong>
                                    </span>
                                    <span style="background: #d4edda; padding: 2px 6px; border-radius: 3px; color: #155724;">
                                        ✓ <strong><?= $stats['completed'] ?></strong>
                                    </span>
                                    <span style="background: #fff3cd; padding: 2px 6px; border-radius: 3px; color: #856404;">
                                        ⏳ <strong><?= $stats['calling'] ?></strong>
                                    </span>
                                    <span style="background: #f8d7da; padding: 2px 6px; border-radius: 3px; color: #721c24;">
                                        ✗ <strong><?= $stats['failed'] ?></strong>
                                    </span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php
                            if ($project['TemplateId']) {
                                $tmpl = $templateManager->getTemplate($project['TemplateId']);
                                echo '<span style="background: #e7f3ff; padding: 5px 10px; border-radius: 4px; color: #0066cc; font-weight: 600; font-size: 11px; display: inline-block;">📝 ' . htmlspecialchars($tmpl['TemplateName'] ?? '-') . '</span>';
                            } else {
                                echo '<span style="color: #999;">-</span>';
                            }
                            ?>
                        </td>
                        <td>
                            <?php if (!empty($project['CallerID'])): ?>
                            <strong style="color: #667eea;">📞 <?= htmlspecialchars($project['CallerID']) ?></strong>
                            <?php else: ?>
                            <span style="color: #999;">Varsayılan</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($project['Trunk'])): ?>
                            <strong style="color: #28a745;">📡 <?= htmlspecialchars($project['Trunk']) ?></strong>
                            <?php else: ?>
                            <span style="color: #999;">Otomatik</span>
                            <?php endif; ?>
                        </td>
                        <td><?= date('d.m.Y H:i', strtotime($project['CreatedDate'])) ?></td>
                        <td>
                            <?php if ($project['Active']): ?>
                            <span class="badge badge-success">Aktif</span>
                            <?php else: ?>
                            <span class="badge badge-danger">Pasif</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (hasPermission('project.edit')): ?>
                            <button onclick="openProjectModal(<?= $project['id'] ?>, '<?= htmlspecialchars($project['CallerID'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($project['Trunk'] ?? '', ENT_QUOTES) ?>')" class="btn" style="background: #17a2b8; color: white; font-size: 12px; padding: 6px 12px; margin-right: 3px;">
                                ✏️
                            </button>
                            <?php endif; ?>
                            <?php if (hasPermission('project.delete')): ?>
                                <?php if ($project['id'] == 1 || $project['ProjectName'] == 'Varsayılan Proje'): ?>
                                    <button class="btn" style="background: #e9ecef; color: #6c757d; cursor: not-allowed; font-size: 11px; padding: 6px 10px;" disabled title="Varsayılan Proje silinemez">
                                        🔒
                                    </button>
                                <?php else: ?>
                                    <button onclick="deleteProject(<?= $project['id'] ?>, '<?= htmlspecialchars($project['ProjectName'], ENT_QUOTES) ?>')" class="btn btn-danger" style="font-size: 12px; padding: 6px 12px;">
                                        🗑️
                                    </button>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Proje Silme Modal -->
    <div id="deleteModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 10000; align-items: center; justify-content: center;">
        <div style="background: white; padding: 40px; border-radius: 15px; max-width: 500px; width: 90%; box-shadow: 0 10px 40px rgba(0,0,0,0.3); text-align: center;">
            <div style="font-size: 60px; margin-bottom: 20px;">🗑️</div>
            <h2 style="color: #dc3545; margin-bottom: 15px;">Projeyi Sil?</h2>
            <p style="font-size: 18px; margin-bottom: 10px;">
                <strong id="deleteProjectName"></strong>
            </p>
            <p style="color: #666; margin-bottom: 30px;">Bu işlem geri alınamaz!</p>
            
            <div style="display: flex; gap: 15px; justify-content: center;">
                <button onclick="cancelDelete()" class="btn" style="background: #6c757d; color: white; padding: 15px 30px; font-size: 16px;">
                    ✖️ İptal
                </button>
                <button onclick="confirmDelete()" class="btn" style="background: #dc3545; color: white; padding: 15px 30px; font-size: 16px;">
                    🗑️ Evet, Sil
                </button>
            </div>
        </div>
    </div>

    <!-- Proje Düzenleme Modal -->
    <div id="projectModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 9999; align-items: center; justify-content: center;">
        <div style="background: white; padding: 30px; border-radius: 15px; max-width: 600px; width: 90%; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
            <h2 style="color: #667eea; margin-bottom: 20px;">✏️ Proje Bilgilerini Düzenle</h2>
            
            <div class="form-group">
                <label>📞 Arayan Numara (Caller ID)</label>
                <input type="text" id="modal_caller_id" placeholder="Örn: 02129999999" maxlength="20" style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 16px;">
                <small style="color: #666; display: block; margin-top: 8px;">
                    💡 10-15 haneli numara giriniz (sadece rakam)
                </small>
            </div>
            
            <div class="form-group" style="margin-top: 20px;">
                <label>📡 Trunk (Giden Hat)</label>
                <input type="text" id="modal_trunk" placeholder="Örn: 902122130686" maxlength="50" style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 16px;">
                <small style="color: #666; display: block; margin-top: 8px;">
                    💡 Asterisk'te kullanılacak giden hat bilgisi<br>
                    📌 Örnek: <strong>902122130686</strong> → Channel: <code>SIP/902122130686/05307711717</code>
                </small>
            </div>
            
            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 25px;">
                <button onclick="closeProjectModal()" class="btn" style="background: #6c757d; color: white;">
                    ✖️ İptal
                </button>
                <button onclick="saveProject()" class="btn btn-primary">
                    💾 Kaydet
                </button>
            </div>
        </div>
    </div>

    <script>
        let currentProjectId = null;

        function openProjectModal(projectId, currentCallerId, currentTrunk) {
            currentProjectId = projectId;
            document.getElementById('modal_caller_id').value = currentCallerId;
            document.getElementById('modal_trunk').value = currentTrunk;
            document.getElementById('projectModal').style.display = 'flex';
        }

        function closeProjectModal() {
            document.getElementById('projectModal').style.display = 'none';
            currentProjectId = null;
        }

        function saveProject() {
            if (!currentProjectId) {
                alert('❌ Proje ID bulunamadı!');
                return;
            }
            
            const callerId = document.getElementById('modal_caller_id').value.trim();
            const trunk = document.getElementById('modal_trunk').value.trim();
            
            // Validation
            if (callerId && !/^[0-9]{10,15}$/.test(callerId)) {
                alert('❌ Caller ID 10-15 haneli rakamlardan oluşmalıdır!');
                return;
            }
            
            // AJAX ile kaydet
            fetch('update_caller_id.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'project_id=' + currentProjectId + '&caller_id=' + encodeURIComponent(callerId) + '&trunk=' + encodeURIComponent(trunk),
                credentials: 'same-origin'
            })
            .then(response => {
                console.log('HTTP Status:', response.status);
                if (!response.ok) {
                    throw new Error('HTTP error! status: ' + response.status);
                }
                return response.text();
            })
            .then(text => {
                console.log('Raw Response:', text);
                try {
                    const data = JSON.parse(text);
                    console.log('Parsed Data:', data);
                    if (data.success) {
                        alert(data.message);
                        closeProjectModal();
                        location.reload();
                    } else {
                        alert('❌ Hata: ' + data.message);
                    }
                } catch(e) {
                    console.error('JSON Parse Error:', e);
                    alert('❌ Response parse hatası. Console\'a bak.');
                }
            })
            .catch(error => {
                console.error('AJAX Error:', error);
                console.error('Error details:', error);
                alert('❌ Bağlantı hatası: ' + error.message);
            });
        }

        // ESC tuşu ile modal kapat
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeProjectModal();
            }
        });

        // Proje sil
        let deleteProjectId = null;
        let deleteProjectName = null;
        
        function deleteProject(projectId, projectName) {
            console.log('deleteProject called:', projectId, projectName);
            deleteProjectId = projectId;
            deleteProjectName = projectName;
            
            // Custom confirm modal göster
            document.getElementById('deleteModal').style.display = 'flex';
            document.getElementById('deleteProjectName').textContent = projectName;
        }
        
        function confirmDelete() {
            console.log('Starting delete...');
            document.getElementById('deleteModal').style.display = 'none';

            fetch('delete_project.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'project_id=' + deleteProjectId,
                credentials: 'same-origin'
            })
            .then(response => {
                console.log('Delete HTTP Status:', response.status);
                if (!response.ok) {
                    throw new Error('HTTP error! status: ' + response.status);
                }
                return response.text();
            })
            .then(text => {
                console.log('Delete Raw Response:', text);
                try {
                    const data = JSON.parse(text);
                    console.log('Delete Parsed Data:', data);
                    if (data.success) {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert('❌ Hata: ' + data.message);
                    }
                } catch(e) {
                    console.error('JSON Parse Error:', e);
                    alert('❌ Response parse hatası.');
                }
            })
            .catch(error => {
                console.error('Delete Error:', error);
                alert('❌ Silme hatası: ' + error.message);
            });
        }
        
        function cancelDelete() {
            console.log('User cancelled delete');
            document.getElementById('deleteModal').style.display = 'none';
            deleteProjectId = null;
            deleteProjectName = null;
        }
    </script>
</body>
</html>
