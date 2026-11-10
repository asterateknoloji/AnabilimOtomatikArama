<?php
// Output buffering başlat - JSON öncesi output'ları engelle
ob_start();

header('Content-Type: application/json; charset=utf-8');

require_once 'includes/check_auth.php';
require_once 'config.php';
require_once 'includes/ImportManager.php';
require_once 'SimpleXLSX.php';

// İçe aktarma yetkisi kontrolü
requirePermission('import.create');

use Shuchkin\SimpleXLSX;

try {
    if (!isset($_FILES['excel_file']) || !isset($_POST['project_id'])) {
        throw new Exception('Eksik parametreler');
    }
    
    $file = $_FILES['excel_file'];
    $projectId = (int)$_POST['project_id'];
    $templateId = isset($_POST['template_id']) && !empty($_POST['template_id']) ? (int)$_POST['template_id'] : null;
    $scheduledTime = isset($_POST['scheduled_time']) && !empty($_POST['scheduled_time']) ? $_POST['scheduled_time'] : null;
    
    // Template adını al
    $templateName = 'Varsayılan';
    if ($templateId) {
        $templateStmt = $pdo->prepare("SELECT TemplateName FROM " . table('MESSAGE_TEMPLATES') . " WHERE id = ?");
        $templateStmt->execute([$templateId]);
        $templateResult = $templateStmt->fetch();
        if ($templateResult) {
            $templateName = $templateResult['TemplateName'];
        }
    }
    
    // Tarih formatını MySQL'e uygun hale getir (datetime-local -> YYYY-MM-DD HH:MM:SS)
    if ($scheduledTime) {
        $scheduledTime = str_replace('T', ' ', $scheduledTime) . ':00';
    }
    
    if ($projectId <= 0) {
        throw new Exception('Geçersiz proje');
    }
    
    // Dosya kontrolü
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Dosya yükleme hatası');
    }
    
    $allowedExtensions = ['xls', 'xlsx'];
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($fileExtension, $allowedExtensions)) {
        throw new Exception('Sadece Excel dosyaları (.xls, .xlsx) kabul edilir');
    }
    
    // Dosya adını formatla
    $originalFileName = $file['name'];
    $datePrefix = date('Y-m-d');
    $fileBaseName = pathinfo($originalFileName, PATHINFO_FILENAME);
    $newFileName = "{$datePrefix}_{$fileBaseName}.{$fileExtension}";
    
    // Import Manager
    $importManager = new ImportManager($pdo);
    
    // Import kaydı oluştur
    $importId = $importManager->createImport(
        $projectId,
        $newFileName,
        $originalFileName,
        $templateId,
        $scheduledTime, // Zamanlanmış saat (varsa)
        60 // Varsayılan retry interval (dakika)
    );
    
    // SimpleXLSX ile Excel'i oku (ZIP extension gerektirmez!)
    $xlsx = SimpleXLSX::parse($file['tmp_name']);
    
    if (!$xlsx) {
        throw new Exception('Excel dosyası okunamadı: ' . SimpleXLSX::parseError());
    }
    
    $successCount = 0;
    $errorCount = 0;
    
    // İlk sayfayı oku
    $rows = $xlsx->rows();
    
    // İlk satırı atla (başlık)
    array_shift($rows);
    
    // ✅ Excel içinde tekrar eden numaraları takip et
    $seenPhones = [];
    $duplicateCount = 0;
    
    // Her satırı işle
    $rowNumber = 1;
    foreach ($rows as $row) {
        $rowNumber++;
        
        try {
            // Excel formatı: Telefon, AdSoyad, Tutar, Metin1, Metin2, Metin3
            $phoneNumber = isset($row[0]) ? trim($row[0]) : '';
            $name = isset($row[1]) ? trim($row[1]) : '';
            $amount = isset($row[2]) ? trim($row[2]) : 0;
            $metin1 = isset($row[3]) ? trim($row[3]) : '';
            $metin2 = isset($row[4]) ? trim($row[4]) : '';
            $metin3 = isset($row[5]) ? trim($row[5]) : '';
            
            // Telefon numarası kontrolü
            if (empty($phoneNumber) || strlen($phoneNumber) < 10) {
                $errorCount++;
                continue;
            }
            
            // ✅ KONTROL 1: Excel içinde bu numara daha önce eklendi mi?
            if (isset($seenPhones[$phoneNumber])) {
                $duplicateCount++;
                error_log("Duplicate phone in Excel: {$phoneNumber} (satır {$rowNumber})");
                continue; // Bu numarayı atla
            }
            
            // ✅ KONTROL 2: Veritabanında bu ImportId için bu numara var mı?
            $checkStmt = $pdo->prepare("
                SELECT COUNT(*) FROM " . table('CALLS') . " 
                WHERE ImportId = ? AND PhoneNumber = ?
            ");
            $checkStmt->execute([$importId, $phoneNumber]);
            $exists = $checkStmt->fetchColumn();
            
            if ($exists > 0) {
                $duplicateCount++;
                error_log("Duplicate phone in DB: {$phoneNumber} (ImportId: {$importId})");
                continue; // Bu numarayı atla
            }
            
            // Veritabanına ekle (Aktif = 0 - PASIF BAŞLAR, MANUEL AKTİF YAPILACAK!)
            $stmt = $pdo->prepare("
                INSERT INTO " . table('CALLS') . " 
                (PhoneNumber, Adi, Tutar, Metin1, Metin2, Metin3, ProjectId, ImportId, 
                 TemplateId, TemplateName,
                 Status, CallCount, TryCallCount, Aktif, RecordDate, Aramaadi, CallTime, LastCallTime)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 0, 3, 0, NOW(), ?, ?, ?)
            ");
            
            // Zamanlanmış arama varsa CallTime'ı set et, yoksa NOW() (HEMEN ARA!)
            $callTime = $scheduledTime ? $scheduledTime : date('Y-m-d H:i:s');
            $lastCallTime = null; // İlk aramada LastCallTime NULL
            
            error_log("📝 Import kaydı ekleniyor - Phone: $phoneNumber, Template: $templateName, CallTime: $callTime");
            
            $stmt->execute([
                $phoneNumber,
                $name,
                $amount,
                $metin1,
                $metin2,
                $metin3,
                $projectId,
                $importId,
                $templateId,
                $templateName,
                $name, // Aramaadi
                $callTime,
                $lastCallTime
            ]);
            
            // Bu numarayı işaretle
            $seenPhones[$phoneNumber] = true;
            $successCount++;
            
        } catch (Exception $e) {
            $errorCount++;
            error_log("Excel Import Satır {$rowNumber} hatası: " . $e->getMessage());
        }
    }
    
    // Duplicates varsa log'a yaz
    if ($duplicateCount > 0) {
        error_log("Import tamamlandı - {$duplicateCount} tekrar eden numara atlandı");
    }
    
    // Import kaydını güncelle
    // Status: 'pending' (Kayıtlar PASIF başlar, manuel aktif edilecek)
    $newStatus = 'pending';
    
    $pdo->prepare("
        UPDATE " . table('IMPORT_HISTORY') . " 
        SET RecordCount = ?, Status = ? 
        WHERE id = ?
    ")->execute([$successCount, $newStatus, $importId]);
    
    // ⚠️ OTOMATIK BAŞLATMA KALDIRILDI
    // Kayıtlar Aktif = 0 olarak ekleniyor
    // Kullanıcı manuel olarak "Aktif" yapacak
    $startedCalls = 0;
    
    $result = [
        'success' => $successCount,
        'errors' => $errorCount,
        'started' => $startedCalls,
        'duplicates' => $duplicateCount
    ];
    
    // Log
    $logMsg = "Yeni import oluşturuldu: {$originalFileName} ({$successCount} kayıt)";
    if ($duplicateCount > 0) {
        $logMsg .= " [{$duplicateCount} duplicate atlandı]";
    }
    logActivity('create', 'import', $logMsg, $importId);
    
    // Başarılı mesajı oluştur
    $message = "Import başarılı! Kayıtlar PASIF olarak eklendi. 'Aktif' yaparak başlatabilirsiniz.";
    if ($duplicateCount > 0) {
        $message .= " ({$duplicateCount} tekrar eden numara atlandı)";
    }
    
    // Başarılı
    // Output buffer'ı temizle ve sadece JSON döndür
    ob_end_clean();
    echo json_encode([
        'success' => true,
        'message' => $message,
        'recordCount' => $result['success'],
        'errors' => $result['errors'],
        'started' => $result['started'],
        'duplicates' => $result['duplicates'],
        'importId' => $importId
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    // Detaylı hata logu
    error_log("Quick Import Hatası: " . $e->getMessage());
    error_log("Stack Trace: " . $e->getTraceAsString());
    
    // Output buffer'ı temizle
    ob_end_clean();
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ], JSON_UNESCAPED_UNICODE);
}
