<?php
/**
 * TTS Cache Yönetim Sayfası
 */

require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/TTSCache.php';

$cache = new TTSCache();

// Temizleme işlemi
if (isset($_POST['clear_cache'])) {
    $count = $cache->clear();
    $message = "✅ $count adet cache dosyası silindi.";
}

if (isset($_POST['cleanup_old'])) {
    $count = $cache->cleanup();
    $message = "✅ $count adet eski cache dosyası temizlendi.";
}

// İstatistikleri al
$stats = $cache->getStats();

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TTS Cache Yönetimi</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .cache-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .stat-card {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-value {
            font-size: 2em;
            font-weight: bold;
            color: #2196F3;
            margin: 10px 0;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9em;
        }
        
        .progress-bar {
            width: 100%;
            height: 30px;
            background: #e0e0e0;
            border-radius: 15px;
            overflow: hidden;
            margin: 20px 0;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #4CAF50, #2196F3);
            transition: width 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            margin: 5px;
        }
        
        .btn-danger {
            background: #f44336;
            color: white;
        }
        
        .btn-warning {
            background: #ff9800;
            color: white;
        }
        
        .alert {
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <h1>🎤 TTS Cache Yönetimi</h1>
        
        <?php if (isset($message)): ?>
            <div class="alert"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <div class="info-box">
            <h3>ℹ️ Cache Nedir?</h3>
            <p>
                TTS Cache, aynı metinlerin tekrar Google TTS ile oluşturulmasını önler. 
                Bu sayede <strong>toplu aramalarda büyük performans artışı</strong> sağlanır.
            </p>
            <p>
                <strong>Örnek:</strong> 100 kişiye aynı mesajı gönderiyorsanız, 
                ses dosyası sadece 1 kez oluşturulur, 99 kez cache'den alınır!
            </p>
        </div>
        
        <h2>📊 Cache İstatistikleri</h2>
        
        <div class="cache-stats">
            <div class="stat-card">
                <div class="stat-label">Toplam Dosya</div>
                <div class="stat-value"><?php echo number_format($stats['count']); ?></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-label">Kullanılan Alan</div>
                <div class="stat-value"><?php echo $stats['size_mb']; ?> MB</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-label">Limit</div>
                <div class="stat-value"><?php echo $stats['limit_mb']; ?> MB</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-label">Kullanım</div>
                <div class="stat-value"><?php echo $stats['usage_percent']; ?>%</div>
            </div>
        </div>
        
        <h3>Disk Kullanımı</h3>
        <div class="progress-bar">
            <div class="progress-fill" style="width: <?php echo min($stats['usage_percent'], 100); ?>%">
                <?php echo $stats['usage_percent']; ?>%
            </div>
        </div>
        
        <h2>🛠️ Yönetim</h2>
        
        <form method="post" style="margin: 20px 0;">
            <button type="submit" name="cleanup_old" class="btn btn-warning" 
                    onclick="return confirm('30 günden eski cache dosyaları silinecek. Devam edilsin mi?')">
                🧹 Eski Dosyaları Temizle (30+ gün)
            </button>
            
            <button type="submit" name="clear_cache" class="btn btn-danger"
                    onclick="return confirm('TÜM cache temizlenecek! Performans geçici olarak düşebilir. Emin misiniz?')">
                🗑️ Tüm Cache'i Temizle
            </button>
        </form>
        
        <div class="info-box" style="background: #fff3cd; border-color: #ffc107; color: #856404;">
            <h3>⚠️ Önemli Notlar</h3>
            <ul>
                <li><strong>Cache otomatik yönetilir</strong> - 500 MB limiti aşarsa en eski dosyalar silinir</li>
                <li><strong>30 gün sonra</strong> cache dosyaları otomatik expire olur</li>
                <li><strong>Toplu aramalar</strong> için cache mutlaka aktif tutun</li>
                <li><strong>Cache temizleme</strong> performansı geçici olarak düşürür</li>
            </ul>
        </div>
        
        <h2>📈 Performans Kazancı</h2>
        
        <table class="table">
            <thead>
                <tr>
                    <th>Senaryo</th>
                    <th>Cache YOK</th>
                    <th>Cache VAR</th>
                    <th>Kazanç</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>10 aynı mesaj</td>
                    <td>~15 saniye</td>
                    <td>~2 saniye</td>
                    <td><strong>7.5x daha hızlı!</strong></td>
                </tr>
                <tr>
                    <td>100 aynı mesaj</td>
                    <td>~150 saniye</td>
                    <td>~2 saniye</td>
                    <td><strong>75x daha hızlı!</strong></td>
                </tr>
                <tr>
                    <td>1000 aynı mesaj</td>
                    <td>~25 dakika</td>
                    <td>~2 saniye</td>
                    <td><strong>750x daha hızlı!</strong></td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>
