<?php
/**
 * Basit Test Arama Kaydı Ekle
 * Minimal form - sadece gerekli alanlar
 */

require_once 'config.php';

$message = '';
$messageType = '';

// Form gönderildiyse
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_call'])) {
    try {
        $phoneNumber = preg_replace('/[^0-9]/', '', $_POST['phone']);
        $name = $_POST['name'];
        $message_text = $_POST['message'];
        
        if (strlen($phoneNumber) < 10) {
            throw new Exception("Telefon numarası en az 10 haneli olmalı!");
        }
        
        // Basit INSERT
        $sql = "INSERT INTO CALLS (
            PhoneNumber, Adi, Metin, 
            Status, CallCount, TryCallCount, 
            Aktif, Locked, TemplateID, TemplateName,
            RecordDate
        ) VALUES (
            ?, ?, ?,
            1, 0, 0,
            1, 0, 1, 'Varsayılan',
            NOW()
        )";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$phoneNumber, $name, $message_text]);
        
        $callId = $pdo->lastInsertId();
        $message = "✅ Kayıt başarıyla eklendi! Call ID: $callId";
        $messageType = 'success';
        
    } catch (Exception $e) {
        $message = "❌ Hata: " . $e->getMessage();
        $messageType = 'error';
    }
}

// Son eklenen kayıtlar
$lastCalls = [];
try {
    $stmt = $pdo->query("SELECT * FROM CALLS WHERE Status = 1 ORDER BY id DESC LIMIT 10");
    $lastCalls = $stmt->fetchAll();
} catch (Exception $e) {
    $lastCalls = [];
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hızlı Test Arama Ekle</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 {
            color: #2c3e50;
            margin-bottom: 10px;
            text-align: center;
        }
        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            color: #555;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 14px;
        }
        input, textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
            font-family: inherit;
            transition: all 0.3s;
        }
        input:focus, textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        textarea {
            resize: vertical;
            min-height: 100px;
        }
        .btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.5);
        }
        .recent-calls {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 2px solid #e0e0e0;
        }
        .recent-calls h3 {
            color: #2c3e50;
            margin-bottom: 15px;
            font-size: 18px;
        }
        .call-item {
            background: #f8f9fa;
            padding: 12px;
            margin-bottom: 10px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
        .call-item strong {
            color: #667eea;
        }
        .call-item small {
            color: #666;
            font-size: 12px;
        }
        .info {
            background: #e7f3ff;
            padding: 12px;
            border-radius: 8px;
            color: #004085;
            font-size: 13px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📞 Hızlı Test Arama Ekle</h1>
        <p class="subtitle">CALLS tablosuna direkt kayıt ekle</p>
        
        <?php if ($message): ?>
            <div class="message <?= $messageType ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <div class="info">
            💡 Bu form CALLS tablosuna direkt kayıt ekler. Sadece temel alanlar gerekli.
        </div>
        
        <form method="POST">
            <div class="form-group">
                <label for="phone">📱 Telefon Numarası *</label>
                <input 
                    type="text" 
                    id="phone" 
                    name="phone" 
                    placeholder="05551234567 veya 02121234567"
                    required
                    value="05551234567"
                >
            </div>
            
            <div class="form-group">
                <label for="name">👤 Ad Soyad *</label>
                <input 
                    type="text" 
                    id="name" 
                    name="name" 
                    placeholder="Ahmet Yılmaz"
                    required
                    value="Test Kullanıcı"
                >
            </div>
            
            <div class="form-group">
                <label for="message">💬 Mesaj Metni *</label>
                <textarea 
                    id="message" 
                    name="message" 
                    placeholder="Sayın ... ödeme hatırlatması için aradık..."
                    required
                >Sayın kullanıcı, bu bir test aramasıdır.</textarea>
            </div>
            
            <button type="submit" name="add_call" class="btn">
                ➕ Kaydı Ekle (Status=1)
            </button>
        </form>
        
        <?php if (!empty($lastCalls)): ?>
        <div class="recent-calls">
            <h3>📋 Son Eklenen Kayıtlar (Status=1)</h3>
            <?php foreach ($lastCalls as $call): ?>
                <div class="call-item">
                    <strong>ID: <?= $call['id'] ?></strong> - 
                    <?= htmlspecialchars($call['PhoneNumber']) ?> | 
                    <?= htmlspecialchars($call['Adi']) ?>
                    <br>
                    <small>
                        Status: <?= $call['Status'] ?> | 
                        CallCount: <?= $call['CallCount'] ?> | 
                        Locked: <?= $call['Locked'] ? '🔒' : '🔓' ?>
                    </small>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <div style="text-align: center; margin-top: 20px;">
            <a href="test_insert_call.php" style="color: #667eea; text-decoration: none;">
                📊 Gelişmiş Form
            </a>
            |
            <a href="otomatikarama.php" style="color: #667eea; text-decoration: none;">
                🏠 Ana Sayfa
            </a>
        </div>
    </div>
</body>
</html>
