<?php
/**
 * Template 2 - Tuş 2 Transfer Kontrolü
 * 100 numaralı dahiliye aktarım kontrolü
 */

require_once 'config.php';
require_once 'includes/SshHelper.php';

header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Template 2 Transfer Kontrolü</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, sans-serif; 
            padding: 40px; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            min-height: 100vh; 
        }
        .container { 
            max-width: 1200px; 
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
            margin-bottom: 15px;
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
        .info { 
            background: #d1ecf1; 
            color: #0c5460; 
            padding: 15px; 
            border-radius: 8px; 
            margin: 15px 0; 
            border-left: 4px solid #17a2b8; 
        }
        .warning { 
            background: #fff3cd; 
            color: #856404; 
            padding: 15px; 
            border-radius: 8px; 
            margin: 15px 0; 
            border-left: 4px solid #ffc107; 
        }
        pre { 
            background: #f4f4f4; 
            padding: 15px; 
            border-radius: 5px; 
            overflow-x: auto; 
            font-size: 13px; 
            border: 1px solid #ddd;
            max-height: 400px;
            overflow-y: auto;
        }
        .check-item {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 15px 0;
            border-left: 4px solid #667eea;
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
            border: none;
            cursor: pointer;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .btn-fix {
            background: linear-gradient(135deg, #f5576c 0%, #f093fb 100%);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #667eea;
            color: white;
            font-weight: 600;
        }
        tr:hover {
            background: #f5f5f5;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Template 2 - Tuş 2 Transfer Kontrolü</h1>
        <p>auto-calls-template-2 şablonunda 2 tuşuna basıldığında 100 numaralı dahiliye aktarım kontrolü</p>

        <?php
        try {
            echo "<div class='info'>";
            echo "<strong>🔧 SSH Bağlantı Bilgileri:</strong><br>";
            echo "Host: " . ASTERISK_SSH_HOST . ":" . ASTERISK_SSH_PORT . "<br>";
            echo "User: " . ASTERISK_SSH_USER;
            echo "</div>";

            $ssh = new SshHelper(
                ASTERISK_SSH_HOST,
                ASTERISK_SSH_PORT,
                ASTERISK_SSH_USER,
                ASTERISK_SSH_PASS
            );

            // 1. Veritabanı Kontrolü
            echo "<h2>📊 1. Veritabanı Kontrolü</h2>";
            echo "<div class='check-item'>";
            
            $stmt = $pdo->prepare("SELECT * FROM " . table('MESSAGE_TEMPLATES') . " WHERE id = 2");
            $stmt->execute();
            $template = $stmt->fetch();
            
            if ($template) {
                echo "<div class='success'>";
                echo "<strong>✅ Template 2 bulundu</strong><br>";
                echo "<strong>Şablon Adı:</strong> " . htmlspecialchars($template['TemplateName']) . "<br>";
                echo "<strong>Context:</strong> " . htmlspecialchars($template['DialplanContext']) . "<br>";
                echo "</div>";
                
                echo "<table>";
                echo "<tr><th>Parametre</th><th>Değer</th></tr>";
                echo "<tr><td><strong>Key2Action</strong></td><td>" . htmlspecialchars($template['Key2Action'] ?? 'N/A') . "</td></tr>";
                echo "<tr><td><strong>Key2TransferNumber</strong></td><td>" . htmlspecialchars($template['Key2TransferNumber'] ?? 'N/A') . "</td></tr>";
                echo "<tr><td><strong>Key2AudioFile</strong></td><td>" . htmlspecialchars($template['Key2AudioFile'] ?? 'N/A') . "</td></tr>";
                echo "</table>";
                
                // Beklenen değerler kontrolü
                if ($template['Key2Action'] !== 'transfer') {
                    echo "<div class='error'>";
                    echo "❌ <strong>SORUN:</strong> Key2Action 'transfer' olmalı, şu an: " . htmlspecialchars($template['Key2Action']);
                    echo "</div>";
                }
                
                if ($template['Key2TransferNumber'] !== '100') {
                    echo "<div class='warning'>";
                    echo "⚠️ <strong>DİKKAT:</strong> Key2TransferNumber '100' olmalı, şu an: " . htmlspecialchars($template['Key2TransferNumber'] ?? 'BOŞ');
                    echo "</div>";
                }
            } else {
                echo "<div class='error'>❌ Template 2 veritabanında bulunamadı!</div>";
            }
            
            echo "</div>";

            // 2. Local Dialplan Dosyası Kontrolü
            echo "<h2>📄 2. Local Dialplan Dosyası Kontrolü</h2>";
            echo "<div class='check-item'>";
            
            $localFile = __DIR__ . '/asterisk/dialplan/extensions_template_2.conf';
            
            if (file_exists($localFile)) {
                echo "<div class='success'>✅ Local dosya var: {$localFile}</div>";
                
                $content = file_get_contents($localFile);
                
                // Tuş 2 bölümünü bul
                if (preg_match('/; Tuş 2 Davranışı.*?exten => 2,1,.*?Hangup\(\)/s', $content, $match)) {
                    echo "<div class='info'>";
                    echo "<strong>🔍 Tuş 2 Konfigürasyonu:</strong>";
                    echo "<pre>" . htmlspecialchars($match[0]) . "</pre>";
                    echo "</div>";
                    
                    // 100 numarasını kontrol et
                    if (strpos($match[0], 'Local/100@from-internal') !== false) {
                        echo "<div class='success'>✅ Local dosyada 100 numaralı dahili tespit edildi</div>";
                    } else {
                        echo "<div class='error'>❌ Local dosyada 100 numaralı dahili bulunamadı</div>";
                    }
                } else {
                    echo "<div class='error'>❌ Tuş 2 konfigürasyonu bulunamadı</div>";
                }
            } else {
                echo "<div class='error'>❌ Local dosya bulunamadı: {$localFile}</div>";
            }
            
            echo "</div>";

            // 3. Asterisk Sunucusu Dosya Kontrolü
            echo "<h2>🌐 3. Asterisk Sunucusu Dosya Kontrolü</h2>";
            echo "<div class='check-item'>";
            
            $remoteFile = '/etc/asterisk/extensions_template_2.conf';
            $checkFile = $ssh->exec("test -f {$remoteFile} && echo 'EXISTS' || echo 'NOT_FOUND'");
            
            if (trim($checkFile) === 'EXISTS') {
                echo "<div class='success'>✅ Asterisk sunucusunda dosya var: {$remoteFile}</div>";
                
                // Dosya içeriğini çek
                $remoteContent = $ssh->exec("cat {$remoteFile}");
                
                // Tuş 2 bölümünü göster
                if (preg_match('/; Tuş 2 Davranışı.*?exten => 2,1,.*?Hangup\(\)/s', $remoteContent, $remoteMatch)) {
                    echo "<div class='info'>";
                    echo "<strong>🔍 Remote Tuş 2 Konfigürasyonu:</strong>";
                    echo "<pre>" . htmlspecialchars($remoteMatch[0]) . "</pre>";
                    echo "</div>";
                    
                    // 100 numarasını kontrol et
                    if (strpos($remoteMatch[0], 'Local/100@from-internal') !== false) {
                        echo "<div class='success'>✅ Remote dosyada 100 numaralı dahili tespit edildi</div>";
                    } else {
                        echo "<div class='error'>❌ Remote dosyada 100 numaralı dahili bulunamadı</div>";
                        echo "<div class='warning'>";
                        echo "📌 <strong>Çözüm:</strong> Şablonu yeniden kaydedin veya aşağıdaki butona tıklayın.";
                        echo "</div>";
                    }
                } else {
                    echo "<div class='error'>❌ Remote dosyada Tuş 2 konfigürasyonu bulunamadı</div>";
                }
            } else {
                echo "<div class='error'>❌ Asterisk sunucusunda dosya bulunamadı!</div>";
                echo "<div class='warning'>";
                echo "📌 <strong>Çözüm:</strong> Template'i yeniden oluşturmalısınız.";
                echo "</div>";
            }
            
            echo "</div>";

            // 4. Asterisk Context Kontrolü
            echo "<h2>☎️ 4. Asterisk Context Kontrolü</h2>";
            echo "<div class='check-item'>";
            
            $contextCheck = $ssh->exec("asterisk -rx 'dialplan show auto-calls-template-2'");
            
            if (strpos($contextCheck, 'auto-calls-template-2') !== false) {
                echo "<div class='success'>✅ Context yüklü: auto-calls-template-2</div>";
                
                // Tuş 2 extensionını kontrol et
                if (preg_match("/'2' =>.*/", $contextCheck)) {
                    echo "<div class='success'>✅ Extension '2' tanımlı</div>";
                    
                    // Context'in tamamını göster
                    echo "<details>";
                    echo "<summary style='cursor: pointer; color: #667eea; font-weight: 600;'>📄 Tam Dialplan (Tıklayın)</summary>";
                    echo "<pre>" . htmlspecialchars($contextCheck) . "</pre>";
                    echo "</details>";
                } else {
                    echo "<div class='error'>❌ Extension '2' bulunamadı</div>";
                }
            } else {
                echo "<div class='error'>❌ Context yüklü değil veya bulunamadı</div>";
                echo "<div class='warning'>";
                echo "📌 <strong>Çözüm:</strong> Asterisk'te dialplan reload yapılmalı.";
                echo "</div>";
            }
            
            echo "</div>";

            // 5. Extension 100 Kontrolü
            echo "<h2>📞 5. Extension 100 Varlık Kontrolü</h2>";
            echo "<div class='check-item'>";
            
            $ext100Check = $ssh->exec("asterisk -rx 'core show hints' | grep '100@'");
            
            if (!empty(trim($ext100Check))) {
                echo "<div class='success'>✅ Extension 100 tanımlı</div>";
                echo "<pre>" . htmlspecialchars($ext100Check) . "</pre>";
            } else {
                echo "<div class='warning'>⚠️ Extension 100 bulunamadı veya tanımlı değil</div>";
                echo "<div class='info'>";
                echo "📌 <strong>Not:</strong> Extension 100 PBX'te tanımlı olmalı. FreePBX'ten kontrol edin.";
                echo "</div>";
            }
            
            // Alternatif kontrol: from-internal context
            $fromInternalCheck = $ssh->exec("asterisk -rx 'dialplan show 100@from-internal' | head -20");
            
            if (strpos($fromInternalCheck, '100') !== false) {
                echo "<div class='success'>✅ Extension 100 from-internal context'inde var</div>";
                echo "<details>";
                echo "<summary style='cursor: pointer; color: #667eea; font-weight: 600;'>📄 Dialplan Detay</summary>";
                echo "<pre>" . htmlspecialchars($fromInternalCheck) . "</pre>";
                echo "</details>";
            }
            
            echo "</div>";

            // 6. Son Testler
            echo "<h2>🧪 6. Test Aramaları Kontrolü</h2>";
            echo "<div class='check-item'>";
            
            $stmt = $pdo->prepare("
                SELECT 
                    c.*,
                    CASE 
                        WHEN c.tus1 IS NOT NULL THEN CONCAT('Tuş 1: ', c.tus1)
                        WHEN c.tus2 IS NOT NULL THEN CONCAT('Tuş 2: ', c.tus2)
                        WHEN c.tus3 IS NOT NULL THEN CONCAT('Tuş 3: ', c.tus3)
                        ELSE 'Tuş basılmadı'
                    END as dtmf_status
                FROM " . table('CALLS') . " c
                WHERE c.ImportId IN (SELECT id FROM " . table('IMPORT_HISTORY') . " WHERE TemplateId = 2)
                ORDER BY c.RecordDate DESC
                LIMIT 10
            ");
            $stmt->execute();
            $recentCalls = $stmt->fetchAll();
            
            if (count($recentCalls) > 0) {
                echo "<div class='info'>";
                echo "<strong>📊 Template 2 ile Yapılan Son 10 Arama:</strong>";
                echo "</div>";
                
                echo "<table>";
                echo "<tr>";
                echo "<th>ID</th>";
                echo "<th>Telefon</th>";
                echo "<th>Durum</th>";
                echo "<th>SipCode</th>";
                echo "<th>Basılan Tuş</th>";
                echo "<th>Transfer No</th>";
                echo "<th>Tarih</th>";
                echo "</tr>";
                
                foreach ($recentCalls as $call) {
                    $statusClass = '';
                    if ($call['Status'] == 2) $statusClass = 'style="color: green;"';
                    elseif ($call['Status'] == 0) $statusClass = 'style="color: red;"';
                    
                    // Basılan tuşu bul
                    $pressedKey = '-';
                    if (!empty($call['tus1'])) $pressedKey = "1 ({$call['tus1']})";
                    elseif (!empty($call['tus2'])) $pressedKey = "2 ({$call['tus2']})";
                    elseif (!empty($call['tus3'])) $pressedKey = "3 ({$call['tus3']})";
                    
                    // Status mesajı
                    $statusMsg = '';
                    if ($call['Status'] == 0) $statusMsg = 'Bekliyor';
                    elseif ($call['Status'] == 1) $statusMsg = 'Aranıyor';
                    elseif ($call['Status'] == 2) $statusMsg = 'Tamamlandı';
                    else $statusMsg = $call['Status'];
                    
                    echo "<tr>";
                    echo "<td>{$call['id']}</td>";
                    echo "<td>{$call['PhoneNumber']}</td>";
                    echo "<td {$statusClass}>" . htmlspecialchars($statusMsg) . "</td>";
                    echo "<td>" . ($call['SipCode'] ?? '-') . "</td>";
                    echo "<td>" . $pressedKey . "</td>";
                    echo "<td>" . ($call['TransferNo'] ?? '-') . "</td>";
                    echo "<td>" . date('d.m.Y H:i', strtotime($call['RecordDate'])) . "</td>";
                    echo "</tr>";
                }
                
                echo "</table>";
                
                // Tuş 2 basılan aramaları kontrol et
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) as count 
                    FROM " . table('CALLS') . " 
                    WHERE ImportId IN (SELECT id FROM " . table('IMPORT_HISTORY') . " WHERE TemplateId = 2) 
                    AND tus2 IS NOT NULL
                ");
                $stmt->execute();
                $key2Count = $stmt->fetch()['count'];
                
                if ($key2Count > 0) {
                    echo "<div class='success'>";
                    echo "✅ Template 2'de tuş 2 basılan {$key2Count} arama var";
                    echo "</div>";
                } else {
                    echo "<div class='warning'>";
                    echo "⚠️ Template 2'de henüz tuş 2 basılan arama yok";
                    echo "</div>";
                }
            } else {
                echo "<div class='warning'>";
                echo "⚠️ Template 2 ile henüz arama yapılmamış";
                echo "</div>";
            }
            
            echo "</div>";

            // Özet ve Öneriler
            echo "<h2>📋 Özet ve Öneriler</h2>";
            echo "<div class='check-item'>";
            
            $issues = [];
            
            if (!$template || $template['Key2Action'] !== 'transfer') {
                $issues[] = "Veritabanında Key2Action 'transfer' olarak ayarlanmalı";
            }
            
            if (!$template || $template['Key2TransferNumber'] !== '100') {
                $issues[] = "Veritabanında Key2TransferNumber '100' olmalı";
            }
            
            if (trim($checkFile) !== 'EXISTS') {
                $issues[] = "Asterisk sunucusunda dialplan dosyası eksik";
            }
            
            if (strpos($contextCheck, 'auto-calls-template-2') === false) {
                $issues[] = "Context Asterisk'te yüklü değil";
            }
            
            if (count($issues) > 0) {
                echo "<div class='error'>";
                echo "<strong>❌ Tespit Edilen Sorunlar:</strong><ul>";
                foreach ($issues as $issue) {
                    echo "<li>" . htmlspecialchars($issue) . "</li>";
                }
                echo "</ul>";
                echo "</div>";
                
                echo "<div class='info'>";
                echo "<strong>🔧 Önerilen Çözümler:</strong><br>";
                echo "1. Şablonu tekrar kaydetmeyi deneyin (sablonlar.php)<br>";
                echo "2. Veya aşağıdaki 'Düzelt' butonuna tıklayın";
                echo "</div>";
                
                echo "<form method='post' style='display: inline;'>";
                echo "<button type='submit' name='fix_template' class='btn btn-fix'>🔧 Template 2'yi Düzelt</button>";
                echo "</form>";
            } else {
                echo "<div class='success'>";
                echo "<h3>✅ Tüm Kontroller Başarılı!</h3>";
                echo "<p>Template 2'nin tuş 2 konfigürasyonu doğru görünüyor.</p>";
                echo "<p><strong>Transfer hedefi:</strong> 100 numaralı dahili</p>";
                echo "</div>";
            }
            
            echo "</div>";

            // Düzeltme işlemi
            if (isset($_POST['fix_template'])) {
                echo "<h2>🔧 Düzeltme İşlemi</h2>";
                echo "<div class='check-item'>";
                
                try {
                    // Veritabanını güncelle
                    $stmt = $pdo->prepare("
                        UPDATE " . table('MESSAGE_TEMPLATES') . "
                        SET Key2Action = 'transfer',
                            Key2TransferNumber = '100',
                            Key2AudioFile = 'otomatik/transfer',
                            UpdatedDate = NOW()
                        WHERE id = 2
                    ");
                    $stmt->execute();
                    
                    echo "<div class='success'>✅ Veritabanı güncellendi</div>";
                    
                    // Dialplan'ı yeniden oluştur
                    require_once 'includes/TemplateManager.php';
                    $templateManager = new TemplateManager($pdo);
                    $templateManager->generateDialplanForTemplate(2);
                    
                    echo "<div class='success'>✅ Dialplan yeniden oluşturuldu ve Asterisk'e gönderildi</div>";
                    
                    echo "<div class='info'>";
                    echo "<strong>🎉 İşlem Tamamlandı!</strong><br>";
                    echo "Sayfayı yenileyerek kontrolleri tekrar çalıştırın.";
                    echo "</div>";
                    
                    echo "<script>setTimeout(function(){ location.reload(); }, 3000);</script>";
                    
                } catch (Exception $e) {
                    echo "<div class='error'>";
                    echo "❌ Hata: " . htmlspecialchars($e->getMessage());
                    echo "</div>";
                }
                
                echo "</div>";
            }

        } catch (Exception $e) {
            echo "<div class='error'>";
            echo "<strong>❌ Hata:</strong> " . htmlspecialchars($e->getMessage());
            echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
            echo "</div>";
        }
        ?>

        <div style="text-align: center; margin-top: 30px;">
            <a href="sablonlar.php" class="btn">📝 Şablonlar</a>
            <a href="regenerate_all_templates.php" class="btn">🔄 Tüm Template'leri Yenile</a>
            <a href="check_cdr.php" class="btn">📊 CDR Kontrol</a>
        </div>
    </div>
</body>
</html>
