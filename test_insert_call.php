<?php
/**
 * Test Arama Kaydı Ekleme ve Otomatik Arama Başlatma
 */

require_once 'config.php';

echo "<!DOCTYPE html>";
echo "<html lang='tr'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<title>Test Arama Kaydı Ekle</title>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
    .container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    h1 { color: #333; border-bottom: 3px solid #667eea; padding-bottom: 10px; }
    h2 { color: #667eea; margin-top: 30px; }
    .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 4px solid #28a745; }
    .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 4px solid #dc3545; }
    .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 4px solid #17a2b8; }
    .form-group { margin: 15px 0; }
    label { display: block; font-weight: bold; margin-bottom: 5px; color: #555; }
    input, textarea, select { width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; font-size: 14px; }
    input:focus, textarea:focus, select:focus { outline: none; border-color: #667eea; }
    .btn { display: inline-block; padding: 12px 25px; background: #667eea; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; font-weight: bold; margin: 5px; }
    .btn:hover { background: #5568d3; }
    .btn-danger { background: #dc3545; }
    .btn-danger:hover { background: #c82333; }
    table { width: 100%; border-collapse: collapse; margin: 15px 0; }
    th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
    th { background: #667eea; color: white; }
    .code { background: #f4f4f4; padding: 2px 5px; border-radius: 3px; font-family: monospace; font-size: 12px; }
</style>";
echo "</head>";
echo "<body>";
echo "<div class='container'>";

echo "<h1>📞 Test Arama Kaydı Ekle</h1>";

// Değişkenleri başta tanımla
$projects = [];
$imports = [];
$templates = [];

// Mevcut projeleri listele
echo "<h2>📋 Mevcut Projeler</h2>";
try {
    $stmt = $pdo->query("SELECT * FROM PROJECTS ORDER BY id DESC LIMIT 10");
    $projects = $stmt->fetchAll();
    
    if (empty($projects)) {
        echo "<div class='info'>ℹ️ Hiç proje bulunamadı. Proje olmadan da kayıt ekleyebilirsiniz.</div>";
    } else {
        echo "<table>";
        echo "<tr><th>ID</th><th>Proje Adı</th><th>Oluşturma Tarihi</th><th>Durum</th></tr>";
        foreach ($projects as $project) {
            echo "<tr>";
            echo "<td>{$project['id']}</td>";
            echo "<td><strong>{$project['ProjectName']}</strong></td>";
            echo "<td>{$project['CreateDate']}</td>";
            echo "<td>" . ($project['IsActive'] ? '✅ Aktif' : '❌ Pasif') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<div class='info'>ℹ️ PROJECTS tablosu bulunamadı veya hata: " . $e->getMessage() . "</div>";
    $projects = [];
}

// Mevcut import kayıtlarını listele
echo "<h2>📥 Mevcut Import Kayıtları</h2>";
try {
    $stmt = $pdo->query("SELECT * FROM IMPORT_HISTORY ORDER BY id DESC LIMIT 10");
    $imports = $stmt->fetchAll();
    
    if (empty($imports)) {
        echo "<div class='info'>ℹ️ Hiç import kaydı yok. Import ID olmadan da kayıt ekleyebilirsiniz.</div>";
    } else {
        echo "<table>";
        echo "<tr><th>ID</th><th>Proje ID</th><th>Dosya Adı</th><th>Kayıt Sayısı</th><th>Tarih</th></tr>";
        foreach ($imports as $import) {
            echo "<tr>";
            echo "<td>{$import['id']}</td>";
            echo "<td>{$import['ProjectId']}</td>";
            echo "<td>{$import['FileName']}</td>";
            echo "<td>{$import['TotalRecords']}</td>";
            echo "<td>{$import['ImportDate']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<div class='info'>ℹ️ IMPORT_HISTORY tablosu bulunamadı veya hata: " . $e->getMessage() . "</div>";
    $imports = [];
}

// Mevcut template'leri listele
echo "<h2>📄 Mevcut Şablonlar</h2>";
$templates = [];
try {
    // Önce MESSAGE_TEMPLATES tablosunu dene
    $stmt = $pdo->query("SELECT * FROM MESSAGE_TEMPLATES ORDER BY id");
    $templates = $stmt->fetchAll();
    
    if (empty($templates)) {
        echo "<div class='info'>ℹ️ Hiç şablon yok. Varsayılan şablon (ID: 1) kullanılacak.</div>";
    } else {
        echo "<table>";
        echo "<tr><th>ID</th><th>Şablon Adı</th><th>Açıklama</th></tr>";
        foreach ($templates as $template) {
            echo "<tr>";
            echo "<td>{$template['id']}</td>";
            echo "<td><strong>{$template['TemplateName']}</strong></td>";
            echo "<td>" . ($template['Description'] ?? 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    // MESSAGE_TEMPLATES tablosu yoksa, TEMPLATES'i dene
    try {
        $stmt = $pdo->query("SELECT * FROM TEMPLATES ORDER BY id");
        $templates = $stmt->fetchAll();
        
        if (!empty($templates)) {
            echo "<table>";
            echo "<tr><th>ID</th><th>Şablon Adı</th><th>Açıklama</th></tr>";
            foreach ($templates as $template) {
                echo "<tr>";
                echo "<td>{$template['id']}</td>";
                echo "<td><strong>{$template['TemplateName']}</strong></td>";
                echo "<td>" . ($template['Description'] ?? 'N/A') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<div class='info'>ℹ️ Hiç şablon yok. Varsayılan şablon (ID: 1) kullanılacak.</div>";
        }
    } catch (Exception $e2) {
        // Hiçbir template tablosu yok
        echo "<div class='info'>ℹ️ Şablon tablosu bulunamadı. Manuel olarak TemplateID girebilirsiniz.</div>";
        $templates = [];
    }
}

// Form işleme
if (isset($_POST['insert_call'])) {
    echo "<div style='background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0;'>";
    echo "<h3>🔄 Arama Kaydı Ekleniyor...</h3>";
    
    try {
        $projectId = $_POST['project_id'] ?: null;
        $importId = $_POST['import_id'] ?: null;
        $phoneNumber = $_POST['phone_number'];
        $adi = $_POST['adi'];
        $metin = $_POST['metin'];
        $metin1 = $_POST['metin1'] ?: null;
        $metin2 = $_POST['metin2'] ?: null;
        $metin3 = $_POST['metin3'] ?: null;
        $tutar = $_POST['tutar'] ?: null;
        $templateId = $_POST['template_id'] ?: 1;
        $templateName = $_POST['template_name'] ?: 'Varsayılan';
        
        // Telefon numarasını kontrol et
        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
        if (strlen($phoneNumber) < 10) {
            throw new Exception("Geçersiz telefon numarası! En az 10 haneli olmalı.");
        }
        
        // Kaydı ekle
        $sql = "INSERT INTO CALLS (
            ProjectId, ImportId, PhoneNumber, Status, CallCount, TryCallCount,
            Adi, Metin, Metin1, Metin2, Metin3, Tutar, Aktif,
            TemplateID, TemplateName, RecordDate, Locked
        ) VALUES (
            ?, ?, ?, 1, 0, 0,
            ?, ?, ?, ?, ?, ?, 1,
            ?, ?, NOW(), 0
        )";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $projectId, $importId, $phoneNumber,
            $adi, $metin, $metin1, $metin2, $metin3, $tutar,
            $templateId, $templateName
        ]);
        
        if ($result) {
            $callId = $pdo->lastInsertId();
            echo "<p class='success'>✅ Arama kaydı başarıyla eklendi!</p>";
            echo "<table style='background: white; margin: 10px 0;'>";
            echo "<tr><th>Alan</th><th>Değer</th></tr>";
            echo "<tr><td><strong>Call ID</strong></td><td>{$callId}</td></tr>";
            echo "<tr><td><strong>Telefon</strong></td><td>{$phoneNumber}</td></tr>";
            echo "<tr><td><strong>Adı</strong></td><td>{$adi}</td></tr>";
            echo "<tr><td><strong>Metin</strong></td><td>{$metin}</td></tr>";
            echo "<tr><td><strong>Şablon ID</strong></td><td>{$templateId}</td></tr>";
            echo "<tr><td><strong>Şablon Adı</strong></td><td>{$templateName}</td></tr>";
            echo "</table>";
            
            // Arama başlatma seçeneği
            echo "<form method='POST'>";
            echo "<input type='hidden' name='call_id' value='{$callId}'>";
            echo "<button type='submit' name='start_call' class='btn'>📞 Aramayı Başlat</button>";
            echo "</form>";
        } else {
            throw new Exception("Kayıt eklenemedi!");
        }
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ Hata: " . $e->getMessage() . "</p>";
    }
    
    echo "</div>";
}

// Arama başlatma işlemi
if (isset($_POST['start_call'])) {
    $callId = $_POST['call_id'];
    
    echo "<div style='background: #d4edda; border-left: 4px solid #28a745; padding: 15px; margin: 20px 0;'>";
    echo "<h3>📞 Arama Başlatılıyor...</h3>";
    
    try {
        // AutoCallManager'ı kullan
        require_once 'includes/AutoCallManager.php';
        
        $autoCallManager = new AutoCallManager($pdo);
        $result = $autoCallManager->processCall($callId);
        
        if ($result['success']) {
            echo "<p class='success'>✅ Arama başarıyla başlatıldı!</p>";
            echo "<p><strong>Call File:</strong> <code>{$result['callfile']}</code></p>";
            if (isset($result['message'])) {
                echo "<p>{$result['message']}</p>";
            }
        } else {
            echo "<p class='error'>❌ Arama başlatılamadı: {$result['error']}</p>";
        }
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ Hata: " . $e->getMessage() . "</p>";
    }
    
    echo "</div>";
}

// Form
echo "<h2>➕ Yeni Arama Kaydı Ekle</h2>";
echo "<form method='POST'>";

echo "<div class='form-group'>";
echo "<label>Proje ID (Opsiyonel)</label>";
echo "<select name='project_id'>";
echo "<option value=''>-- Seçiniz --</option>";
if (!empty($projects)) {
    foreach ($projects as $project) {
        echo "<option value='{$project['id']}'>{$project['ProjectName']} (ID: {$project['id']})</option>";
    }
}
echo "</select>";
echo "</div>";

echo "<div class='form-group'>";
echo "<label>Import ID (Opsiyonel)</label>";
echo "<select name='import_id'>";
echo "<option value=''>-- Seçiniz --</option>";
if (!empty($imports)) {
    foreach ($imports as $import) {
        echo "<option value='{$import['id']}'>{$import['FileName']} (ID: {$import['id']})</option>";
    }
}
echo "</select>";
echo "</div>";

echo "<div class='form-group'>";
echo "<label>Telefon Numarası (*)</label>";
echo "<input type='text' name='phone_number' placeholder='Örn: 05551234567' required>";
echo "<small style='color: #666;'>Format: 05XX XXX XX XX veya 0212 XXX XX XX</small>";
echo "</div>";

echo "<div class='form-group'>";
echo "<label>Adı (*)</label>";
echo "<input type='text' name='adi' placeholder='Örn: Ahmet Yılmaz' required>";
echo "</div>";

echo "<div class='form-group'>";
echo "<label>Metin (Ana Mesaj) (*)</label>";
echo "<textarea name='metin' rows='3' placeholder='Örn: Sayın Ahmet Yılmaz, ödeme hatırlatması için aradık.' required></textarea>";
echo "</div>";

echo "<div class='form-group'>";
echo "<label>Metin1 (Ek Bilgi 1)</label>";
echo "<textarea name='metin1' rows='2' placeholder='Opsiyonel ek bilgi 1'></textarea>";
echo "</div>";

echo "<div class='form-group'>";
echo "<label>Metin2 (Ek Bilgi 2)</label>";
echo "<textarea name='metin2' rows='2' placeholder='Opsiyonel ek bilgi 2'></textarea>";
echo "</div>";

echo "<div class='form-group'>";
echo "<label>Metin3 (Ek Bilgi 3)</label>";
echo "<textarea name='metin3' rows='2' placeholder='Opsiyonel ek bilgi 3'></textarea>";
echo "</div>";

echo "<div class='form-group'>";
echo "<label>Tutar (Opsiyonel)</label>";
echo "<input type='number' step='0.01' name='tutar' placeholder='Örn: 1250.50'>";
echo "</div>";

echo "<div class='form-group'>";
echo "<label>Şablon ID</label>";
echo "<select name='template_id'>";
if (!empty($templates)) {
    foreach ($templates as $template) {
        $selected = $template['id'] == 1 ? 'selected' : '';
        echo "<option value='{$template['id']}' {$selected}>{$template['TemplateName']} (ID: {$template['id']})</option>";
    }
} else {
    echo "<option value='1' selected>Varsayılan (ID: 1)</option>";
    echo "<option value='6'>Şablon 6</option>";
}
echo "</select>";
echo "<small style='color: #666; display: block; margin-top: 5px;'>Manuel olarak TemplateID girebilirsiniz</small>";
echo "</div>";

echo "<div class='form-group'>";
echo "<label>Şablon Adı</label>";
echo "<input type='text' name='template_name' value='Varsayılan' placeholder='Örn: Varsayılan, Özel Şablon'>";
echo "</div>";

echo "<button type='submit' name='insert_call' class='btn'>➕ Kaydı Ekle</button>";
echo "<a href='otomatikarama.php' class='btn' style='background: #6c757d;'>🏠 Ana Sayfa</a>";

echo "</form>";

// Mevcut bekleyen aramalar
echo "<h2>📞 Bekleyen Aramalar (Status = 1)</h2>";
try {
    $stmt = $pdo->query("
        SELECT * FROM CALLS 
        WHERE Status = 1 AND Locked = 0 
        ORDER BY id DESC 
        LIMIT 20
    ");
    $pendingCalls = $stmt->fetchAll();
    
    if (empty($pendingCalls)) {
        echo "<div class='info'>ℹ️ Bekleyen arama yok.</div>";
    } else {
        echo "<table>";
        echo "<tr><th>ID</th><th>Telefon</th><th>Adı</th><th>Metin</th><th>Durum</th><th>İşlem</th></tr>";
        foreach ($pendingCalls as $call) {
            echo "<tr>";
            echo "<td>{$call['id']}</td>";
            echo "<td>{$call['PhoneNumber']}</td>";
            echo "<td>{$call['Adi']}</td>";
            echo "<td>" . substr($call['Metin'], 0, 50) . "...</td>";
            echo "<td>Status: {$call['Status']}, CallCount: {$call['CallCount']}</td>";
            echo "<td>";
            echo "<form method='POST' style='display: inline;'>";
            echo "<input type='hidden' name='call_id' value='{$call['id']}'>";
            echo "<button type='submit' name='start_call' class='btn' style='padding: 5px 10px; font-size: 12px;'>📞 Ara</button>";
            echo "</form>";
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<div class='error'>Hata: " . $e->getMessage() . "</div>";
}

echo "</div>";
echo "</body>";
echo "</html>";
