<?php
/**
 * Import Debug Testi
 */

require_once 'config.php';
require_once 'includes/AutoCallManager.php';

echo "=== Excel Import Debug ===\n\n";

try {
    $manager = new AutoCallManager($pdo);
    
    $file = [
        'tmp_name' => __DIR__ . '/test_import.csv',
        'name' => 'test_import.csv'
    ];
    
    echo "[1/3] Dosya kontrol ediliyor...\n";
    if (!file_exists($file['tmp_name'])) {
        die("HATA: Dosya bulunamadı!\n");
    }
    echo "  ✓ Dosya mevcut: {$file['tmp_name']}\n\n";
    
    echo "[2/3] CSV içeriği okunuyor...\n";
    $content = file_get_contents($file['tmp_name']);
    echo "  İçerik:\n";
    echo "  " . str_replace("\n", "\n  ", $content) . "\n\n";
    
    echo "[3/3] Import başlatılıyor...\n";
    $result = $manager->importFromExcel($file, 1, null, 1);
    
    echo "\n========================================\n";
    echo "SONUÇ:\n";
    echo "========================================\n";
    echo "Başarılı: {$result['success']}\n";
    echo "Hatalı: {$result['errors']}\n\n";
    
    // Veritabanından kontrol et
    echo "Veritabanı Kontrolü:\n";
    echo "========================================\n";
    
    $stmt = $pdo->query("
        SELECT id, PhoneNumber, Adi, Tutar, ProjectId, ImportId 
        FROM " . table('CALLS') . " 
        ORDER BY id DESC 
        LIMIT 5
    ");
    
    $calls = $stmt->fetchAll();
    
    foreach ($calls as $call) {
        echo sprintf(
            "ID: %d | Tel: %s | Ad: %s | Tutar: %.2f | Proje: %s | Import: %s\n",
            $call['id'],
            $call['PhoneNumber'],
            $call['Adi'],
            $call['Tutar'],
            $call['ProjectId'] ?? 'NULL',
            $call['ImportId'] ?? 'NULL'
        );
    }
    
} catch (Exception $e) {
    echo "\n[HATA] " . $e->getMessage() . "\n";
    echo "\nStack Trace:\n";
    echo $e->getTraceAsString() . "\n";
}
