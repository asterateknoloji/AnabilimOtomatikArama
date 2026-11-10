<?php
/**
 * Duplicate Kayıtları Temizleme Scripti
 * 
 * Aynı ImportId içinde aynı telefon numarası olan kayıtlardan
 * sadece EN ESKİ olanı tutar, diğerlerini siler.
 */

require_once 'config.php';
require_once 'includes/Database.php';
require_once 'includes/EnvLoader.php';

EnvLoader::load(__DIR__ . '/.env');

$db = Database::getInstance();
$pdo = $db->getConnection();

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Duplicate Kayıt Temizleme</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            color: #333;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        h1 {
            color: #667eea;
            margin-bottom: 20px;
            font-size: 28px;
        }
        h2 {
            color: #764ba2;
            margin: 30px 0 15px 0;
            font-size: 20px;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        .warning-box {
            background: #fff3cd;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #ffc107;
        }
        .info-box {
            background: #e8f5e9;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
        }
        .result-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .duplicate-item {
            background: white;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            border-left: 3px solid #dc3545;
        }
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            margin: 5px;
            text-decoration: none;
            display: inline-block;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        .btn-danger:hover {
            background: #c82333;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
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
            background: #f8f9fa;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧹 Duplicate Kayıt Temizleme</h1>
        
        <?php
        if (isset($_POST['clean_duplicates'])) {
            // TEMIZLEME İŞLEMİ
            echo "<div class='warning-box'><strong>⚠️ UYARI:</strong> Temizleme işlemi başlatıldı...</div>";
            
            // Duplicate kayıtları bul ve en eski olanı tut
            $findDuplicates = $pdo->query("
                SELECT ImportId, PhoneNumber, COUNT(*) as count, MIN(id) as keep_id
                FROM " . table('CALLS') . "
                GROUP BY ImportId, PhoneNumber
                HAVING COUNT(*) > 1
            ");
            
            $duplicates = $findDuplicates->fetchAll(PDO::FETCH_ASSOC);
            $totalDeleted = 0;
            
            foreach ($duplicates as $dup) {
                // EN ESKİ kayıt dışındaki tümünü sil
                $deleteStmt = $pdo->prepare("
                    DELETE FROM " . table('CALLS') . " 
                    WHERE ImportId = ? 
                    AND PhoneNumber = ? 
                    AND id != ?
                ");
                $deleteStmt->execute([
                    $dup['ImportId'],
                    $dup['PhoneNumber'],
                    $dup['keep_id']
                ]);
                
                $deleted = $deleteStmt->rowCount();
                $totalDeleted += $deleted;
            }
            
            echo "<div class='success'>";
            echo "✅ Temizleme tamamlandı!<br>";
            echo "• Toplam {$totalDeleted} duplicate kayıt silindi.<br>";
            echo "• Her numaradan sadece EN ESKİ kayıt tutuldu.";
            echo "</div>";
            
            echo "<a href='arama_yonetimi.php' class='btn btn-secondary'>← Arama Yönetimine Dön</a>";
            
        } else {
            // DUPLICATE KAYITLARI GÖSTER
            echo "<div class='warning-box'>";
            echo "<strong>⚠️ UYARI:</strong> Bu işlem, aynı ImportId içinde aynı telefon numarasına sahip ";
            echo "kayıtlardan sadece EN ESKİ olanı tutar, diğerlerini siler!<br><br>";
            echo "<strong>Bu işlem GERİ ALINAMAZ!</strong>";
            echo "</div>";
            
            // Duplicate kayıtları bul
            $findDuplicates = $pdo->query("
                SELECT ImportId, PhoneNumber, COUNT(*) as count
                FROM " . table('CALLS') . "
                GROUP BY ImportId, PhoneNumber
                HAVING COUNT(*) > 1
                ORDER BY ImportId, PhoneNumber
            ");
            
            $duplicates = $findDuplicates->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($duplicates)) {
                echo "<div class='info-box'>";
                echo "✅ Harika! Sistemde duplicate kayıt bulunamadı.";
                echo "</div>";
                echo "<a href='arama_yonetimi.php' class='btn btn-secondary'>← Arama Yönetimine Dön</a>";
            } else {
                echo "<h2>📊 Bulunan Duplicate Kayıtlar</h2>";
                echo "<div class='info-box'>";
                echo "Toplam <strong>" . count($duplicates) . "</strong> farklı numara duplicate olarak bulundu.";
                echo "</div>";
                
                echo "<table>";
                echo "<thead><tr>";
                echo "<th>Import ID</th>";
                echo "<th>Telefon Numarası</th>";
                echo "<th>Tekrar Sayısı</th>";
                echo "<th>Silinecek Kayıt</th>";
                echo "</tr></thead>";
                echo "<tbody>";
                
                $totalToDelete = 0;
                foreach ($duplicates as $dup) {
                    $willDelete = $dup['count'] - 1; // En eski tutar, diğerleri silinir
                    $totalToDelete += $willDelete;
                    
                    echo "<tr>";
                    echo "<td>{$dup['ImportId']}</td>";
                    echo "<td>0{$dup['PhoneNumber']}</td>";
                    echo "<td>{$dup['count']} kez</td>";
                    echo "<td style='color: #dc3545; font-weight: 600;'>{$willDelete} kayıt silinecek</td>";
                    echo "</tr>";
                }
                
                echo "</tbody></table>";
                
                echo "<div class='result-box'>";
                echo "<strong>📋 ÖZET:</strong><br>";
                echo "• Toplam duplicate numara: " . count($duplicates) . "<br>";
                echo "• Toplam silinecek kayıt: <strong style='color: #dc3545;'>{$totalToDelete}</strong><br>";
                echo "• Tutulacak kayıt: <strong style='color: #28a745;'>" . count($duplicates) . "</strong> (en eski olanlar)";
                echo "</div>";
                
                echo "<form method='post' onsubmit='return confirm(\"⚠️ {$totalToDelete} kayıt SİLİNECEK!\\n\\nEmin misiniz? Bu işlem geri alınamaz!\");'>";
                echo "<button type='submit' name='clean_duplicates' class='btn btn-danger'>";
                echo "🗑️ Duplicate Kayıtları Temizle ({$totalToDelete} kayıt)";
                echo "</button>";
                echo "<a href='arama_yonetimi.php' class='btn btn-secondary'>❌ İptal</a>";
                echo "</form>";
            }
        }
        ?>
    </div>
</body>
</html>
