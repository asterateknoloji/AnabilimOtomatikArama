<?php
require_once 'includes/check_auth.php';

// Cache'i engelle
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yetkisiz Erişim</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            padding: 60px 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 500px;
            width: 100%;
            text-align: center;
        }
        .icon {
            font-size: 80px;
            color: #e74c3c;
            margin-bottom: 20px;
        }
        h1 {
            color: #2c3e50;
            font-size: 32px;
            margin-bottom: 15px;
        }
        p {
            color: #666;
            font-size: 16px;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        .user-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 30px;
            font-size: 14px;
            color: #555;
        }
        .btn {
            display: inline-block;
            padding: 14px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s;
            margin: 5px;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.5);
        }
        .btn-secondary {
            background: #95a5a6;
        }
    </style>
</head>
<body>
    <div class="container">
        <i class="fas fa-ban icon"></i>
        <h1>Yetkisiz Erişim</h1>
        <p>Bu sayfaya erişim yetkiniz bulunmamaktadır.</p>
        
        <div class="user-info">
            <i class="fas fa-user"></i> <strong><?= htmlspecialchars($currentUser['username']) ?></strong><br>
            <?php 
            $roles = $auth->getUserRoles($currentUser['id']);
            if (!empty($roles)) {
                echo '<small>Rol: ' . implode(', ', $roles) . '</small>';
            }
            ?>
        </div>
        
        <?php
        // Gelinen sayfayı kontrol et
        $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
        $showBackButton = !empty($referer) && 
                          strpos($referer, 'unauthorized.php') === false && 
                          strpos($referer, 'login.php') === false &&
                          strpos($referer, 'logout.php') === false;
        ?>
        
        <?php if ($showBackButton): ?>
        <button onclick="goBack()" class="btn" style="border: none; cursor: pointer; background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);">
            <i class="fas fa-arrow-left"></i> Geri Dön
        </button>
        <?php endif; ?>
        
        <button onclick="goHome()" class="btn" style="border: none; cursor: pointer;">
            <i class="fas fa-home"></i> Ana Sayfaya Dön
        </button>
        
        <a href="logout.php" class="btn btn-secondary">
            <i class="fas fa-sign-out-alt"></i> Çıkış Yap
        </a>
    </div>
    
    <script>
        // Gelinen sayfaya geri dön
        function goBack() {
            <?php if ($showBackButton): ?>
            window.location.href = '<?= htmlspecialchars($referer, ENT_QUOTES, 'UTF-8') ?>';
            <?php endif; ?>
        }
        
        // History'yi temizleyerek ana sayfaya yönlendir
        function goHome() {
            window.location.replace('otomatikarama.php');
        }
    </script>
</body>
</html>
