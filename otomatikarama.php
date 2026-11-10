<?php
require_once 'includes/check_auth.php';

$username = $currentUser['adi'] . ' ' . $currentUser['soyadi'];
$userRoles = $auth->getUserRoles($currentUser['id']);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Otomatik Arama Sistemi</title>
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
            padding: 50px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            text-align: center;
            max-width: 600px;
            width: 100%;
        }
        h1 {
            color: #2c3e50;
            margin-bottom: 15px;
            font-size: 32px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 40px;
            font-size: 16px;
        }
        .menu-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
            margin-top: 30px;
        }
        .menu-btn {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 20px 25px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        .menu-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.5);
        }
        .menu-btn i {
            font-size: 24px;
            width: 30px;
        }
        .menu-text {
            text-align: left;
            flex: 1;
        }
        .menu-text strong {
            display: block;
            font-size: 18px;
            margin-bottom: 3px;
        }
        .menu-text small {
            font-size: 13px;
            opacity: 0.9;
        }
        .version {
            margin-top: 30px;
            color: #999;
            font-size: 13px;
        }
        .accordion-header {
            cursor: pointer;
            user-select: none;
            transition: all 0.3s;
            position: relative;
        }
        .accordion-header:hover {
            transform: translateX(5px);
        }
        .accordion-header::after {
            content: '\f078';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            transition: transform 0.3s;
        }
        .accordion-header.active::after {
            transform: translateY(-50%) rotate(180deg);
        }
        .accordion-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
        }
        .accordion-content.active {
            max-height: 2000px;
            transition: max-height 0.5s ease-in;
        }
    </style>
</head>
<body>
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div>
                <h1 style="margin-bottom: 0;">🤖 Otomatik Arama Sistemi</h1>
            </div>
            <div style="text-align: right;">
                <div style="color: #666; font-size: 14px; margin-bottom: 5px;">
                    <i class="fas fa-user-circle"></i> <?= htmlspecialchars($username) ?>
                </div>
                <div style="color: #999; font-size: 12px; margin-bottom: 5px;">
                    <?php if (!empty($userRoles)): ?>
                        <i class="fas fa-shield-alt"></i> <?= implode(', ', $userRoles) ?>
                    <?php endif; ?>
                </div>
                <a href="logout.php" style="color: #f5576c; text-decoration: none; font-size: 13px; font-weight: 600;">
                    <i class="fas fa-sign-out-alt"></i> Çıkış Yap
                </a>
            </div>
        </div>
        <p class="subtitle">Excel dosyasından müşterileri içe aktarın ve otomatik aramalar başlatın</p>
        
        <!-- ==================== ANA İŞLEMLER ==================== -->
        <div style="margin-top: 35px;">
            <h3 class="accordion-header active" onclick="toggleAccordion(this)" style="color: #2c3e50; font-size: 20px; margin-bottom: 0; padding: 15px 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 10px; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-phone-volume"></i> Ana İşlemler
            </h3>
            <div class="accordion-content active" style="margin-top: 15px;">
                <div class="menu-grid">
                    <a href="arama_yonetimi.php" class="menu-btn" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <i class="fas fa-phone-volume"></i>
                        <div class="menu-text">
                            <strong>Arama Yönetimi</strong>
                            <small>İçe aktarma, arama başlatma ve raporlama</small>
                        </div>
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- ==================== TANIMLAR ==================== -->
        <div style="margin-top: 35px;">
            <h3 class="accordion-header" onclick="toggleAccordion(this)" style="color: #2c3e50; font-size: 20px; margin-bottom: 0; padding: 15px 20px; background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; border-radius: 10px; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-cogs"></i> Tanımlar
            </h3>
            <div class="accordion-content" style="margin-top: 15px;">
                <div class="menu-grid">
                    <a href="projeler.php" class="menu-btn" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                        <i class="fas fa-folder-open"></i>
                        <div class="menu-text">
                            <strong>Projeler</strong>
                            <small>Proje tanımlama ve yönetimi</small>
                        </div>
                        <i class="fas fa-chevron-right"></i>
                    </a>
                    
                    <a href="sablonlar.php" class="menu-btn" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                        <i class="fas fa-file-alt"></i>
                        <div class="menu-text">
                            <strong>Şablonlar</strong>
                            <small>Mesaj şablonları ve ses dosyaları</small>
                        </div>
                        <i class="fas fa-chevron-right"></i>
                    </a>

                    <a href="tts_tanimlari.php" class="menu-btn" style="background: linear-gradient(135deg, #3949ab 0%, #00acc1 100%);">
                        <i class="fas fa-volume-up"></i>
                        <div class="menu-text">
                            <strong>TTS Tanımları</strong>
                            <small>Windows, Piper ve dil/ses öncelikleri</small>
                        </div>
                        <i class="fas fa-chevron-right"></i>
                    </a>
                    
                    <a href="calisma_saatleri.php" class="menu-btn" style="background: linear-gradient(135deg, #ffa726 0%, #fb8c00 100%);">
                        <i class="fas fa-clock"></i>
                        <div class="menu-text">
                            <strong>Çalışma Saatleri</strong>
                            <small>Arama yapılacak zaman aralıkları</small>
                        </div>
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- ==================== LOG İŞLEMLERİ ==================== -->
        <?php if (hasPermission('log.view')): ?>
        <div style="margin-top: 35px;">
            <h3 class="accordion-header" onclick="toggleAccordion(this)" style="color: #2c3e50; font-size: 20px; margin-bottom: 0; padding: 15px 20px; background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white; border-radius: 10px; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-history"></i> Log İşlemleri
            </h3>
            <div class="accordion-content" style="margin-top: 15px;">
                <div class="menu-grid">
                    <a href="activity_logs.php" class="menu-btn" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                        <i class="fas fa-clipboard-list"></i>
                        <div class="menu-text">
                            <strong>Aktivite Logları</strong>
                            <small>Kullanıcı işlem geçmişi ve sistem logları</small>
                        </div>
                        <i class="fas fa-chevron-right"></i>
                    </a>
                    
                    <a href="login_history.php" class="menu-btn" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <i class="fas fa-user-clock"></i>
                        <div class="menu-text">
                            <strong>Login Geçmişi</strong>
                            <small>Giriş/çıkış işlemleri ve IP adresleri</small>
                        </div>
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- ==================== ROL VE YETKİ YÖNETİMİ ==================== -->
        <?php if (hasPermission('user.view') || hasPermission('config.view')): ?>
        <div style="margin-top: 35px;">
            <h3 class="accordion-header" onclick="toggleAccordion(this)" style="color: #2c3e50; font-size: 20px; margin-bottom: 0; padding: 15px 20px; background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); color: white; border-radius: 10px; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-shield-alt"></i> Rol ve Yetki Yönetimi
            </h3>
            <div class="accordion-content" style="margin-top: 15px;">
                <div class="menu-grid">
                <?php if (hasPermission('user.view')): ?>
                <a href="users_management.php" class="menu-btn" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                    <i class="fas fa-users"></i>
                    <div class="menu-text">
                        <strong>Kullanıcı Yönetimi</strong>
                        <small>Kullanıcılar, roller ve yetkiler</small>
                    </div>
                    <i class="fas fa-chevron-right"></i>
                </a>
                <?php endif; ?>
                
                <?php if (hasPermission('config.edit')): ?>
                <a href="role_management.php" class="menu-btn" style="background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);">
                    <i class="fas fa-shield-alt"></i>
                    <div class="menu-text">
                        <strong>Rol Yönetimi</strong>
                        <small>Rollerin yetkilerini tanımlayın</small>
                    </div>
                    <i class="fas fa-chevron-right"></i>
                </a>
                <?php endif; ?>
                
                <?php if (hasPermission('config.view')): ?>
                <a href="system_config.php" class="menu-btn" style="background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);">
                    <i class="fas fa-cogs"></i>
                    <div class="menu-text">
                        <strong>Sistem Ayarları</strong>
                        <small>Veritabanı, SSH ve Asterisk konfigürasyonu</small>
                    </div>
                    <i class="fas fa-chevron-right"></i>
                </a>
                
                <a href="test_custom_cleanup.php" class="menu-btn" style="background: linear-gradient(135deg, #ff9a56 0%, #ff6a88 100%);">
                    <i class="fas fa-broom"></i>
                    <div class="menu-text">
                        <strong>Dosya Temizleme</strong>
                        <small>Custom klasör otomatik temizleme sistemi</small>
                    </div>
                    <i class="fas fa-chevron-right"></i>
                </a>
                
                <a href="check_cron_health.php" class="menu-btn" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                    <i class="fas fa-heartbeat"></i>
                    <div class="menu-text">
                        <strong>Cron Sağlık Kontrolü</strong>
                        <small>Otomatik arama sisteminin sağlık durumu</small>
                    </div>
                    <i class="fas fa-chevron-right"></i>
                </a>
                <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="version">
            v1.0.0 | Otomatik Arama Sistemi
        </div>
    </div>

    <script>
        function toggleAccordion(header) {
            const content = header.nextElementSibling;
            const isActive = header.classList.contains('active');
            
            // Tüm accordion'ları kapat
            document.querySelectorAll('.accordion-header').forEach(h => {
                h.classList.remove('active');
            });
            document.querySelectorAll('.accordion-content').forEach(c => {
                c.classList.remove('active');
            });
            
            // Tıklanan accordion zaten açıksa kapalı kalsın, değilse aç
            if (!isActive) {
                header.classList.add('active');
                content.classList.add('active');
            }
        }

        // Menü tıklama logu
        document.addEventListener('DOMContentLoaded', function() {
            const menuButtons = document.querySelectorAll('.menu-btn');
            
            menuButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault(); // Sayfaya hemen gitmeyi engelle
                    
                    const menuUrl = this.getAttribute('href');
                    const menuName = this.querySelector('strong').textContent;
                    
                    // AJAX ile log kaydı oluştur
                    fetch('log_menu_click.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'menu_name=' + encodeURIComponent(menuName) + '&menu_url=' + encodeURIComponent(menuUrl)
                    })
                    .then(response => response.json())
                    .then(data => {
                        // Log kaydedildi, şimdi sayfaya git
                        window.location.href = menuUrl;
                    })
                    .catch(error => {
                        // Hata olsa bile sayfaya git
                        console.error('Log hatası:', error);
                        window.location.href = menuUrl;
                    });
                });
            });
        });
    </script>
</body>
</html>
