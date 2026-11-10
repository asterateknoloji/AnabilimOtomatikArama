<?php
/**
 * OTOMATİK KURULUM SCRIPTI
 * 
 * Database yeni makineye taşındıysa bu scripti çalıştırın.
 * Tüm ayarları otomatik yapacak.
 */

session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

$step = isset($_GET['step']) ? intval($_GET['step']) : 1;
$data = isset($_SESSION['setup_data']) ? $_SESSION['setup_data'] : [];

// Form gönderildi mi?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['setup_data'] = array_merge($data, $_POST);
    $data = $_SESSION['setup_data'];
}

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Otomatik Arama Sistemi - Kurulum</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 600px;
            width: 100%;
            padding: 40px;
        }
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo svg {
            width: 80px;
            height: 80px;
            fill: #667eea;
        }
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }
        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            position: relative;
        }
        .steps::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 0;
            right: 0;
            height: 2px;
            background: #e0e0e0;
            z-index: 0;
        }
        .step {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #999;
            position: relative;
            z-index: 1;
        }
        .step.active {
            background: #667eea;
            color: white;
        }
        .step.completed {
            background: #4caf50;
            color: white;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }
        input[type="text"],
        input[type="password"],
        input[type="number"] {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        input:focus {
            outline: none;
            border-color: #667eea;
        }
        .help-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        .btn {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 20px;
        }
        .btn-primary {
            background: #667eea;
            color: white;
        }
        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        .btn-success {
            background: #4caf50;
            color: white;
        }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        .progress {
            height: 4px;
            background: #e0e0e0;
            border-radius: 2px;
            overflow: hidden;
            margin-bottom: 20px;
        }
        .progress-bar {
            height: 100%;
            background: #667eea;
            transition: width 0.3s;
        }
        .test-result {
            padding: 10px;
            margin: 5px 0;
            border-radius: 5px;
            font-size: 13px;
        }
        .test-success { background: #d4edda; color: #155724; }
        .test-error { background: #f8d7da; color: #721c24; }
        .test-warning { background: #fff3cd; color: #856404; }
        pre {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
            font-size: 12px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-1-13h2v6h-2zm0 8h2v2h-2z"/>
            </svg>
        </div>
        
        <h1>Otomatik Arama Sistemi</h1>
        <p class="subtitle">Kurulum Sihirbazı</p>
        
        <div class="progress">
            <div class="progress-bar" style="width: <?php echo ($step / 4 * 100); ?>%"></div>
        </div>
        
        <div class="steps">
            <div class="step <?php echo $step >= 1 ? 'active' : ''; ?> <?php echo $step > 1 ? 'completed' : ''; ?>">1</div>
            <div class="step <?php echo $step >= 2 ? 'active' : ''; ?> <?php echo $step > 2 ? 'completed' : ''; ?>">2</div>
            <div class="step <?php echo $step >= 3 ? 'active' : ''; ?> <?php echo $step > 3 ? 'completed' : ''; ?>">3</div>
            <div class="step <?php echo $step >= 4 ? 'active' : ''; ?>">4</div>
        </div>

        <?php if ($step == 1): ?>
            <!-- ADIM 1: Database Ayarları -->
            <h2 style="margin-bottom: 20px;">1. Veritabanı Ayarları</h2>
            
            <form method="POST" action="?step=2">
                <div class="form-group">
                    <label>🖥️ Database Host</label>
                    <input type="text" name="db_host" value="<?php echo $data['db_host'] ?? 'localhost'; ?>" required>
                    <div class="help-text">Lokal veritabanı için: localhost</div>
                </div>
                
                <div class="form-group">
                    <label>📊 Database Adı</label>
                    <input type="text" name="db_name" value="<?php echo $data['db_name'] ?? 'ANABILIMNEW'; ?>" required>
                </div>
                
                <div class="form-group">
                    <label>👤 Database Kullanıcı</label>
                    <input type="text" name="db_user" value="<?php echo $data['db_user'] ?? 'root'; ?>" required>
                </div>
                
                <div class="form-group">
                    <label>🔑 Database Şifre</label>
                    <input type="password" name="db_pass" value="<?php echo $data['db_pass'] ?? ''; ?>">
                    <div class="help-text">XAMPP varsayılan: boş bırakın</div>
                </div>
                
                <button type="submit" class="btn btn-primary">İleri →</button>
            </form>

        <?php elseif ($step == 2): ?>
            <!-- ADIM 2: Asterisk SSH Ayarları -->
            <h2 style="margin-bottom: 20px;">2. Asterisk Sunucu (SSH)</h2>
            
            <form method="POST" action="?step=3">
                <div class="form-group">
                    <label>🖥️ Asterisk Host</label>
                    <input type="text" name="asterisk_host" value="<?php echo $data['asterisk_host'] ?? '213.14.87.158'; ?>" required>
                </div>
                
                <div class="form-group">
                    <label>🔌 SSH Port</label>
                    <input type="number" name="asterisk_port" value="<?php echo $data['asterisk_port'] ?? '45555'; ?>" required>
                </div>
                
                <div class="form-group">
                    <label>👤 SSH Kullanıcı</label>
                    <input type="text" name="asterisk_user" value="<?php echo $data['asterisk_user'] ?? 'root'; ?>" required>
                </div>
                
                <div class="form-group">
                    <label>🔑 SSH Şifre</label>
                    <input type="password" name="asterisk_pass" value="<?php echo $data['asterisk_pass'] ?? ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label>📞 Caller ID</label>
                    <input type="text" name="caller_id" value="<?php echo $data['caller_id'] ?? '02164150000'; ?>" required>
                    <div class="help-text">Arayan numara</div>
                </div>
                
                <div class="form-group">
                    <label>📡 Default Trunk</label>
                    <input type="text" name="default_trunk" value="<?php echo $data['default_trunk'] ?? 'Telsam'; ?>" required>
                </div>
                
                <button type="submit" class="btn btn-primary">İleri →</button>
            </form>

        <?php elseif ($step == 3): ?>
            <!-- ADIM 3: Test ve Kurulum -->
            <h2 style="margin-bottom: 20px;">3. Test ve Kurulum</h2>
            
            <?php
            // .env dosyasını oluştur
            $envContent = "# Otomatik Arama Sistemi - Ortam Değişkenleri\n";
            $envContent .= "# Oluşturulma: " . date('Y-m-d H:i:s') . "\n\n";
            $envContent .= "# VERİTABANI AYARLARI\n";
            $envContent .= "DB_HOST=" . $data['db_host'] . "\n";
            $envContent .= "DB_NAME=" . $data['db_name'] . "\n";
            $envContent .= "DB_USER=" . $data['db_user'] . "\n";
            $envContent .= "DB_PASS=" . $data['db_pass'] . "\n\n";
            $envContent .= "# ASTERISK AMI AYARLARI\n";
            $envContent .= "AMI_HOST=" . $data['asterisk_host'] . "\n";
            $envContent .= "AMI_PORT=5038\n";
            $envContent .= "AMI_USER=astera\n";
            $envContent .= "AMI_PASS=astera2012\n\n";
            $envContent .= "# ASTERISK SSH AYARLARI\n";
            $envContent .= "ASTERISK_SSH_HOST=" . $data['asterisk_host'] . "\n";
            $envContent .= "ASTERISK_SSH_PORT=" . $data['asterisk_port'] . "\n";
            $envContent .= "ASTERISK_SSH_USER=" . $data['asterisk_user'] . "\n";
            $envContent .= "ASTERISK_SSH_PASS=" . $data['asterisk_pass'] . "\n\n";
            $envContent .= "# ASTERISK YOLLARI\n";
            $envContent .= "ASTERISK_SPOOL_PATH=/var/spool/asterisk/outgoing/\n";
            $envContent .= "ASTERISK_SOUNDS_PATH=/var/lib/asterisk/sounds/custom/\n\n";
            $envContent .= "# ARAYAN NUMARA\n";
            $envContent .= "CALLER_ID=" . $data['caller_id'] . "\n\n";
            $envContent .= "# GİDEN HAT (TRUNK)\n";
            $envContent .= "DEFAULT_TRUNK=" . $data['default_trunk'] . "\n\n";
            $envContent .= "# FFMPEG YOLU\n";
            $envContent .= "FFMPEG_PATH=\n\n";
            $envContent .= "# ŞİFRELEME ANAHTARI\n";
            $envContent .= "ENCRYPTION_KEY=" . base64_encode(random_bytes(32)) . "\n\n";
            $envContent .= "# UYGULAMA AYARLARI\n";
            $envContent .= "APP_ENV=production\n";
            $envContent .= "APP_DEBUG=false\n";
            
            file_put_contents(__DIR__ . '/.env', $envContent);
            
            echo '<div class="alert alert-success">✅ .env dosyası oluşturuldu!</div>';
            
            // Klasörleri oluştur
            $folders = ['logs', 'tmp', 'audio', 'uploads'];
            foreach ($folders as $folder) {
                if (!is_dir(__DIR__ . '/' . $folder)) {
                    mkdir(__DIR__ . '/' . $folder, 0777, true);
                }
                chmod(__DIR__ . '/' . $folder, 0777);
            }
            echo '<div class="alert alert-success">✅ Klasörler oluşturuldu ve izinler ayarlandı!</div>';
            
            // Test bağlantıları
            echo '<h3 style="margin: 20px 0 10px 0;">Bağlantı Testleri:</h3>';
            
            // MySQL Test
            try {
                $pdo = new PDO(
                    "mysql:host={$data['db_host']};dbname={$data['db_name']};charset=utf8mb4",
                    $data['db_user'],
                    $data['db_pass'],
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );
                echo '<div class="test-result test-success">✅ MySQL Bağlantısı: Başarılı</div>';
                
                // Tablo sayısını kontrol et
                $stmt = $pdo->query("SHOW TABLES");
                $tables = $stmt->fetchAll();
                echo '<div class="test-result test-success">✅ ' . count($tables) . ' tablo bulundu</div>';
                
            } catch (PDOException $e) {
                echo '<div class="test-result test-error">❌ MySQL Bağlantısı: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
            
            // SSH Test
            $connection = @fsockopen($data['asterisk_host'], $data['asterisk_port'], $errno, $errstr, 5);
            if ($connection) {
                echo '<div class="test-result test-success">✅ Asterisk SSH Port: Açık</div>';
                fclose($connection);
            } else {
                echo '<div class="test-result test-warning">⚠️ Asterisk SSH Port: ' . htmlspecialchars($errstr) . '</div>';
            }
            
            // PHP Uzantıları
            $extensions = ['pdo_mysql', 'openssl', 'curl', 'mbstring'];
            $allOk = true;
            foreach ($extensions as $ext) {
                if (extension_loaded($ext)) {
                    echo '<div class="test-result test-success">✅ PHP ' . $ext . ': Yüklü</div>';
                } else {
                    echo '<div class="test-result test-error">❌ PHP ' . $ext . ': Eksik!</div>';
                    $allOk = false;
                }
            }
            
            ?>
            
            <form method="POST" action="?step=4">
                <button type="submit" class="btn btn-primary">Son Adıma Geç →</button>
            </form>

        <?php elseif ($step == 4): ?>
            <!-- ADIM 4: Tamamlandı -->
            <h2 style="margin-bottom: 20px;">🎉 Kurulum Tamamlandı!</h2>
            
            <div class="alert alert-success">
                <strong>Başarılı!</strong> Sistem kurulumu tamamlandı.
            </div>
            
            <h3>📋 Yapılması Gerekenler:</h3>
            
            <div style="background: #f5f5f5; padding: 20px; border-radius: 8px; margin: 20px 0;">
                <h4 style="margin-top: 0;">1️⃣ Superadmin Şifresini Sıfırlayın</h4>
                <pre>d:\xampp\php\php.exe reset_superadmin.php</pre>
                
                <h4>2️⃣ Task Scheduler Görevlerini Kurun</h4>
                <pre>PowerShell'i YÖNETİCİ olarak açın:
cd D:\xampp\htdocs\WindowsOtomatikAramason97
.\setup_all_tasks.ps1</pre>
                
                <h4>3️⃣ Giriş Yapın</h4>
                <p><strong>URL:</strong> <a href="login.php">login.php</a></p>
                <p><strong>Kullanıcı:</strong> superadmin</p>
                <p><strong>Şifre:</strong> Astera2021 (reset_superadmin.php çalıştırdıktan sonra)</p>
            </div>
            
            <div style="background: #fff3cd; padding: 15px; border-radius: 8px; margin: 20px 0;">
                <strong>⚠️ Önemli:</strong> setup.php dosyasını güvenlik için silin veya yeniden adlandırın!
            </div>
            
            <a href="login.php" class="btn btn-success">Giriş Sayfasına Git →</a>
            
            <script>
                // Session'ı temizle
                <?php session_destroy(); ?>
            </script>
        <?php endif; ?>
    </div>
</body>
</html>
