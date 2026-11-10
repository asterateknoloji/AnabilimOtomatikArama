<?php
/**
 * Users tablosuna dahili kolonu ekle
 */

require_once 'config.php';

header('Content-Type: text/html; charset=utf-8');

try {
    // Mevcut kolonları kontrol et
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h2>Mevcut Kolonlar:</h2>";
    echo "<ul>";
    foreach ($columns as $col) {
        echo "<li>" . htmlspecialchars($col) . "</li>";
    }
    echo "</ul>";
    
    // dahili kolonu var mı?
    if (in_array('dahili', $columns)) {
        echo "<p style='color: green;'>✅ 'dahili' kolonu zaten mevcut!</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ 'dahili' kolonu bulunamadı. Ekleniyor...</p>";
        
        // Kolonu ekle
        $pdo->exec("ALTER TABLE users ADD COLUMN dahili VARCHAR(50) NULL AFTER soyadi");
        
        echo "<p style='color: green;'>✅ 'dahili' kolonu başarıyla eklendi!</p>";
    }
    
    // Güncel yapıyı göster
    echo "<h2>Güncel Tablo Yapısı:</h2>";
    $stmt = $pdo->query("DESCRIBE users");
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Default']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<br><p style='color: green; font-weight: bold;'>✅ İşlem tamamlandı! Artık kullanıcı yönetimi çalışmalı.</p>";
    echo "<p><a href='users_management.php'>Kullanıcı Yönetimine Git</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Hata: " . htmlspecialchars($e->getMessage()) . "</p>";
}
