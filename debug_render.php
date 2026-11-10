<?php
// Session başlat ve login yap
session_start();
$_SESSION['logged_in'] = true;
$_SESSION['username'] = 'superadmin';

// Arama yönetimi sayfasını include et
ob_start();
try {
    include 'arama_yonetimi.php';
    $output = ob_get_clean();
    
    // Modal'ları kontrol et
    echo "=== MODAL KONTROLÜ ===\n\n";
    
    if (strpos($output, 'id="duplicateModal"') !== false) {
        echo "✅ duplicateModal HTML'de var\n";
    } else {
        echo "❌ duplicateModal HTML'de YOK!\n";
    }
    
    if (strpos($output, 'id="reportModal"') !== false) {
        echo "✅ reportModal HTML'de var\n";
    } else {
        echo "❌ reportModal HTML'de YOK!\n";
    }
    
    if (strpos($output, 'function showDuplicateModal') !== false) {
        echo "✅ showDuplicateModal fonksiyonu var\n";
    } else {
        echo "❌ showDuplicateModal fonksiyonu YOK!\n";
    }
    
    if (strpos($output, 'function showReport') !== false) {
        echo "✅ showReport fonksiyonu var\n";
    } else {
        echo "❌ showReport fonksiyonu YOK!\n";
    }
    
    // ÇOĞALT butonlarını say
    $cogaltCount = substr_count($output, '📋 ÇOĞALT');
    echo "✅ ÇOĞALT buton sayısı: $cogaltCount\n";
    
    // RAPOR butonlarını say
    $raporCount = substr_count($output, '📊 RAPOR');
    echo "✅ RAPOR buton sayısı: $raporCount\n";
    
    // Buton onclick'lerini kontrol et
    preg_match_all('/onclick="showDuplicateModal\(([^)]+)\)"/', $output, $matches);
    echo "✅ showDuplicateModal onclick sayısı: " . count($matches[0]) . "\n";
    
    preg_match_all('/onclick="showReport\(([^)]+)\)"/', $output, $matches2);
    echo "✅ showReport onclick sayısı: " . count($matches2[0]) . "\n";
    
    // JavaScript hatalarını kontrol et
    if (preg_match('/<script[^>]*>(.*?)<\/script>/s', $output, $scriptMatches)) {
        echo "\n=== JAVASCRIPT KONTROLÜ ===\n";
        if (strpos($scriptMatches[1], 'function showDuplicateModal') !== false) {
            echo "✅ JavaScript fonksiyonu tanımlı\n";
        }
    }
    
    // HTML boyutu
    echo "\n=== GENEL BİLGİ ===\n";
    echo "HTML boyutu: " . strlen($output) . " bytes\n";
    
} catch (Exception $e) {
    ob_end_clean();
    echo "❌ HATA: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
