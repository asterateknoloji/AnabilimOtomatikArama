<?php
/**
 * Extension 100 Durum Kontrolü
 */

require_once 'config.php';
require_once 'includes/SshHelper.php';

header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Extension 100 Kontrol</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, sans-serif; 
            padding: 40px; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            min-height: 100vh; 
        }
        .container { 
            max-width: 1000px; 
            margin: 0 auto; 
            background: white; 
            padding: 40px; 
            border-radius: 15px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.3); 
        }
        h1 { 
            color: #667eea; 
            border-bottom: 3px solid #667eea; 
            padding-bottom: 15px; 
            margin-bottom: 30px; 
        }
        h2 {
            color: #764ba2;
            margin-top: 30px;
            padding-bottom: 10px;
            border-bottom: 2px solid #eee;
        }
        .success { 
            background: #d4edda; 
            color: #155724; 
            padding: 15px; 
            border-radius: 8px; 
            margin: 15px 0; 
            border-left: 4px solid #28a745; 
        }
        .error { 
            background: #f8d7da; 
            color: #721c24; 
            padding: 15px; 
            border-radius: 8px; 
            margin: 15px 0; 
            border-left: 4px solid #dc3545; 
        }
        .warning { 
            background: #fff3cd; 
            color: #856404; 
            padding: 15px; 
            border-radius: 8px; 
            margin: 15px 0; 
            border-left: 4px solid #ffc107; 
        }
        .info { 
            background: #d1ecf1; 
            color: #0c5460; 
            padding: 15px; 
            border-radius: 8px; 
            margin: 15px 0; 
            border-left: 4px solid #17a2b8; 
        }
        pre { 
            background: #f4f4f4; 
            padding: 15px; 
            border-radius: 5px; 
            overflow-x: auto; 
            font-size: 13px; 
            border: 1px solid #ddd;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin: 10px 5px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📞 Extension 100 Durum Kontrolü</h1>

        <?php
        try {
            $ssh = new SshHelper(
                ASTERISK_SSH_HOST,
                ASTERISK_SSH_PORT,
                ASTERISK_SSH_USER,
                ASTERISK_SSH_PASS
            );

            // 1. SIP Peer Durumu
            echo "<h2>1. SIP Peer Durumu (Extension 100)</h2>";
            $sipShow = $ssh->exec("asterisk -rx 'sip show peer 100'");
            
            if (strpos($sipShow, 'Peer \'100\' not found') !== false || strpos($sipShow, 'No such peer') !== false) {
                echo "<div class='error'>";
                echo "❌ Extension 100 tanımlı değil!";
                echo "</div>";
            } else {
                echo "<div class='info'>";
                echo "<pre>" . htmlspecialchars($sipShow) . "</pre>";
                echo "</div>";
                
                // Registered durumunu kontrol et
                if (preg_match('/Status\s*:\s*(\S+)/', $sipShow, $match)) {
                    $status = $match[1];
                    if (strpos($status, 'OK') !== false || strpos($status, 'Reachable') !== false) {
                        echo "<div class='success'>✅ Extension 100 kayıtlı ve ulaşılabilir: {$status}</div>";
                    } else {
                        echo "<div class='error'>❌ Extension 100 OFFLINE veya ulaşılamaz: {$status}</div>";
                    }
                }
            }

            // 2. Extension 100 Hints
            echo "<h2>2. Extension Hints</h2>";
            $hints = $ssh->exec("asterisk -rx 'core show hints' | grep '100@'");
            
            if (!empty(trim($hints))) {
                echo "<div class='success'>✅ Extension 100 hint bulundu:</div>";
                echo "<pre>" . htmlspecialchars($hints) . "</pre>";
            } else {
                echo "<div class='warning'>⚠️ Extension 100 için hint bulunamadı</div>";
            }

            // 3. Active Channels
            echo "<h2>3. Aktif Kanallar (Extension 100)</h2>";
            $channels = $ssh->exec("asterisk -rx 'core show channels' | grep '100'");
            
            if (!empty(trim($channels))) {
                echo "<div class='warning'>⚠️ Extension 100 şu anda görüşmede:</div>";
                echo "<pre>" . htmlspecialchars($channels) . "</pre>";
            } else {
                echo "<div class='success'>✅ Extension 100 şu anda boş</div>";
            }

            // 4. Extension 100 Dialplan
            echo "<h2>4. Dialplan (from-internal context)</h2>";
            $dialplan = $ssh->exec("asterisk -rx 'dialplan show 100@from-internal'");
            
            if (strpos($dialplan, '100') !== false) {
                echo "<div class='success'>✅ Extension 100 dialplan'da tanımlı</div>";
                echo "<details>";
                echo "<summary style='cursor: pointer; color: #667eea; font-weight: 600;'>Dialplan Detayı</summary>";
                echo "<pre>" . htmlspecialchars($dialplan) . "</pre>";
                echo "</details>";
            } else {
                echo "<div class='error'>❌ Extension 100 dialplan'da bulunamadı</div>";
            }

            // 5. Tüm SIP Peers Listesi
            echo "<h2>5. Kayıtlı Tüm SIP Extensions</h2>";
            $allPeers = $ssh->exec("asterisk -rx 'sip show peers' | head -20");
            echo "<div class='info'>";
            echo "<strong>Aktif extensionlar:</strong>";
            echo "<pre>" . htmlspecialchars($allPeers) . "</pre>";
            echo "</div>";

            // 6. Öneriler
            echo "<h2>📋 Sonuç ve Öneriler</h2>";
            
            $isRegistered = (strpos($sipShow, 'OK') !== false || strpos($sipShow, 'Reachable') !== false);
            $exists = (strpos($sipShow, 'not found') === false);
            
            if (!$exists) {
                echo "<div class='error'>";
                echo "<strong>❌ SORUN: Extension 100 PBX'te tanımlı değil!</strong><br><br>";
                echo "<strong>Çözüm:</strong><br>";
                echo "1. FreePBX admin paneline girin<br>";
                echo "2. Applications > Extensions menüsünden Extension 100'ü oluşturun<br>";
                echo "3. Veya Template 2'yi düzenleyerek başka bir aktif dahiliye transfer yapın";
                echo "</div>";
            } elseif (!$isRegistered) {
                echo "<div class='warning'>";
                echo "<strong>⚠️ SORUN: Extension 100 OFFLINE (kayıtsız)</strong><br><br>";
                echo "<strong>Çözüm:</strong><br>";
                echo "1. Extension 100'ün kayıtlı olduğu telefonu kontrol edin<br>";
                echo "2. Telefon/softphone açık ve kayıtlı mı kontrol edin<br>";
                echo "3. SIP credential'ları kontrol edin<br>";
                echo "4. Veya Template 2'yi düzenleyerek başka bir ONLINE dahiliye transfer yapın<br><br>";
                
                // Alternatif extensionlar öner
                echo "<strong>💡 Alternatif Online Extensions:</strong><br>";
                $onlineExts = $ssh->exec("asterisk -rx 'sip show peers' | grep 'OK' | head -10");
                if (!empty(trim($onlineExts))) {
                    echo "<pre>" . htmlspecialchars($onlineExts) . "</pre>";
                    echo "Yukarıdaki extensionlardan birini kullanabilirsiniz.";
                }
                echo "</div>";
            } else {
                echo "<div class='success'>";
                echo "<strong>✅ Extension 100 aktif ve kayıtlı!</strong><br><br>";
                echo "Transfer çalışıyor ancak muhtemelen:<br>";
                echo "• Extension 100 çalmıyor (DND aktif)<br>";
                echo "• Çalıyor ama cevap verilmiyor<br>";
                echo "• Timeout süresi çok kısa<br>";
                echo "• Call Forward to Voicemail aktif<br><br>";
                echo "FreePBX'ten Extension 100 ayarlarını kontrol edin.";
                echo "</div>";
            }

        } catch (Exception $e) {
            echo "<div class='error'>";
            echo "<strong>❌ Hata:</strong> " . htmlspecialchars($e->getMessage());
            echo "</div>";
        }
        ?>

        <div style="text-align: center; margin-top: 30px;">
            <a href="sablonlar.php?edit=2" class="btn">✏️ Template 2'yi Düzenle</a>
            <a href="check_template2_transfer.php" class="btn">🔍 Transfer Kontrolü</a>
        </div>
    </div>
</body>
</html>
