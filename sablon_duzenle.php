<?php
/**
 * TTS Mesaj Şablonu Düzenleme
 */

require_once 'config.php';

// Mevcut şablonu config'den oku (varsa)
$templateFile = __DIR__ . '/includes/tts_template.json';

$defaultTemplate = [
    [
        'type' => 'text',
        'value' => 'Merhaba'
    ],
    [
        'type' => 'field',
        'value' => 'Adi'
    ],
    [
        'type' => 'text',
        'value' => '.'
    ],
    [
        'type' => 'field',
        'value' => 'Metin'
    ],
    [
        'type' => 'text',
        'value' => 'Bugünkü borcunuz'
    ],
    [
        'type' => 'field',
        'value' => 'Tutar'
    ],
    [
        'type' => 'text',
        'value' => 'Türk Lirasıdır.'
    ],
    [
        'type' => 'field',
        'value' => 'Metin1'
    ],
    [
        'type' => 'field',
        'value' => 'Metin2'
    ],
    [
        'type' => 'field',
        'value' => 'Metin3'
    ],
    [
        'type' => 'text',
        'value' => 'Tekrar dinlemek için 1, operatöre bağlanmak için 2 tuşuna basınız.'
    ]
];

if (file_exists($templateFile)) {
    $template = json_decode(file_get_contents($templateFile), true);
} else {
    $template = $defaultTemplate;
}

// Şablon kaydet
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_template'])) {
    $newTemplate = json_decode($_POST['template_data'], true);
    file_put_contents($templateFile, json_encode($newTemplate, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    $template = $newTemplate;
    $message = 'Şablon başarıyla kaydedildi!';
}

// Test önizlemesi
$previewText = '';
if (isset($_POST['preview'])) {
    $previewData = [
        'Adi' => $_POST['preview_adi'] ?? 'Ahmet Yılmaz',
        'Metin' => $_POST['preview_metin'] ?? 'Sizi Anabilim eğitim kurumlarından arıyoruz',
        'Tutar' => $_POST['preview_tutar'] ?? '1.250,00',
        'Metin1' => $_POST['preview_metin1'] ?? 'Erken kayıt dönemi başlamıştır',
        'Metin2' => $_POST['preview_metin2'] ?? 'İyi günler dileriz',
        'Metin3' => $_POST['preview_metin3'] ?? ''
    ];
    
    foreach ($template as $item) {
        if ($item['type'] === 'text') {
            $previewText .= $item['value'] . ' ';
        } elseif ($item['type'] === 'field') {
            $previewText .= ($previewData[$item['value']] ?? '') . ' ';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mesaj Şablonu Düzenle</title>
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

        .subtitle {
            color: #666;
            font-size: 16px;
        }

        .main-grid {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 30px;
        }

        .sidebar {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            height: fit-content;
        }

        .sidebar h2 {
            color: #667eea;
            font-size: 20px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }

        .field-item {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 15px;
            margin-bottom: 10px;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
            transition: all 0.2s;
            user-select: none;
        }

        .field-item:hover {
            transform: translateX(5px) scale(1.02);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .field-item:active {
            transform: translateX(5px) scale(0.98);
        }

        .field-icon {
            font-size: 20px;
        }

        .text-template {
            background: #f8f9fa;
            padding: 12px 15px;
            margin-bottom: 10px;
            border-radius: 8px;
            border-left: 4px solid #28a745;
            cursor: pointer;
        }

        .editor-area {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .editor-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #667eea;
        }

        .template-builder {
            min-height: 400px;
            border: 2px dashed #ddd;
            border-radius: 10px;
            padding: 20px;
            background: #f8f9fa;
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
            cursor: move;
        }

        .template-item.field {
            border-left: 4px solid #667eea;
        }

        .template-item.text {
            border-left: 4px solid #28a745;
        }

        .template-item .drag-handle {
            font-size: 20px;
            color: #999;
            cursor: move;
        }

        .template-item .content {
            flex: 1;
        }

        .template-item .content input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .template-item .label {
            background: #667eea;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        .template-item.text .label {
            background: #28a745;
        }

        .template-item .remove {
            background: #dc3545;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }

        .template-item .remove:hover {
            background: #c82333;
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
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
        }

        .preview-section {
            background: #fff3cd;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
            border-left: 4px solid #ffc107;
        }

        .preview-text {
            background: white;
            padding: 15px;
            border-radius: 5px;
            margin-top: 10px;
            font-size: 15px;
            line-height: 1.6;
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

        .preview-form {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 15px;
        }

        .preview-form input {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📢 Mesaj Şablonu Düzenle</h1>
            <p class="subtitle">Otomatik aramalarda okunacak mesajı istediğiniz gibi düzenleyin</p>
        </div>

        <?php if (isset($message)): ?>
        <div class="alert alert-success">
            ✅ <?= $message ?>
        </div>
        <?php endif; ?>

        <div class="main-grid">
            <!-- Sidebar: Alan Seçici -->
            <div class="sidebar">
                <h2>📋 Alan Ekle</h2>
                <p style="color: #666; font-size: 14px; margin-bottom: 20px;">
                    <strong>Tıkla</strong> veya <strong>sürükle</strong> ile şablona ekleyin
                </p>

                <div class="field-item" draggable="true" data-type="field" data-value="Adi" onclick="addFieldByClick('Adi')" title="Sürükle veya tıkla">
                    <span class="field-icon">👤</span>
                    <span>Müşteri Adı</span>
                </div>

                <div class="field-item" draggable="true" data-type="field" data-value="Metin" onclick="addFieldByClick('Metin')" title="Sürükle veya tıkla">
                    <span class="field-icon">📝</span>
                    <span>Ana Metin</span>
                </div>

                <div class="field-item" draggable="true" data-type="field" data-value="Tutar" onclick="addFieldByClick('Tutar')" title="Sürükle veya tıkla">
                    <span class="field-icon">💰</span>
                    <span>Tutar</span>
                </div>

                <div class="field-item" draggable="true" data-type="field" data-value="Metin1" onclick="addFieldByClick('Metin1')" title="Sürükle veya tıkla">
                    <span class="field-icon">📄</span>
                    <span>Ek Metin 1</span>
                </div>

                <div class="field-item" draggable="true" data-type="field" data-value="Metin2" onclick="addFieldByClick('Metin2')" title="Sürükle veya tıkla">
                    <span class="field-icon">📄</span>
                    <span>Ek Metin 2</span>
                </div>

                <div class="field-item" draggable="true" data-type="field" data-value="Metin3" onclick="addFieldByClick('Metin3')" title="Sürükle veya tıkla">
                    <span class="field-icon">📄</span>
                    <span>Ek Metin 3</span>
                </div>

                <div style="margin-top: 30px;">
                    <h2>✏️ Sabit Metin</h2>
                    <p style="color: #666; font-size: 14px; margin-bottom: 15px;">
                        Tıklayarak sabit metin ekleyin
                    </p>
                    <button class="btn btn-success" onclick="addTextItem()" style="width: 100%;">
                        + Sabit Metin Ekle
                    </button>
                </div>
                
                <div style="margin-top: 20px;">
                    <h2>🔊 Ses Kaydı</h2>
                    <p style="color: #666; font-size: 14px; margin-bottom: 15px;">
                        Önceden kaydedilmiş ses dosyası ekleyin
                    </p>
                    <button class="btn" onclick="addAudioFile()" style="width: 100%; background: #f5576c; color: white;">
                        + Ses Kaydı Ekle
                    </button>
                </div>
                
                <div style="margin-top: 20px;">
                    <h2>⏱️ Sessizlik</h2>
                    <p style="color: #666; font-size: 14px; margin-bottom: 15px;">
                        Belirtilen süre kadar sessizlik ekleyin
                    </p>
                    <button class="btn" onclick="addSilence()" style="width: 100%; background: #ffa726; color: white;">
                        + Sessizlik Ekle
                    </button>
                </div>

                <div style="margin-top: 30px;">
                    <a href="otomatikarama.php" class="btn btn-primary" style="width: 100%; display: block; text-align: center; text-decoration: none;">
                        ← Ana Sayfaya Dön
                    </a>
                </div>
            </div>

            <!-- Editor Area -->
            <div class="editor-area">
                <div class="editor-header">
                    <h2 style="color: #667eea; margin: 0;">🎯 Mesaj Şablonu</h2>
                    <button class="btn btn-primary" onclick="saveTemplate()">
                        💾 Şablonu Kaydet
                    </button>
                </div>

                <div id="templateBuilder" class="template-builder">
                    <?php if (empty($template)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">📭</div>
                        <p>Mesaj şablonu boş</p>
                        <p style="font-size: 14px; color: #999;">Soldan alan ekleyerek başlayın</p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Önizleme -->
                <div class="preview-section">
                    <h3 style="color: #856404; margin-bottom: 15px;">👁️ Önizleme</h3>
                    <form method="post" style="display: inline;">
                        <div class="preview-form">
                            <input type="text" name="preview_adi" placeholder="Müşteri Adı" value="<?= $_POST['preview_adi'] ?? 'Ahmet Yılmaz' ?>">
                            <input type="text" name="preview_metin" placeholder="Ana Metin" value="<?= $_POST['preview_metin'] ?? 'Sizi Anabilim eğitim kurumlarından arıyoruz' ?>">
                            <input type="text" name="preview_tutar" placeholder="Tutar" value="<?= $_POST['preview_tutar'] ?? '1.250,00' ?>">
                            <input type="text" name="preview_metin1" placeholder="Ek Metin 1" value="<?= $_POST['preview_metin1'] ?? 'Erken kayıt dönemi başlamıştır' ?>">
                            <input type="text" name="preview_metin2" placeholder="Ek Metin 2" value="<?= $_POST['preview_metin2'] ?? 'İyi günler dileriz' ?>">
                            <input type="text" name="preview_metin3" placeholder="Ek Metin 3" value="<?= $_POST['preview_metin3'] ?? '' ?>">
                        </div>
                        <button type="submit" name="preview" class="btn btn-success" style="margin-top: 15px;">
                            🔊 Önizlemeyi Güncelle
                        </button>
                    </form>

                    <?php if ($previewText): ?>
                    <div class="preview-text">
                        <?= htmlspecialchars($previewText) ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <form id="saveForm" method="post" style="display: none;">
        <input type="hidden" name="save_template" value="1">
        <input type="hidden" name="template_data" id="templateData">
    </form>

    <script>
        let template = <?= json_encode($template) ?>;
        let draggedElement = null;

        // Şablonu render et
        function renderTemplate() {
            const builder = document.getElementById('templateBuilder');
            
            if (template.length === 0) {
                builder.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-state-icon">📭</div>
                        <p>Mesaj şablonu boş</p>
                        <p style="font-size: 14px; color: #999;">Soldan alan ekleyerek başlayın</p>
                    </div>
                `;
                return;
            }

            builder.innerHTML = template.map((item, index) => {
                let isField = item.type === 'field';
                let label, icon, content;
                
                if (isField) {
                    label = getFieldLabel(item.value);
                    icon = getFieldIcon(item.value);
                    content = `<strong>{${item.value}}</strong>`;
                } else if (item.type === 'text') {
                    label = 'Sabit Metin';
                    icon = '✏️';
                    content = `<input type="text" value="${item.value || ''}" onchange="updateItem(${index}, this.value)" style="width: 100%; padding: 5px; border: 1px solid #ddd; border-radius: 4px;">`;
                } else if (item.type === 'audio') {
                    label = 'Ses Kaydı';
                    icon = '🔊';
                    content = `<span style="color: #666;">${item.value}</span>`;
                } else if (item.type === 'silence') {
                    label = 'Sessizlik';
                    icon = '⏱️';
                    content = `<span style="color: #666;">${item.value} saniye</span>`;
                } else {
                    label = 'Bilinmeyen';
                    icon = '❓';
                    content = `<span>${item.value}</span>`;
                }
                
                return `
                    <div class="template-item ${item.type}" draggable="true" data-index="${index}">
                        <span class="drag-handle">⋮⋮</span>
                        <span class="label">${icon} ${label}</span>
                        <div class="content">
                            ${content}
                        </div>
                        <button class="remove" onclick="removeItem(${index})">🗑️ Sil</button>
                    </div>
                `;
            }).join('');

            // Drag & Drop olaylarını ekle
            document.querySelectorAll('.template-item').forEach(item => {
                item.addEventListener('dragstart', handleDragStart);
                item.addEventListener('dragover', handleDragOver);
                item.addEventListener('drop', handleDrop);
                item.addEventListener('dragend', handleDragEnd);
            });
        }

        function getFieldLabel(field) {
            const labels = {
                'Adi': 'Müşteri Adı',
                'Metin': 'Ana Metin',
                'Tutar': 'Tutar',
                'Metin1': 'Ek Metin 1',
                'Metin2': 'Ek Metin 2',
                'Metin3': 'Ek Metin 3'
            };
            return labels[field] || field;
        }

        function getFieldIcon(field) {
            const icons = {
                'Adi': '👤',
                'Metin': '📝',
                'Tutar': '💰',
                'Metin1': '📄',
                'Metin2': '📄',
                'Metin3': '📄'
            };
            return icons[field] || '📄';
        }

        // Sidebar alan sürükleme - BAŞLANGIÇ İŞLEMLERİ
        function initDragAndDrop() {
            console.log('🔧 Drag & Drop başlatılıyor...');
            
            // Sidebar alanları
            const fieldItems = document.querySelectorAll('.sidebar .field-item');
            console.log('📋 Bulunan alan sayısı:', fieldItems.length);
            
            fieldItems.forEach(item => {
                item.addEventListener('dragstart', function(e) {
                    console.log('🎯 Sürükleme başladı:', this.dataset.value);
                    e.dataTransfer.effectAllowed = 'copy';
                    e.dataTransfer.setData('type', this.dataset.type);
                    e.dataTransfer.setData('value', this.dataset.value);
                    this.style.opacity = '0.5';
                });
                
                item.addEventListener('dragend', function(e) {
                    this.style.opacity = '1';
                });
            });

            // Drop zone
            const builder = document.getElementById('templateBuilder');
            if (!builder) {
                console.error('❌ templateBuilder bulunamadı!');
                return;
            }
            
            console.log('✅ Drop zone hazır');
            
            builder.addEventListener('dragover', function(e) {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'copy';
                this.style.background = '#e3f2fd'; // Görsel geri bildirim
            });
            
            builder.addEventListener('dragleave', function(e) {
                this.style.background = '#f8f9fa'; // Eski renk
            });

            builder.addEventListener('drop', function(e) {
                e.preventDefault();
                this.style.background = '#f8f9fa'; // Eski renk
                
                const type = e.dataTransfer.getData('type');
                const value = e.dataTransfer.getData('value');
                
                console.log('🎉 Bırakıldı - Type:', type, 'Value:', value);
                
                if (type && value) {
                    template.push({ type, value });
                    renderTemplate();
                    console.log('✅ Şablona eklendi!');
                } else {
                    console.error('❌ Type veya Value boş!');
                }
            });
        }
        
        // Başlangıçta çalıştır
        initDragAndDrop();

        // Sıralama
        function handleDragStart(e) {
            draggedElement = e.target;
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/html', e.target.innerHTML);
            e.target.style.opacity = '0.4';
        }

        function handleDragOver(e) {
            if (e.preventDefault) {
                e.preventDefault();
            }
            e.dataTransfer.dropEffect = 'move';
            return false;
        }

        function handleDrop(e) {
            if (e.stopPropagation) {
                e.stopPropagation();
            }
            
            if (draggedElement !== e.target && e.target.classList.contains('template-item')) {
                const fromIndex = parseInt(draggedElement.dataset.index);
                const toIndex = parseInt(e.target.dataset.index);
                
                const item = template.splice(fromIndex, 1)[0];
                template.splice(toIndex, 0, item);
                
                renderTemplate();
            }
            
            return false;
        }

        function handleDragEnd(e) {
            e.target.style.opacity = '1';
        }

        function addTextItem() {
            const text = prompt('Sabit metin girin:');
            if (text && text.trim()) {
                template.push({ type: 'text', value: text.trim() });
                renderTemplate();
                console.log('✅ Sabit metin eklendi:', text.trim());
            }
        }
        
        // TIKLAMA İLE ALAN EKLEME - YENİ!
        function addFieldByClick(fieldName) {
            console.log('🖱️ Tıklama ile alan ekleniyor:', fieldName);
            template.push({ type: 'field', value: fieldName });
            renderTemplate();
            
            // Görsel geri bildirim
            const builder = document.getElementById('templateBuilder');
            builder.style.background = '#c8e6c9';
            setTimeout(() => {
                builder.style.background = '#f8f9fa';
            }, 300);
        }
        
        function addAudioFile() {
            const audioName = prompt('Ses dosyası adını girin (örn: karsilama.wav):\n\n(Dosya asterisk/audio/calls/ klasöründe olmalı)');
            if (audioName && audioName.trim()) {
                // .wav uzantısı yoksa ekle
                let fileName = audioName.trim();
                if (!fileName.toLowerCase().endsWith('.wav')) {
                    fileName += '.wav';
                }
                
                template.push({ 
                    type: 'audio', 
                    value: fileName,
                    label: '🔊 Ses: ' + fileName
                });
                renderTemplate();
                alert('✅ Ses dosyası eklendi: ' + fileName);
                console.log('✅ Ses dosyası eklendi:', fileName);
            }
        }
        
        function addSilence() {
            const seconds = prompt('Sessizlik süresi (saniye):', '2');
            if (seconds && !isNaN(seconds) && seconds > 0) {
                template.push({ 
                    type: 'silence', 
                    value: seconds,
                    label: '⏱️ Sessizlik: ' + seconds + ' sn'
                });
                renderTemplate();
            }
        }

        function removeItem(index) {
            if (confirm('Bu öğeyi silmek istediğinizden emin misiniz?')) {
                template.splice(index, 1);
                renderTemplate();
            }
        }

        function updateItem(index, value) {
            template[index].value = value;
        }

        function saveTemplate() {
            document.getElementById('templateData').value = JSON.stringify(template);
            document.getElementById('saveForm').submit();
        }

        // İlk render
        renderTemplate();
    </script>
</body>
</html>
