<?php
/**
 * Veritabanı Tabloları Kontrol
 */

require_once 'config.php';

echo "<!DOCTYPE html>";
echo "<html lang='tr'>";
echo "<head><meta charset='UTF-8'><title>Tablo Kontrolü</title></head>";
echo "<body style='font-family: Arial; padding: 20px;'>";
echo "<h1>📊 Veritabanı Tabloları</h1>";

// Tüm tabloları listele
$stmt = $pdo->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "<h2>Mevcut Tablolar (" . count($tables) . " adet):</h2>";
echo "<ul>";
foreach ($tables as $table) {
    echo "<li><strong>$table</strong></li>";
}
echo "</ul>";

// TEMPLATE ile ilgili tabloları bul
echo "<h2>Template İle İlgili Tablolar:</h2>";
$templateTables = array_filter($tables, function($table) {
    return stripos($table, 'TEMPLATE') !== false;
});

if (empty($templateTables)) {
    echo "<p style='color: red;'>❌ Hiç template tablosu bulunamadı!</p>";
} else {
    echo "<ul>";
    foreach ($templateTables as $table) {
        echo "<li style='color: green;'>✅ <strong>$table</strong></li>";
        
        // Tablo yapısını göster
        $columns = $pdo->query("SHOW COLUMNS FROM `$table`")->fetchAll();
        echo "<ul>";
        foreach ($columns as $col) {
            echo "<li>{$col['Field']} ({$col['Type']})</li>";
        }
        echo "</ul>";
    }
    echo "</ul>";
}

echo "</body></html>";
