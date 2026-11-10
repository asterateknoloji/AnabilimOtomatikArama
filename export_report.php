<?php
// Session'ı başlat (Auth.php'den önce)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ob_start(); // Output buffering başlat

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 0); // Production'da hataları gösterme
ini_set('log_errors', 1); // Log'a yaz

try {
    require_once 'config.php';
    require_once 'includes/Auth.php';
    require_once 'includes/check_auth.php'; // hasPermission() fonksiyonu için
} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    exit('Config yükleme hatası: ' . $e->getMessage());
}

// Oturum kontrolü
try {
    $auth = new Auth($pdo);
    if (!$auth->isLoggedIn()) {
        ob_end_clean();
        http_response_code(401);
        exit('Oturum gerekli');
    }
} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    exit('Auth hatası: ' . $e->getMessage());
}

// İzin kontrolü
try {
    if (!hasPermission('report.view')) {
        ob_end_clean();
        http_response_code(403);
        exit('Yetkiniz yok');
    }
} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    exit('İzin kontrolü hatası: ' . $e->getMessage());
}

try {
    if (!isset($_GET['import_id'])) {
        ob_end_clean();
        exit('Import ID gerekli');
    }
    
    $importId = (int)$_GET['import_id'];
    
    // Import bilgisini al
    $importStmt = $pdo->prepare("
        SELECT OriginalFileName, ImportDate
        FROM " . table('IMPORT_HISTORY') . "
        WHERE id = ?
    ");
    $importStmt->execute([$importId]);
    $import = $importStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$import) {
        ob_end_clean();
        exit('Import bulunamadı');
    }
    
    // Detaylı kayıtlar
    $detailsStmt = $pdo->prepare("
        SELECT 
            CONCAT('\t', PhoneNumber) as 'Telefon',
            Adi as 'Ad Soyad',
            CONCAT(CallCount, ' / ', TryCallCount, ' arama') as 'Aranma',
            CASE 
                WHEN SipCode = 200 THEN 'Başarılı'
                WHEN Status = 1 THEN 'Bekliyor'
                ELSE 'Başarısız'
            END as 'Sonuç',
            Durum as 'Durum Detayı',
            CONCAT('\t', DATE_FORMAT(LastCallTime, '%d.%m.%Y %H:%i')) as 'Son Arama',
            IF(CONCAT_WS(' ', tus1, tus2) != '', CONCAT('\t', CONCAT_WS(' ', tus1, tus2)), '') as 'Tuş'
        FROM " . table('CALLS') . "
        WHERE ImportId = ?
        ORDER BY 
            CASE 
                WHEN Locked = 1 THEN 1
                WHEN Status = 1 THEN 2
                WHEN SipCode = 200 THEN 3
                ELSE 4
            END,
            LastCallTime DESC,
            id DESC
    ");
    
    $detailsStmt->execute([$importId]);
    $data = $detailsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($data)) {
        ob_end_clean();
        exit('Export edilecek veri bulunamadı');
    }
    
    // Dosya adını oluştur
    $baseName = pathinfo($import['OriginalFileName'], PATHINFO_FILENAME);
    $baseName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $baseName);
    $fileName = 'rapor_' . $baseName . '_' . date('Y-m-d_His') . '.csv';
    
    // Buffer'ı temizle ve header'ları gönder
    ob_end_clean();
    
    // CSV Headers
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    header('Cache-Control: max-age=0');
    
    // Output stream aç
    $output = fopen('php://output', 'w');
    
    // BOM ekle (UTF-8 için Excel uyumluluğu)
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Başlık satırını yaz
    if (!empty($data)) {
        fputcsv($output, array_keys($data[0]), ';');
    }
    
    // Veri satırlarını yaz
    foreach ($data as $row) {
        fputcsv($output, $row, ';');
    }
    
    fclose($output);
    exit;
    
} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    error_log('Export hatası: ' . $e->getMessage() . ' | ' . $e->getTraceAsString());
    exit('Export hatası: ' . $e->getMessage());
}
