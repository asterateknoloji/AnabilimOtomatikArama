<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/plain; charset=utf-8');

require_once 'config.php';

try {
    // USERS tablosu var mı kontrol et
    $allTables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $tables = array_filter($allTables, function($t) { 
        return stripos($t, 'USER') !== false; 
    });
    
    echo "=== KULLANICI TABLOLARI ===\n";
    foreach ($tables as $table) {
        echo "- $table\n";
    }
    
    if (count($tables) > 0) {
        echo "\n=== KULLANICILAR ===\n";
        foreach ($tables as $table) {
            try {
                $stmt = $pdo->query("SELECT * FROM `$table` LIMIT 5");
                $users = $stmt->fetchAll();
                
                echo "\nTablo: $table\n";
                echo "Kolonlar: " . implode(', ', array_keys($users[0] ?? [])) . "\n";
                foreach ($users as $user) {
                    print_r($user);
                    echo "---\n";
                }
            } catch (Exception $e) {
                echo "Hata: " . $e->getMessage() . "\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "❌ Hata: " . $e->getMessage();
}
