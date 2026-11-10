<?php
require_once 'includes/check_auth.php';

// İzin kontrolü
requirePermission('template.view');

// Sayfa görüntüleme logu
logActivity('view', 'template', 'Şablonlar sayfası görüntülendi');

require_once 'config.php';
require_once 'includes/TemplateManager.php';

$templateManager = new TemplateManager($pdo);

// Şablon kaydet/güncelle
if (isset($_POST['save_template'])) {
    try {
        $content = json_decode($_POST['template_data'], true);
        
        // Tuş davranışları
        $actions = [
            'key1_action' => $_POST['key1_action'] ?? 'replay',
            'key1_transfer_number' => $_POST['key1_transfer_number'] ?? null,
            'key1_audio_file' => $_POST['key1_audio_file'] ?? 'otomatik/transfer',
            'key2_action' => $_POST['key2_action'] ?? 'transfer',
            'key2_transfer_number' => $_POST['key2_transfer_number'] ?? 'TAHSILAT',
            'key2_audio_file' => $_POST['key2_audio_file'] ?? 'otomatik/transfer',
            'invalid_key_action' => $_POST['invalid_key_action'] ?? 'use_key1',
            'invalid_key_audio_file' => $_POST['invalid_key_audio_file'] ?? 'otomatik/invalid',
            'timeout_action' => $_POST['timeout_action'] ?? 'use_key1_and_hangup',
            'timeout_audio_file' => $_POST['timeout_audio_file'] ?? 'otomatik/goodbye',
            'timeout_seconds' => $_POST['timeout_seconds'] ?? 5
        ];
        
        if (isset($_POST['template_id']) && $_POST['template_id']) {
            // Güncelle
            requirePermission('template.edit');
            $templateId = $_POST['template_id'];
            $templateName = $_POST['template_name'];
            $templateManager->updateTemplate(
                $templateId,
                $templateName,
                $_POST['template_description'],
                $content,
                $actions
            );
            
            // Log
            logActivity('update', 'template', "Şablon güncellendi: {$templateName}", $templateId);
            
            $message = '✅ Şablon güncellendi ve Asterisk dialplan oluşturuldu!';
        } else {
            // Yeni oluştur
            requirePermission('template.create');
            $templateName = $_POST['template_name'];
            $templateId = $templateManager->createTemplate(
                $templateName,
                $_POST['template_description'],
                $content,
                $actions
            );
            
            // Log
            logActivity('create', 'template', "Yeni şablon oluşturuldu: {$templateName}", $templateId);
            
            $message = '✅ Şablon oluşturuldu ve Asterisk dialplan oluşturuldu!';
        }
        
        $messageType = 'success';
    } catch (Exception $e) {
        $message = 'Hata: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Şablon sil
if (isset($_POST['delete_template'])) {
    requirePermission('template.delete');
    try {
        $templateId = $_POST['template_id'];
        // Önce şablon bilgisini al
        $templateInfo = $templateManager->getTemplate($templateId);
        $templateManager->deleteTemplate($templateId);
        
        // Log
        logActivity('delete', 'template', "Şablon silindi: {$templateInfo['TemplateName']}", $templateId);
        
        $message = 'Şablon silindi!';
        $messageType = 'success';
    } catch (Exception $e) {
        $message = 'Hata: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Düzenlenecek şablon
$editTemplate = null;
if (isset($_GET['edit'])) {
    $editTemplate = $templateManager->getTemplate($_GET['edit']);
    $editTemplateContent = json_decode($editTemplate['TemplateContent'], true);
} else {
    // Varsayılan şablon
    $editTemplateContent = [
        ['type' => 'text', 'value' => 'Merhaba'],
        ['type' => 'field', 'value' => 'Adi'],
        ['type' => 'text', 'value' => 'Bugünkü borcunuz'],
        ['type' => 'field', 'value' => 'Tutar'],
        ['type' => 'text', 'value' => 'Türk Lirasıdır.'],
        ['type' => 'field', 'value' => 'Metin1'],
        ['type' => 'text', 'value' => 'Tekrar dinlemek için 1, operatöre bağlanmak için 2 tuşuna basınız.']
    ];
}

$templates = $templateManager->getTemplates(false);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Şablon Yönetimi</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 1400px; margin: 0 auto; }
        .header {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 30px;
        }
        h1 { color: #667eea; font-size: 32px; margin-bottom: 10px; }
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
        }
        .nav-links a:hover {
            background: #667eea;
            color: white;
        }
        .main-grid {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 30px;
            align-items: start;
        }
        .sidebar-column {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .template-list-item {
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 8px;
            border: 2px solid #e0e0e0;
            cursor: pointer;
            transition: all 0.3s;
        }
        .template-list-item:hover {
            border-color: #667eea;
            background: #f8f9ff;
        }
        .template-list-item.active {
            border-color: #667eea;
            background: #f0f2ff;
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
        .btn-success {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
        }
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(17, 153, 142, 0.4);
        }
        .btn-danger {
            background: #dc3545;
            color: white;
            font-size: 14px;
            padding: 8px 15px;
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
        input, textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 15px;
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
        .template-builder {
            min-height: 300px;
            border: 2px dashed #ddd;
            border-radius: 10px;
            padding: 20px;
            background: #f8f9fa;
            margin-bottom: 20px;
        }
        .template-item {
            background: white;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .template-item.field { border-left: 4px solid #667eea; }
        .template-item.text { border-left: 4px solid #28a745; }
        .template-item.audio { border-left: 4px solid #f5576c; }
        .template-item {
            cursor: move;
            transition: all 0.3s;
        }
        .template-item:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transform: translateX(5px);
        }
        .template-item.dragging {
            opacity: 0.5;
            transform: scale(0.95);
        }
        .template-item.drag-over {
            border-top: 3px solid #667eea;
        }
        .template-item .content { flex: 1; }
        .template-item .content input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .template-item .label {
            background: #667eea;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 13px;
        }
        .template-item.text .label { background: #28a745; }
        .template-item.audio .label { background: #f5576c; }
        .template-item.silence { border-left: 4px solid #ffa726; }
        .template-item.silence .label { background: #ffa726; }
        .template-item .remove {
            background: #dc3545;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
        }
        .field-selector {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-bottom: 15px;
        }
        #audioFilesList::-webkit-scrollbar {
            width: 8px;
        }
        #audioFilesList::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }
        #audioFilesList::-webkit-scrollbar-thumb {
            background: #667eea;
            border-radius: 4px;
        }
        #audioFilesList::-webkit-scrollbar-thumb:hover {
            background: #5568d3;
        }
        .field-btn {
            padding: 10px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
        }
        .field-btn:hover {
            background: #764ba2;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📝 Şablon Yönetimi</h1>
            <p class="subtitle">Mesaj şablonları oluşturun ve yönetin</p>
            
            <div class="nav-links">
                <a href="otomatikarama.php">🏠 Ana Sayfa</a>
                <a href="projeler.php">📁 Projeler</a>
                <a href="arama_yonetimi.php">📋 Arama Yönetimi</a>
            </div>
        </div>

        <?php if (isset($message)): ?>
        <div class="alert alert-<?= $messageType ?>">
            <?= $message ?>
        </div>
        <?php endif; ?>

        <div class="main-grid">
            <!-- Sol Kolon: Şablon Listesi + Ses Yükleme -->
            <div class="sidebar-column">
                <!-- Şablon Listesi -->
                <div class="card">
                    <h3 style="margin-bottom: 20px;">📚 Kayıtlı Şablonlar</h3>
                    
                    <?php if (hasPermission('template.create')): ?>
                    <a href="?new=1" class="btn btn-primary" style="width: 100%; margin-bottom: 20px; display: block; text-align: center; text-decoration: none;">
                        ➕ Yeni Şablon
                    </a>
                    <?php endif; ?>
                    
                    <?php foreach ($templates as $tmpl): ?>
                    <div class="template-list-item <?= $editTemplate && $editTemplate['id'] == $tmpl['id'] ? 'active' : '' ?>"
                         onclick="window.location.href='?edit=<?= $tmpl['id'] ?>'">
                        <strong><?= htmlspecialchars($tmpl['TemplateName']) ?></strong>
                        <p style="font-size: 13px; color: #666; margin-top: 5px;">
                            <?= htmlspecialchars($tmpl['Description']) ?>
                        </p>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Ses Dosyası Yönetimi -->
                <div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                    <h3 style="margin-bottom: 15px; color: white;">🔊 Ses Dosyası Yönetimi</h3>
                    
                    <div style="background: rgba(255,255,255,0.15); padding: 12px; border-radius: 8px; margin-bottom: 12px;">
                        <p style="font-size: 13px; margin-bottom: 5px;">📍 <strong>Hedef:</strong></p>
                        <code style="background: rgba(0,0,0,0.3); padding: 6px; border-radius: 4px; display: block; font-size: 11px; word-break: break-all;">
                            /var/lib/asterisk/sounds/otomatik/
                        </code>
                    </div>
                    
                    <!-- Yükleme Bölümü -->
                    <div style="background: white; padding: 15px; border-radius: 8px; color: #333; margin-bottom: 12px;">
                        <strong style="display: block; margin-bottom: 8px; color: #667eea;">⬆️ Yeni Dosya Yükle</strong>
                        <input type="file" id="audioFileInput" accept=".wav,.mp3" style="display: block; width: 100%; padding: 8px; border: 2px dashed #667eea; border-radius: 6px; margin-bottom: 12px; font-size: 13px;">
                        <button type="button" onclick="uploadAudioFile()" class="btn btn-success" style="width: 100%; padding: 10px; font-size: 14px;">
                            ⬆️ Yükle ve Gönder
                        </button>
                        <div id="uploadStatus" style="margin-top: 8px; font-size: 12px;"></div>
                    </div>
                    
                    <!-- Mevcut Dosyalar Listesi -->
                    <div style="background: white; padding: 15px; border-radius: 8px; color: #333;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                            <strong style="color: #667eea;">📁 Mevcut Ses Dosyaları</strong>
                            <button type="button" onclick="loadAudioFiles()" style="background: none; border: none; cursor: pointer; font-size: 18px; color: #667eea;" title="Yenile">
                                🔄
                            </button>
                        </div>
                        <div id="audioFilesList" style="max-height: 300px; overflow-y: auto; font-size: 13px;">
                            <p style="color: #999; text-align: center; padding: 20px;">⏳ Yükleniyor...</p>
                        </div>
                    </div>
                    
                    <div style="margin-top: 12px; font-size: 12px; opacity: 0.9;">
                        💡 <strong>Format:</strong> WAV, MP3<br>
                        🔄 <strong>Dönüşüm:</strong> 8kHz Mono
                    </div>
                </div>
            </div>

            <!-- Sağ Kolon: Şablon Editörü -->
            <div class="card">
                <h3 style="margin-bottom: 20px;">
                    <?= $editTemplate ? '✏️ Şablon Düzenle' : '➕ Yeni Şablon Oluştur' ?>
                </h3>
                
                <form method="post" id="templateForm" novalidate>
                    <input type="hidden" name="template_id" value="<?= $editTemplate['id'] ?? '' ?>">
                    
                    <div class="form-group">
                        <label>Şablon Adı *</label>
                        <input type="text" name="template_name" required 
                               value="<?= htmlspecialchars($editTemplate['TemplateName'] ?? '') ?>"
                               placeholder="Örn: Tahsilat Mesajı 2026">
                    </div>
                    
                    <div class="form-group">
                        <label>Açıklama</label>
                        <textarea name="template_description" rows="2" 
                                  placeholder="Şablon hakkında kısa açıklama..."><?= htmlspecialchars($editTemplate['Description'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Şablon İçeriği</label>
                        
                        <!-- Alan Ekleyici -->
                        <div class="field-selector">
                            <button type="button" class="field-btn" onclick="addField('Adi')">👤 Müşteri Adı</button>
                            <button type="button" class="field-btn" onclick="addField('Metin')">📝 Ana Metin</button>
                            <button type="button" class="field-btn" onclick="addField('Tutar')">💰 Tutar</button>
                            <button type="button" class="field-btn" onclick="addField('Metin1')">📄 Ek Metin 1</button>
                            <button type="button" class="field-btn" onclick="addField('Metin2')">📄 Ek Metin 2</button>
                            <button type="button" class="field-btn" onclick="addField('Metin3')">📄 Ek Metin 3</button>
                            <button type="button" class="btn-primary" onclick="toggleTextInput()">
                                ✏️ Sabit Metin Ekle
                            </button>
                            <button type="button" class="field-btn" style="background: #f5576c;" onclick="toggleAudioInput()">
                                🔊 Ses Kaydı Ekle
                            </button>
                            <button type="button" class="field-btn" style="background: #ffa726;" onclick="toggleSilenceInput()">
                                ⏱️ Sessizlik Ekle
                            </button>
                        </div>
                        
                        <!-- Inline Input Panelleri -->
                        <div id="textInputPanel" style="display: none; background: #e8f5e9; padding: 15px; border-radius: 8px; margin-bottom: 10px; border: 2px solid #4caf50;">
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <input type="text" id="textInputValue" placeholder="Sabit metin girin..." style="flex: 1; padding: 10px; border: 2px solid #4caf50; border-radius: 5px;">
                                <button type="button" onclick="confirmAddText()" style="padding: 10px 20px; background: #4caf50; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;">✅ Ekle</button>
                                <button type="button" onclick="cancelInput()" style="padding: 10px 20px; background: #f44336; color: white; border: none; border-radius: 5px; cursor: pointer;">❌ İptal</button>
                            </div>
                        </div>
                        
                        <div id="audioInputPanel" style="display: none; background: #ffebee; padding: 15px; border-radius: 8px; margin-bottom: 10px; border: 2px solid #f5576c;">
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <input type="text" id="audioInputValue" placeholder="Ses dosyası adı (örn: karsilama.wav)" style="flex: 1; padding: 10px; border: 2px solid #f5576c; border-radius: 5px;">
                                <button type="button" onclick="confirmAddAudio()" style="padding: 10px 20px; background: #f5576c; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;">✅ Ekle</button>
                                <button type="button" onclick="cancelInput()" style="padding: 10px 20px; background: #f44336; color: white; border: none; border-radius: 5px; cursor: pointer;">❌ İptal</button>
                            </div>
                        </div>
                        
                        <div id="silenceInputPanel" style="display: none; background: #fff3e0; padding: 15px; border-radius: 8px; margin-bottom: 10px; border: 2px solid #ffa726;">
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <input type="number" id="silenceInputValue" placeholder="Süre (saniye)" value="2" min="0.1" step="0.5" style="width: 150px; padding: 10px; border: 2px solid #ffa726; border-radius: 5px;">
                                <span style="color: #666; font-weight: bold;">saniye</span>
                                <button type="button" onclick="confirmAddSilence()" style="padding: 10px 20px; background: #ffa726; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;">✅ Ekle</button>
                                <button type="button" onclick="cancelInput()" style="padding: 10px 20px; background: #f44336; color: white; border: none; border-radius: 5px; cursor: pointer;">❌ İptal</button>
                            </div>
                        </div>
                        
                        <!-- Şablon Builder -->
                        <div id="templateBuilder" class="template-builder"></div>
                    </div>
                    
                    <input type="hidden" name="template_data" id="templateData">
                    
                    <!-- Tuş Davranışları -->
                    <div style="border: 2px solid #667eea; border-radius: 10px; padding: 20px; margin-top: 30px; background: #f8f9ff;">
                        <h3 style="color: #667eea; margin-bottom: 20px;">🎹 Tuş Davranışları</h3>
                        
                        <!-- 1 Tuşu -->
                        <div class="form-group">
                            <label>1 Tuşu Fonksiyonu</label>
                            <select name="key1_action" id="key1Action" onchange="toggleKey1Transfer()" style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px;">
                                <option value="replay" <?= ($editTemplate['Key1Action'] ?? 'replay') === 'replay' ? 'selected' : '' ?>>📝 Anonsu Tekrar Dinlet</option>
                                <option value="transfer" <?= ($editTemplate['Key1Action'] ?? '') === 'transfer' ? 'selected' : '' ?>>📞 Transfer Yap</option>
                                <option value="hangup" <?= ($editTemplate['Key1Action'] ?? '') === 'hangup' ? 'selected' : '' ?>>❌ Hattı Kapat</option>
                            </select>
                        </div>
                        
                        <div id="key1TransferDiv" class="form-group" style="display: <?= ($editTemplate['Key1Action'] ?? 'replay') === 'transfer' ? 'block' : 'none' ?>;">
                            <label>1 Tuşu Transfer Numarası</label>
                            <input type="text" name="key1_transfer_number" value="<?= htmlspecialchars($editTemplate['Key1TransferNumber'] ?? '133') ?>" placeholder="Örn: 133">
                        </div>
                        
                        <div id="key1AudioDiv" class="form-group" style="display: <?= ($editTemplate['Key1Action'] ?? 'replay') === 'transfer' ? 'block' : 'none' ?>;">
                            <label>🔊 1 Tuşu Ses Dosyası (Transfer öncesi)</label>
                            <select name="key1_audio_file" style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px;">
                                <option value="otomatik/transfer" <?= ($editTemplate['Key1AudioFile'] ?? 'otomatik/transfer') === 'otomatik/transfer' ? 'selected' : '' ?>>🔊 Transfer Mesajı</option>
                                <option value="otomatik/agent-connecting" <?= ($editTemplate['Key1AudioFile'] ?? '') === 'otomatik/agent-connecting' ? 'selected' : '' ?>>🔊 Bağlanıyor</option>
                                <option value="" <?= ($editTemplate['Key1AudioFile'] ?? '') === '' ? 'selected' : '' ?>>🔇 Ses Yok</option>
                            </select>
                        </div>
                        
                        <!-- 2 Tuşu -->
                        <div class="form-group">
                            <label>2 Tuşu Fonksiyonu</label>
                            <select name="key2_action" id="key2Action" onchange="toggleKey2Transfer()" style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px;">
                                <option value="replay" <?= ($editTemplate['Key2Action'] ?? 'transfer') === 'replay' ? 'selected' : '' ?>>📝 Anonsu Tekrar Dinlet</option>
                                <option value="transfer" <?= ($editTemplate['Key2Action'] ?? 'transfer') === 'transfer' ? 'selected' : '' ?>>📞 Transfer Yap</option>
                                <option value="hangup" <?= ($editTemplate['Key2Action'] ?? '') === 'hangup' ? 'selected' : '' ?>>❌ Hattı Kapat</option>
                            </select>
                        </div>
                        
                        <div id="key2TransferDiv" class="form-group" style="display: <?= ($editTemplate['Key2Action'] ?? 'transfer') === 'transfer' ? 'block' : 'none' ?>;">
                            <label>2 Tuşu Transfer Numarası</label>
                            <input type="text" name="key2_transfer_number" value="<?= htmlspecialchars($editTemplate['Key2TransferNumber'] ?? 'TAHSILAT') ?>" placeholder="Örn: TAHSILAT veya 200">
                            <small style="color: #666; font-size: 13px;">Kuyruk adı (TAHSILAT) veya dahili numara (200)</small>
                        </div>
                        
                        <div id="key2AudioDiv" class="form-group" style="display: <?= ($editTemplate['Key2Action'] ?? 'transfer') === 'transfer' ? 'block' : 'none' ?>;">
                            <label>🔊 2 Tuşu Ses Dosyası (Transfer öncesi)</label>
                            <select name="key2_audio_file" style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px;">
                                <option value="otomatik/transfer" <?= ($editTemplate['Key2AudioFile'] ?? 'otomatik/transfer') === 'otomatik/transfer' ? 'selected' : '' ?>>🔊 Transfer Mesajı</option>
                                <option value="otomatik/agent-connecting" <?= ($editTemplate['Key2AudioFile'] ?? '') === 'otomatik/agent-connecting' ? 'selected' : '' ?>>🔊 Bağlanıyor</option>
                                <option value="" <?= ($editTemplate['Key2AudioFile'] ?? '') === '' ? 'selected' : '' ?>>🔇 Ses Yok</option>
                            </select>
                        </div>
                        
                        <!-- Yanlış Tuşlama -->
                        <div class="form-group">
                            <label>Yanlış Tuşlama Durumu</label>
                            <select name="invalid_key_action" style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px;">
                                <option value="use_key1" <?= ($editTemplate['InvalidKeyAction'] ?? 'use_key1') === 'use_key1' ? 'selected' : '' ?>>1 Tuşu Fonksiyonunu Çalıştır</option>
                                <option value="hangup" <?= ($editTemplate['InvalidKeyAction'] ?? '') === 'hangup' ? 'selected' : '' ?>>Hattı Kapat</option>
                            </select>
                        </div>
                        
                        <!-- Timeout -->
                        <div class="form-group">
                            <label>Timeout Durumu</label>
                            <select name="timeout_action" style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px;">
                                <option value="use_key1_and_hangup" <?= ($editTemplate['TimeoutAction'] ?? 'use_key1_and_hangup') === 'use_key1_and_hangup' ? 'selected' : '' ?>>1 Tuşu Fonksiyonu + Hattı Kapat</option>
                                <option value="hangup" <?= ($editTemplate['TimeoutAction'] ?? '') === 'hangup' ? 'selected' : '' ?>>Sadece Hattı Kapat</option>
                            </select>
                        </div>
                        
                        <!-- Timeout Süresi -->
                        <div class="form-group">
                            <label>Tuş Bekleme Süresi (Saniye)</label>
                            <input type="number" name="timeout_seconds" min="3" max="30" value="<?= $editTemplate['TimeoutSeconds'] ?? 5 ?>" style="width: 150px;">
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 10px;">
                        <button type="submit" name="save_template" class="btn btn-primary">
                            💾 Şablonu Kaydet
                        </button>
                        
                        <?php if ($editTemplate && $editTemplate['id'] != 1 && hasPermission('template.delete')): ?>
                        <form method="post" style="display: inline;" onsubmit="return confirm('Şablonu silmek istediğinizden emin misiniz?');">
                            <input type="hidden" name="template_id" value="<?= $editTemplate['id'] ?>">
                            <button type="submit" name="delete_template" class="btn btn-danger">
                                🗑️ Şablonu Sil
                            </button>
                        </form>
                        <?php endif; ?>
                        
                        <?php if ($editTemplate && $editTemplate['id'] == 1): ?>
                        <div style="display: inline-block; padding: 12px 24px; background: #f0f0f0; color: #999; border-radius: 8px; font-size: 14px;">
                            <i class="fas fa-lock"></i> Varsayılan şablon silinemez
                        </div>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        let template = <?= json_encode($editTemplateContent) ?>;

        function renderTemplate() {
            console.log('🎨 renderTemplate() çağrıldı');
            console.log('📋 Template içeriği:', template);
            console.log('📊 Template uzunluğu:', template.length);
            
            const builder = document.getElementById('templateBuilder');
            
            if (template.length === 0) {
                builder.innerHTML = '<div style="text-align: center; padding: 40px; color: #999;">Şablon boş. Yukarıdaki butonlardan alan ekleyin.</div>';
                return;
            }

            builder.innerHTML = template.map((item, index) => {
                let label, content;
                
                console.log(`🔄 İşleniyor [${index}]:`, item.type, item);
                
                if (item.type === 'field') {
                    label = getFieldLabel(item.value);
                    content = `<strong>{${item.value}}</strong>`;
                } else if (item.type === 'audio') {
                    label = '🔊 Ses Kaydı';
                    content = `<input type="text" value="${item.file}" onchange="updateAudioItem(${index}, this.value)" placeholder="dosya_adi">`;
                } else if (item.type === 'silence') {
                    label = '⏱️ Sessizlik';
                    content = `<input type="number" value="${item.duration}" onchange="updateSilenceItem(${index}, this.value)" placeholder="saniye" step="0.5" min="0.1" style="width: 80px;"> <span style="color: #666;">saniye</span>`;
                    console.log('⏱️ Sessizlik elementi oluşturuldu:', item.duration, 'saniye');
                } else {
                    label = '✏️ Sabit Metin';
                    content = `<input type="text" value="${item.value}" onchange="updateItem(${index}, this.value)">`;
                }
                
                return `
                    <div class="template-item ${item.type}" 
                         draggable="true" 
                         data-index="${index}"
                         ondragstart="handleDragStart(event)"
                         ondragover="handleDragOver(event)"
                         ondrop="handleDrop(event)"
                         ondragend="handleDragEnd(event)">
                        <span class="label">⋮⋮ ${label}</span>
                        <div class="content">${content}</div>
                        <button type="button" class="remove" onclick="removeItem(${index})">🗑️</button>
                    </div>
                `;
            }).join('');
            
            console.log('✅ Render tamamlandı! DOM güncellendi.');
        }

        function getFieldLabel(field) {
            const labels = {
                'Adi': '👤 Müşteri Adı',
                'Metin': '📝 Ana Metin',
                'Tutar': '💰 Tutar',
                'Metin1': '📄 Ek Metin 1',
                'Metin2': '📄 Ek Metin 2',
                'Metin3': '📄 Ek Metin 3'
            };
            return labels[field] || field;
        }

        function addField(fieldName) {
            console.log('📋 Alan ekleniyor:', fieldName);
            template.push({ type: 'field', value: fieldName });
            renderTemplate();
        }

        // YENİ: Inline Input Panellerini Aç/Kapat
        function toggleTextInput() {
            console.log('✏️ Sabit Metin paneli açılıyor');
            cancelInput(); // Diğerlerini kapat
            document.getElementById('textInputPanel').style.display = 'block';
            
            // Focus'u erteliyoruz - DOM güncellemesini bekle
            setTimeout(() => {
                document.getElementById('textInputValue').focus();
                console.log('🎯 Focus: textInputValue');
            }, 50);
        }
        
        function toggleAudioInput() {
            console.log('🔊 Ses Kaydı paneli açılıyor');
            cancelInput(); // Diğerlerini kapat
            document.getElementById('audioInputPanel').style.display = 'block';
            
            // Focus'u erteliyoruz - DOM güncellemesini bekle
            setTimeout(() => {
                document.getElementById('audioInputValue').focus();
                console.log('🎯 Focus: audioInputValue');
            }, 50);
        }
        
        function toggleSilenceInput() {
            console.log('⏱️ Sessizlik paneli açılıyor');
            cancelInput(); // Diğerlerini kapat
            document.getElementById('silenceInputPanel').style.display = 'block';
            
            // Focus'u erteliyoruz - DOM güncellemesini bekle
            setTimeout(() => {
                const input = document.getElementById('silenceInputValue');
                input.focus();
                input.select(); // Değeri seç
                console.log('🎯 Focus + Select: silenceInputValue');
            }, 50);
        }
        
        function cancelInput() {
            console.log('❌ Tüm input panelleri kapatılıyor');
            document.getElementById('textInputPanel').style.display = 'none';
            document.getElementById('audioInputPanel').style.display = 'none';
            document.getElementById('silenceInputPanel').style.display = 'none';
        }
        
        // YENİ: Onay Fonksiyonları
        function confirmAddText() {
            const text = document.getElementById('textInputValue').value.trim();
            console.log('✅ Sabit metin onaylandı:', text);
            
            if (text) {
                template.push({ type: 'text', value: text });
                renderTemplate();
                console.log('✅ Sabit metin şablona eklendi');
                
                // Input'u temizle ve paneli kapat
                document.getElementById('textInputValue').value = '';
                cancelInput();
            } else {
                alert('⚠️ Lütfen metin girin!');
                console.log('⚠️ Metin boş, eklenmedi');
            }
        }
        
        function confirmAddAudio() {
            let fileName = document.getElementById('audioInputValue').value.trim();
            console.log('✅ Ses dosyası onaylandı:', fileName);
            
            if (fileName) {
                // .wav uzantısını kaldır
                fileName = fileName.replace('.wav', '');
                
                template.push({ 
                    type: 'audio', 
                    file: fileName,
                    description: fileName + '.wav'
                });
                renderTemplate();
                console.log('✅ Ses dosyası şablona eklendi:', fileName);
                
                // Input'u temizle ve paneli kapat
                document.getElementById('audioInputValue').value = '';
                cancelInput();
            } else {
                alert('⚠️ Lütfen dosya adı girin!');
                console.log('⚠️ Dosya adı boş, eklenmedi');
            }
        }
        
        function confirmAddSilence() {
            const duration = parseFloat(document.getElementById('silenceInputValue').value);
            console.log('✅ Sessizlik onaylandı:', duration);
            
            if (!isNaN(duration) && duration > 0) {
                template.push({ 
                    type: 'silence', 
                    duration: duration
                });
                renderTemplate();
                console.log('✅ Sessizlik şablona eklendi:', duration, 'saniye');
                
                cancelInput();
            } else {
                alert('⚠️ Lütfen geçerli bir süre girin!');
                console.log('❌ Geçersiz süre:', duration);
            }
        }
        
        // ESKİ FONKSİYONLAR - Artık kullanılmıyor ama yedek olarak bırakıyoruz
        function addText() {
            console.log('✏️ (ESKİ) Sabit Metin Ekle butonu tıklandı');
            toggleTextInput(); // Yeni paneli aç
        }

        function addAudio() {
            console.log('🔊 (ESKİ) Ses Kaydı Ekle butonu tıklandı');
            toggleAudioInput(); // Yeni paneli aç
        }

        function addSilence() {
            console.log('⏱️ (ESKİ) Sessizlik Ekle butonu tıklandı');
            toggleSilenceInput(); // Yeni paneli aç
        }

        function removeItem(index) {
            template.splice(index, 1);
            renderTemplate();
        }

        function updateItem(index, value) {
            template[index].value = value;
        }

        function updateAudioItem(index, value) {
            template[index].file = value;
        }

        function updateSilenceItem(index, value) {
            const duration = parseFloat(value);
            if (!isNaN(duration) && duration > 0) {
                template[index].duration = duration;
            }
        }

        function moveUp(index) {
            if (index > 0) {
                [template[index], template[index - 1]] = [template[index - 1], template[index]];
                renderTemplate();
            }
        }

        function moveDown(index) {
            if (index < template.length - 1) {
                [template[index], template[index + 1]] = [template[index + 1], template[index]];
                renderTemplate();
            }
        }

        // Drag & Drop İşlemleri
        let draggedIndex = null;

        function handleDragStart(e) {
            draggedIndex = parseInt(e.target.getAttribute('data-index'));
            e.target.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
        }

        function handleDragOver(e) {
            if (e.preventDefault) {
                e.preventDefault();
            }
            e.dataTransfer.dropEffect = 'move';
            
            const target = e.currentTarget;
            if (target.classList.contains('template-item')) {
                target.classList.add('drag-over');
            }
            
            return false;
        }

        function handleDrop(e) {
            if (e.stopPropagation) {
                e.stopPropagation();
            }
            
            const dropIndex = parseInt(e.currentTarget.getAttribute('data-index'));
            
            if (draggedIndex !== null && draggedIndex !== dropIndex) {
                // Öğeleri yer değiştir
                const draggedItem = template[draggedIndex];
                template.splice(draggedIndex, 1);
                template.splice(dropIndex, 0, draggedItem);
                renderTemplate();
            }
            
            return false;
        }

        function handleDragEnd(e) {
            e.target.classList.remove('dragging');
            
            // Tüm drag-over class'larını temizle
            document.querySelectorAll('.drag-over').forEach(el => {
                el.classList.remove('drag-over');
            });
            
            draggedIndex = null;
        }

        // Ses dosyası yükleme
        function uploadAudioFile() {
            const fileInput = document.getElementById('audioFileInput');
            const statusDiv = document.getElementById('uploadStatus');
            
            if (!fileInput.files || fileInput.files.length === 0) {
                statusDiv.innerHTML = '<span style="color: #f44336;">❌ Lütfen dosya seçin!</span>';
                return;
            }
            
            const file = fileInput.files[0];
            const formData = new FormData();
            formData.append('audio_file', file);
            
            statusDiv.innerHTML = '<span style="color: #2196F3;">⏳ Yükleniyor...</span>';
            
            fetch('upload_audio.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    statusDiv.innerHTML = '<span style="color: #4CAF50;">✅ Başarılı: ' + data.fileName + '</span>';
                    fileInput.value = '';
                    
                    // Ses dosyaları listesini yenile
                    loadAudioFiles();
                    
                    // Otomatik olarak şablona ekle
                    if (confirm('Ses dosyası yüklendi!\n\nŞablona otomatik eklensin mi?')) {
                        template.push({
                            type: 'audio',
                            file: data.fileName.replace('.wav', ''),
                            description: data.fileName
                        });
                        renderTemplate();
                    }
                } else {
                    let errorMsg = '<span style="color: #f44336;">❌ ' + data.message + '</span>';
                    if (data.debug) {
                        console.error('Debug Info:', data.debug);
                        errorMsg += '<br><small style="color: #999;">Detay için console\'a bakın</small>';
                    }
                    statusDiv.innerHTML = errorMsg;
                }
            })
            .catch(error => {
                statusDiv.innerHTML = '<span style="color: #f44336;">❌ Yükleme hatası: ' + error.message + '</span>';
                console.error('Upload error:', error);
            });
        }

        // Mevcut ses dosyalarını yükle
        function loadAudioFiles() {
            const listDiv = document.getElementById('audioFilesList');
            listDiv.innerHTML = '<p style="color: #999; text-align: center; padding: 20px;">⏳ Yükleniyor...</p>';
            
            fetch('list_audio_files.php', {
                credentials: 'same-origin'
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.files && data.files.length > 0) {
                        let html = '<div style="display: flex; flex-direction: column; gap: 8px;">';
                        
                        data.files.forEach(file => {
                            const fileName = file.replace('.wav', '');
                            html += `
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px; background: #f5f5f5; border-radius: 6px; border: 1px solid #e0e0e0;">
                                    <div style="flex: 1; overflow: hidden;">
                                        <div style="font-weight: 600; color: #333; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="${file}">
                                            🔊 ${file}
                                        </div>
                                    </div>
                                    <button type="button" onclick="addAudioToTemplate('${fileName}', '${file}')" 
                                            style="padding: 6px 12px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 12px; white-space: nowrap; margin-left: 10px;">
                                        ➕ Ekle
                                    </button>
                                </div>
                            `;
                        });
                        
                        html += '</div>';
                        listDiv.innerHTML = html;
                    } else {
                        listDiv.innerHTML = '<p style="color: #999; text-align: center; padding: 20px;">📭 Henüz ses dosyası yok</p>';
                    }
                })
                .catch(error => {
                    listDiv.innerHTML = '<p style="color: #f44336; text-align: center; padding: 20px;">❌ Yükleme hatası</p>';
                    console.error('Load audio files error:', error);
                });
        }
        
        // Ses dosyasını şablona ekle
        function addAudioToTemplate(fileName, displayName) {
            template.push({
                type: 'audio',
                file: fileName,
                description: displayName
            });
            renderTemplate();
            
            // Başarı mesajı göster
            const statusDiv = document.getElementById('uploadStatus');
            statusDiv.innerHTML = '<span style="color: #4CAF50;">✅ Şablona eklendi: ' + displayName + '</span>';
            setTimeout(() => {
                statusDiv.innerHTML = '';
            }, 3000);
        }
        
        // Sayfa yüklendiğinde ses dosyalarını listele
        document.addEventListener('DOMContentLoaded', function() {
            loadAudioFiles();
        });

        document.getElementById('templateForm').addEventListener('submit', function(e) {
            console.log('💾 Form submit ediliyor...');
            
            // Şablon adı kontrolü
            const templateName = document.querySelector('input[name="template_name"]').value.trim();
            if (!templateName) {
                e.preventDefault();
                alert('❌ Lütfen şablon adı girin!');
                document.querySelector('input[name="template_name"]').focus();
                return false;
            }
            
            // Template içerik kontrolü
            if (template.length === 0) {
                e.preventDefault();
                alert('❌ Şablon içeriği boş! Lütfen en az bir alan ekleyin.');
                return false;
            }
            
            // Önce tüm input panellerini kapat (validation hatasını önler)
            cancelInput();
            
            // Template data'yı JSON olarak kaydet
            document.getElementById('templateData').value = JSON.stringify(template);
            console.log('📋 Template data hazırlandı:', template);
        });

        // 1 Tuşu transfer seçeneği
        function toggleKey1Transfer() {
            const action = document.getElementById('key1Action').value;
            const transferDiv = document.getElementById('key1TransferDiv');
            const audioDiv = document.getElementById('key1AudioDiv');
            
            if (action === 'transfer') {
                transferDiv.style.display = 'block';
                audioDiv.style.display = 'block';
            } else {
                transferDiv.style.display = 'none';
                audioDiv.style.display = 'none';
            }
        }
        
        // 2 Tuşu transfer seçeneği
        function toggleKey2Transfer() {
            const action = document.getElementById('key2Action').value;
            const transferDiv = document.getElementById('key2TransferDiv');
            const audioDiv = document.getElementById('key2AudioDiv');
            
            if (action === 'transfer') {
                transferDiv.style.display = 'block';
                audioDiv.style.display = 'block';
            } else {
                transferDiv.style.display = 'none';
                audioDiv.style.display = 'none';
            }
        }

        // Sayfa yüklenirken
        console.log('═══════════════════════════════════════════════');
        console.log('🚀 ŞABLON YÖNETİMİ BAŞLATILDI');
        console.log('═══════════════════════════════════════════════');
        console.log('📋 Başlangıç Template:', template);
        console.log('🔢 Template öğe sayısı:', template.length);
        
        renderTemplate();
        
        console.log('✅ İlk render tamamlandı');
        console.log('═══════════════════════════════════════════════');
        
        // Enter tuşu ile ekleme
        document.getElementById('textInputValue').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                console.log('⌨️ Enter tuşu - Sabit metin');
                confirmAddText();
            }
        });
        
        document.getElementById('audioInputValue').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                console.log('⌨️ Enter tuşu - Ses kaydı');
                confirmAddAudio();
            }
        });
        
        document.getElementById('silenceInputValue').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                console.log('⌨️ Enter tuşu - Sessizlik');
                confirmAddSilence();
            }
        });
        
        // ESC tuşu ile iptal
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                console.log('⌨️ ESC tuşu - İptal');
                cancelInput();
            }
        });
    </script>
</body>
</html>
