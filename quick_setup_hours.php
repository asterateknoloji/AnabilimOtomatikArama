<?php
/**
 * HIZLI KURULUM - Varsayılan Çalışma Saatleri
 */

require_once 'config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Kurulum</title></head><body>";
echo "<h1>Çalışma Saatleri Kurulumu</h1>";

try {
    // Önce temizle
    echo "<p>🗑️ Eski kayıtlar temizleniyor...</p>";
    $pdo->exec("DELETE FROM " . table('ZAMANLAR') . "");
    
    // Yeni kayıtları ekle
    echo "<p>➕ Yeni kayıtlar ekleniyor...</p>";
    
    $stmt = $pdo->prepare("
        INSERT INTO " . table('ZAMANLAR') . " (Gun, BaslamaSaati, BitisSaati) 
        VALUES (?, ?, ?)
    ");
    
    $gunler = [
        1 => 'Pazartesi',
        2 => 'Salı',
        3 => 'Çarşamba',
        4 => 'Perşembe',
        5 => 'Cuma'
    ];
    
    foreach ($gunler as $gunNo => $gunAd) {
        $result = $stmt->execute([
            $gunNo,
            '2026-01-01 10:00:00',
            '2026-01-01 18:00:00'
        ]);
        
        if ($result) {
            echo "<p>✅ <strong>{$gunAd}</strong>: 10:00 - 18:00</p>";
        } else {
            echo "<p>❌ <strong>{$gunAd}</strong>: Hata!</p>";
        }
    }
    
    echo "<hr>";
    echo "<h2 style='color: green;'>✅ KURULUM TAMAMLANDI!</h2>";
    
    // Kontrol
    $check = $pdo->query("SELECT COUNT(*) as total FROM " . table('ZAMANLAR') . "")->fetch();
    echo "<p><strong>Toplam Kayıt:</strong> {$check['total']}</p>";
    
    echo "<hr>";
    echo "<p><a href='calisma_saatleri.php' style='display: inline-block; padding: 15px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 8px; font-size: 18px; font-weight: bold;'>→ Çalışma Saatleri Sayfasına Git</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>❌ HATA:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "</body></html>";
?>
