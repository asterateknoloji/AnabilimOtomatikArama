<?php
/**
 * CALLS Tablosu Yapısını Göster
 */

require_once 'config.php';

echo "<h2>📋 CALLS Tablosu - Kolon Yapısı</h2>";
echo "<hr>";

try {
    $stmt = $pdo->query("SHOW COLUMNS FROM CALLS");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>✅ Mevcut Kolonlar (" . count($columns) . " adet):</h3>";
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Kolon Adı</th><th>Tip</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td><strong>" . $col['Field'] . "</strong></td>";
        echo "<td>" . $col['Type'] . "</td>";
        echo "<td>" . $col['Null'] . "</td>";
        echo "<td>" . $col['Key'] . "</td>";
        echo "<td>" . $col['Default'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    echo "<hr>";
    
    // Gerekli kolonları kontrol et
    $requiredColumns = ['ImportId', 'PhoneNumber', 'Adi', 'Status', 'CallCount', 'Locked', 'Aktif'];
    $existingColumns = array_column($columns, 'Field');
    
    echo "<h3>🔍 Gerekli Kolon Kontrolü:</h3>";
    echo "<ul>";
    foreach ($requiredColumns as $req) {
        if (in_array($req, $existingColumns)) {
            echo "<li>✅ <strong>$req</strong> - Mevcut</li>";
        } else {
            echo "<li>❌ <strong style='color: red;'>$req</strong> - EKSİK!</li>";
        }
    }
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "<h3 style='color: red;'>❌ HATA: " . $e->getMessage() . "</h3>";
}

echo "<hr>";
echo "<a href='arama_yonetimi.php' style='background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Geri Dön</a>";
