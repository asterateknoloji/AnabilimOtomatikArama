<?php
/**
 * users Tablosu Yapısını Kontrol Et
 */

require_once __DIR__ . '/config.php';

echo "=== USERS TABLOSU YAPISI ===\n\n";

try {
    // Tablo yapısını göster
    $stmt = $pdo->query("DESCRIBE " . table('users'));
    $columns = $stmt->fetchAll();
    
    echo "Kolonlar:\n";
    foreach ($columns as $col) {
        echo "  - {$col['Field']} ({$col['Type']})";
        if ($col['Key'] == 'PRI') echo " [PRIMARY KEY]";
        if ($col['Null'] == 'NO') echo " [NOT NULL]";
        if ($col['Default']) echo " [DEFAULT: {$col['Default']}]";
        echo "\n";
    }
    
    echo "\n===========================================\n\n";
    
    // Superadmin kullanıcısını göster
    $stmt = $pdo->query("SELECT * FROM " . table('users') . " WHERE username = 'superadmin'");
    $user = $stmt->fetch();
    
    if ($user) {
        echo "SUPERADMIN Kullanıcı Verileri:\n";
        foreach ($user as $key => $value) {
            if (is_numeric($key)) continue; // Numeric indeksleri atla
            
            // Şifre alanını kısalt
            if (stripos($key, 'pass') !== false || stripos($key, 'hash') !== false) {
                $displayValue = substr($value, 0, 30) . "... (" . strlen($value) . " karakter)";
            } else {
                $displayValue = $value;
            }
            
            echo "  {$key}: {$displayValue}\n";
        }
    } else {
        echo "❌ superadmin kullanıcısı bulunamadı!\n";
    }
    
} catch (Exception $e) {
    echo "❌ HATA: " . $e->getMessage() . "\n";
}
