<?php
require_once 'includes/check_auth.php';

// İzin kontrolü
requirePermission('workhours.manage');

// Sayfa görüntüleme logu
logActivity('view', 'workhours', 'Çalışma Saatleri sayfası görüntülendi');

require_once 'config.php';

$message = '';
$messageType = 'success';

// Kayıt ekle/güncelle
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['update_time'])) {
            // Kayıt güncelle
            $id = (int)$_POST['id'];
            $baslamaSaati = $_POST['baslama_saati']; // HH:MM formatında
            $bitisSaati = $_POST['bitis_saati']; // HH:MM formatında
            
            // Validasyon
            if (empty($baslamaSaati) || empty($bitisSaati)) {
                throw new Exception('Saat alanları boş olamaz!');
            }
            
            if ($id <= 0) {
                throw new Exception('Geçersiz ID!');
            }
            
            // datetime formatına çevir
            $baslama = '2026-01-01 ' . $baslamaSaati . ':00';
            $bitis = '2026-01-01 ' . $bitisSaati . ':00';
            
            $stmt = $pdo->prepare("
                UPDATE " . table('ZAMANLAR') . " 
                SET BaslamaSaati = ?, BitisSaati = ? 
                WHERE id = ?
            ");
            $result = $stmt->execute([$baslama, $bitis, $id]);
            
            if (!$result) {
                throw new Exception('Kayıt güncellenemedi!');
            }
            
            // Log
            logActivity('update', 'workhours', "Çalışma saati güncellendi: {$baslamaSaati} - {$bitisSaati}", $id);
            
            // PRG pattern
            header('Location: calisma_saatleri.php?success=updated');
            exit;
            
        } elseif (isset($_POST['add_time'])) {
            // Debug: POST verilerini kontrol et
            error_log("Çalışma saati ekleniyor: Gun=" . $_POST['gun'] . ", Baslama=" . $_POST['baslama_saati'] . ", Bitis=" . $_POST['bitis_saati']);
            
            // Yeni kayıt ekle
            $gun = (int)$_POST['gun'];
            $baslamaSaati = $_POST['baslama_saati']; // HH:MM formatında
            $bitisSaati = $_POST['bitis_saati']; // HH:MM formatında
            
            // Validasyon
            if (empty($baslamaSaati) || empty($bitisSaati)) {
                throw new Exception('Saat alanları boş olamaz!');
            }
            
            if ($gun < 1 || $gun > 7) {
                throw new Exception('Geçersiz gün!');
            }
            
            // datetime formatına çevir (tarih kısmı önemli değil, sadece saat kullanılacak)
            $baslama = '2026-01-01 ' . $baslamaSaati . ':00';
            $bitis = '2026-01-01 ' . $bitisSaati . ':00';
            
            $stmt = $pdo->prepare("
                INSERT INTO " . table('ZAMANLAR') . " (Gun, BaslamaSaati, BitisSaati) 
                VALUES (?, ?, ?)
            ");
            $result = $stmt->execute([$gun, $baslama, $bitis]);
            
            if (!$result) {
                throw new Exception('Veritabanına kayıt eklenemedi!');
            }
            
            error_log("Çalışma saati başarıyla eklendi!");
            
            // PRG pattern
            header('Location: calisma_saatleri.php?success=added');
            exit;
            
        } elseif (isset($_POST['delete_time'])) {
            // Kayıt sil
            $id = (int)$_POST['id'];
            
            if ($id <= 0) {
                throw new Exception('Geçersiz ID!');
            }
            
            $stmt = $pdo->prepare("DELETE FROM " . table('ZAMANLAR') . " WHERE id = ?");
            $result = $stmt->execute([$id]);
            
            if (!$result) {
                throw new Exception('Kayıt silinemedi!');
            }
            
            // PRG pattern
            header('Location: calisma_saatleri.php?success=deleted');
            exit;
        }
        
    } catch (Exception $e) {
        error_log("Çalışma saati hatası: " . $e->getMessage());
        $message = '❌ Hata: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Tüm çalışma saatlerini getir
$stmt = $pdo->query("
    SELECT * FROM " . table('ZAMANLAR') . " 
    ORDER BY Gun ASC, BaslamaSaati ASC
");
$zamanlar = $stmt->fetchAll();

// Günlere göre grupla
$gunler = [
    1 => 'Pazartesi',
    2 => 'Salı',
    3 => 'Çarşamba',
    4 => 'Perşembe',
    5 => 'Cuma',
    6 => 'Cumartesi',
    7 => 'Pazar'
];

$zamanlarByGun = [];
foreach ($zamanlar as $zaman) {
    $zamanlarByGun[$zaman['Gun']][] = $zaman;
}

// Success message
if (isset($_GET['success'])) {
    if ($_GET['success'] === 'added') {
        $message = '✅ Çalışma saati başarıyla eklendi!';
    } elseif ($_GET['success'] === 'deleted') {
        $message = '✅ Çalışma saati silindi!';
    } elseif ($_GET['success'] === 'updated') {
        $message = '✅ Çalışma saati güncellendi!';
    } else {
        $message = '✅ İşlem başarılı!';
    }
    $messageType = 'success';
}

// Düzenlenecek kayıt
$editingId = isset($_GET['edit']) ? (int)$_GET['edit'] : null;
$editingTime = null;
if ($editingId) {
    $stmt = $pdo->prepare("SELECT * FROM " . table('ZAMANLAR') . " WHERE id = ?");
    $stmt->execute([$editingId]);
    $editingTime = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Çalışma Saatleri</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 1200px; margin: 0 auto; }
        .header {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        h1 { color: #667eea; font-size: 32px; }
        .subtitle { color: #666; font-size: 14px; margin-top: 5px; }
        .nav-links { display: flex; gap: 15px; }
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
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .day-section {
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 4px solid #667eea;
        }
        .day-header {
            font-size: 18px;
            font-weight: 600;
            color: #667eea;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .time-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .time-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 15px;
            background: white;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
        }
        .time-text {
            font-size: 16px;
            color: #333;
        }
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-block;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
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
        select, input[type="time"], input[type="datetime-local"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 15px;
        }
        .time-item input[type="time"] {
            width: auto;
            padding: 8px;
            font-size: 14px;
        }
        input[type="time"]::-webkit-calendar-picker-indicator,
        input[type="datetime-local"]::-webkit-calendar-picker-indicator {
            cursor: pointer;
        }
        /* 24 saat formatını zorla */
        input[type="time"] {
            -webkit-appearance: none;
            -moz-appearance: textfield;
        }
        .form-inline {
            display: grid;
            grid-template-columns: 2fr 2fr 2fr 1fr;
            gap: 15px;
            align-items: end;
        }
        .current-status {
            padding: 15px;
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
            border-radius: 10px;
            text-align: center;
            font-size: 16px;
            margin-bottom: 20px;
        }
        .status-warning {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1>⏰ Çalışma Saatleri</h1>
                <p class="subtitle">Otomatik aramaların yapılabileceği zaman aralıklarını belirleyin</p>
            </div>
            <div class="nav-links">
                <a href="otomatikarama.php">🏠 Ana Sayfa</a>
                <a href="arama_yonetimi.php">📋 Arama Yönetimi</a>
            </div>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?>">
            <?= htmlspecialchars($message) ?>
        </div>
        <?php endif; ?>

        <?php
        // Şu anki durum
        $currentDay = date('N');
        $currentTime = date('H:i:s');
        $isWorking = false;
        $currentRange = '';
        
        if (isset($zamanlarByGun[$currentDay])) {
            foreach ($zamanlarByGun[$currentDay] as $zaman) {
                $start = date('H:i:s', strtotime($zaman['BaslamaSaati']));
                $end = date('H:i:s', strtotime($zaman['BitisSaati']));
                
                if ($currentTime >= $start && $currentTime <= $end) {
                    $isWorking = true;
                    $currentRange = date('H:i', strtotime($start)) . ' - ' . date('H:i', strtotime($end));
                    break;
                }
            }
        }
        ?>

        <div class="current-status <?= !$isWorking ? 'status-warning' : '' ?>">
            <?php if ($isWorking): ?>
                ✅ Şu an çalışma saatleri içinde: <?= $currentRange ?>
            <?php else: ?>
                ⏸️ Şu an çalışma saatleri dışında - Aramalar yapılmayacak
            <?php endif; ?>
            <br>
            <small><?= $gunler[$currentDay] ?>, <?= date('H:i') ?></small>
        </div>

        <!-- Yeni Çalışma Saati Ekle -->
        <div class="card">
            <h2 style="margin-bottom: 20px; color: #667eea;">➕ Yeni Çalışma Saati Ekle</h2>
            
            <form method="post">
                <div class="form-inline">
                    <div class="form-group">
                        <label>Gün</label>
                        <select name="gun" required>
                            <?php foreach ($gunler as $gunNo => $gunAd): ?>
                            <option value="<?= $gunNo ?>"><?= $gunAd ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Başlama Saati</label>
                        <input type="time" name="baslama_saati" value="09:00" step="60" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Bitiş Saati</label>
                        <input type="time" name="bitis_saati" value="18:00" step="60" required>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" name="add_time" value="1" class="btn btn-primary" style="width: 100%;">
                            ➕ Ekle
                        </button>
                    </div>
                </div>
            </form>
            
            <?php if (isset($_POST['add_time'])): ?>
                <div style="margin-top: 10px; padding: 10px; background: #fff3cd; border-radius: 5px; font-size: 13px;">
                    ⚠️ Form gönderildi ama işlem tamamlanmadı. Hata mesajını yukarıda kontrol edin.
                </div>
            <?php endif; ?>
        </div>

        <!-- Tanımlı Çalışma Saatleri -->
        <div class="card">
            <h2 style="margin-bottom: 20px; color: #667eea;">📋 Tanımlı Çalışma Saatleri</h2>
            
            <?php if (empty($zamanlar)): ?>
                <p style="text-align: center; color: #999; padding: 40px;">
                    ⚠️ Henüz çalışma saati tanımlanmamış!<br>
                    <small>Yukarıdaki formu kullanarak ekleyebilirsiniz.</small>
                </p>
            <?php else: ?>
                <?php foreach ($gunler as $gunNo => $gunAd): ?>
                    <?php if (isset($zamanlarByGun[$gunNo])): ?>
                    <div class="day-section">
                        <div class="day-header">
                            <span><?= $gunAd ?></span>
                            <?php if ($gunNo == $currentDay): ?>
                                <span style="background: #11998e; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px;">Bugün</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="time-list">
                            <?php foreach ($zamanlarByGun[$gunNo] as $zaman): ?>
                            <?php if ($editingId == $zaman['id']): ?>
                                <!-- Düzenleme Formu -->
                                <div class="time-item" style="background: #fff3cd; border: 2px solid #ffc107;">
                                    <form method="post" style="display: flex; align-items: center; gap: 10px; width: 100%;">
                                        <input type="hidden" name="id" value="<?= $zaman['id'] ?>">
                                        <input type="time" name="baslama_saati" 
                                               value="<?= date('H:i', strtotime($zaman['BaslamaSaati'])) ?>" 
                                               step="60" required style="width: 120px; padding: 8px;">
                                        <span>-</span>
                                        <input type="time" name="bitis_saati" 
                                               value="<?= date('H:i', strtotime($zaman['BitisSaati'])) ?>" 
                                               step="60" required style="width: 120px; padding: 8px;">
                                        <button type="submit" name="update_time" class="btn btn-primary" style="padding: 8px 16px;">
                                            ✅ Kaydet
                                        </button>
                                        <a href="calisma_saatleri.php" class="btn" style="background: #6c757d; color: white; padding: 8px 16px; text-decoration: none;">
                                            ❌ İptal
                                        </a>
                                    </form>
                                </div>
                            <?php else: ?>
                                <!-- Normal Görünüm -->
                                <div class="time-item">
                                    <span class="time-text">
                                        🕐 <?= date('H:i', strtotime($zaman['BaslamaSaati'])) ?> - 
                                        <?= date('H:i', strtotime($zaman['BitisSaati'])) ?>
                                    </span>
                                    <div style="display: flex; gap: 10px;">
                                        <a href="?edit=<?= $zaman['id'] ?>" class="btn" style="background: #17a2b8; color: white; padding: 8px 16px; text-decoration: none;">
                                            ✏️ Düzenle
                                        </a>
                                        <form method="post" style="margin: 0;">
                                            <input type="hidden" name="id" value="<?= $zaman['id'] ?>">
                                            <button type="submit" name="delete_time" class="btn btn-danger" 
                                                    onclick="return confirm('Bu çalışma saatini silmek istediğinizden emin misiniz?')">
                                                🗑️ Sil
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Bilgi Kutusu -->
        <div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
            <h3 style="margin-bottom: 15px;">💡 Önemli Notlar</h3>
            <ul style="list-style: none; padding: 0; line-height: 1.8;">
                <li>✅ Bir gün için birden fazla zaman aralığı tanımlayabilirsiniz</li>
                <li>✅ Örnek: Pazartesi 09:00-12:00 ve 13:00-18:00</li>
                <li>✅ Çalışma saatleri dışında START butonuna basıldığında uyarı verilir</li>
                <li>✅ <code>retry_scheduler.php</code> otomatik olarak kontrol eder</li>
                <li>✅ Zamanlanmış aramalar da çalışma saatlerini bekler</li>
            </ul>
        </div>
    </div>

    <script>
        // 24 saat formatını zorla
        document.addEventListener('DOMContentLoaded', function() {
            const timeInputs = document.querySelectorAll('input[type="time"]');
            
            timeInputs.forEach(input => {
                // HTML5 time input zaten 24 saat formatı kullanır (HH:MM)
                // Ama görüntüleme tarayıcı diline bağlı olabilir
                // Bu yüzden step="60" ile sadece saat ve dakika girişi yapıyoruz
                
                // Değer her zaman HH:MM formatında olacak
                input.addEventListener('change', function() {
                    if (this.value) {
                        // HH:MM formatında olduğundan emin ol
                        const parts = this.value.split(':');
                        if (parts.length >= 2) {
                            const hours = parts[0].padStart(2, '0');
                            const minutes = parts[1].padStart(2, '0');
                            this.value = hours + ':' + minutes;
                        }
                    }
                });
            });
        });
    </script>
</body>
</html>
