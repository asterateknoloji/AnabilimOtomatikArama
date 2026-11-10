<?php
/**
 * Varsayılan Çalışma Saatlerini Ekle
 * Pazartesi-Cuma: 10:00-18:00
 */

require_once 'config.php';

echo "<h1>Varsayılan Çalışma Saatleri Kurulumu</h1>";

try {
    // Önce mevcut kayıtları temizle
    $pdo->exec("TRUNCATE TABLE " . table('ZAMANLAR') . "");
    echo "✅ Eski kayıtlar temizlendi<br>";
    
    // Pazartesi-Cuma arası 10:00-18:00
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
        $stmt->execute([
            $gunNo,
            '2026-01-01 10:00:00',
            '2026-01-01 18:00:00'
        ]);
        echo "✅ {$gunAd}: 10:00 - 18:00 eklendi<br>";
    }
    
    echo "<hr>";
    echo "<h2>✅ Kurulum Tamamlandı!</h2>";
    echo "<p><strong>Çalışma Saatleri:</strong></p>";
    echo "<ul>";
    echo "<li>Pazartesi - Cuma: 10:00 - 18:00</li>";
    echo "<li>Cumartesi - Pazar: Kapalı</li>";
    echo "</ul>";
    
    echo "<hr>";
    echo "<h3>Mevcut Kayıtlar:</h3>";
    
    $stmt = $pdo->query("
        SELECT 
            CASE Gun
                WHEN 1 THEN 'Pazartesi'
                WHEN 2 THEN 'Salı'
                WHEN 3 THEN 'Çarşamba'
                WHEN 4 THEN 'Perşembe'
                WHEN 5 THEN 'Cuma'
                WHEN 6 THEN 'Cumartesi'
                WHEN 7 THEN 'Pazar'
            END as GunAdi,
            TIME(BaslamaSaati) as Baslama,
            TIME(BitisSaati) as Bitis
        FROM " . table('ZAMANLAR') . "
        ORDER BY Gun, BaslamaSaati
    ");
    
    $kayitlar = $stmt->fetchAll();
    
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr style='background: #667eea; color: white;'>";
    echo "<th>Gün</th><th>Başlama</th><th>Bitiş</th>";
    echo "</tr>";
    
    foreach ($kayitlar as $kayit) {
        echo "<tr>";
        echo "<td>{$kayit['GunAdi']}</td>";
        echo "<td>{$kayit['Baslama']}</td>";
        echo "<td>{$kayit['Bitis']}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<hr>";
    echo "<p><a href='calisma_saatleri.php' style='padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px;'>→ Çalışma Saatleri Yönetimine Git</a></p>";
    
} catch (Exception $e) {
    echo "❌ Hata: " . htmlspecialchars($e->getMessage());
}
?>
