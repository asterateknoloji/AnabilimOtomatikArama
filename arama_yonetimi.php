<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/check_auth.php';

// İzin kontrolü
requirePermission('import.view');

// Sayfa görüntüleme logu
logActivity('view', 'import', 'Arama Yönetimi sayfası görüntülendi');

require_once 'config.php';
require_once 'includes/SchemaManager.php';
require_once 'includes/ImportManager.php';
require_once 'includes/ProjectManager.php';
require_once 'includes/AutoCallManager.php';
require_once 'includes/BatchWorkerLauncher.php';

$importManager = new ImportManager($pdo);
ensureApplicationSchema($pdo);
$projectManager = new ProjectManager($pdo);
$autoCallManager = new AutoCallManager($pdo);

/**
 * Çalışma saatleri kontrolü
 */
function isWorkingHours($pdo) {
    $currentDayOfWeek = date('N'); // 1=Pazartesi, 7=Pazar
    $currentTime = date('H:i:s');
    
    try {
        // Tablo var mı kontrol et
        $checkTable = $pdo->query("SHOW TABLES LIKE 'ZAMANLAR'");
        if (!$checkTable->fetch()) {
            return [
                'allowed' => true, 
                'message' => '⚠️ ZAMANLAR tablosu bulunamadı. Lütfen setup.php veya fix_arama_yonetimi.php çalıştırın.'
            ];
        }
        
        $stmt = $pdo->prepare("
            SELECT BaslamaSaati, BitisSaati 
            FROM ZAMANLAR 
            WHERE Gun = ?
            ORDER BY id
        ");
        $stmt->execute([$currentDayOfWeek]);
        $workingHours = $stmt->fetchAll();
    } catch (Exception $e) {
        return [
            'allowed' => true, 
            'message' => '⚠️ Çalışma saatleri kontrolü yapılamadı: ' . $e->getMessage()
        ];
    }
    
    if (empty($workingHours)) {
        return [
            'allowed' => false, 
            'message' => "Bugün ({$currentDayOfWeek}) için çalışma saati tanımlı değil!"
        ];
    }
    
    foreach ($workingHours as $hours) {
        $startTime = date('H:i:s', strtotime($hours['BaslamaSaati']));
        $endTime = date('H:i:s', strtotime($hours['BitisSaati']));
        
        if ($currentTime >= $startTime && $currentTime <= $endTime) {
            return ['allowed' => true];
        }
    }
    
    $hoursText = [];
    foreach ($workingHours as $hours) {
        $startTime = date('H:i', strtotime($hours['BaslamaSaati']));
        $endTime = date('H:i', strtotime($hours['BitisSaati']));
        $hoursText[] = "{$startTime} - {$endTime}";
    }
    
    return [
        'allowed' => false,
        'message' => "Çalışma saatleri dışında! Şu an: " . date('H:i') . "\nÇalışma saatleri: " . implode(', ', $hoursText)
    ];
}

// Otomatik temizlik: 10 dakikadan eski LOCKED kayıtlarını unlock et
// GEÇİCİ OLARAK DEVRE DIŞI - Locked kolonu eklendikten sonra aktif edilecek
/*
try {
    $cleanupStmt = $pdo->prepare("
        UPDATE " . table('CALLS') . " 
        SET Locked = 0, EndCallTime = NOW()
        WHERE Locked = 1
        AND TIMESTAMPDIFF(MINUTE, COALESCE(LastCallTime, CallTime, RecordDate), NOW()) > 10
    ");
    $cleanupStmt->execute();
} catch (Exception $e) {
    error_log("Cleanup error: " . $e->getMessage());
    // Hata olsa bile devam et
}
*/

// Aramalar bitmiş import'larda Aktif=0 yap (sayfa yüklendiğinde)
try {
    $deactivateStmt = $pdo->query("
        SELECT ImportId FROM " . table('CALLS') . " 
        WHERE ImportId IS NOT NULL AND Aktif = 1 
        GROUP BY ImportId
        HAVING SUM(CASE WHEN Status != 2 THEN 1 ELSE 0 END) = 0
    ");
    while ($row = $deactivateStmt->fetch()) {
        $pdo->prepare("UPDATE " . table('CALLS') . " SET Aktif = 0 WHERE ImportId = ?")->execute([$row['ImportId']]);
    }
} catch (Exception $e) {
    error_log("Deactivate finished imports error: " . $e->getMessage());
}

// Tek dosya için arama başlatma
if (isset($_POST['start_import'])) {
    requirePermission('import.control');
    try {
        $importId = $_POST['import_id'];
        $projectId = isset($_POST['project_id']) && $_POST['project_id'] !== '' ? $_POST['project_id'] : null;
        // project_id boşsa mevcut import'un ProjectId'sini koru (sıfırlama yapma)
        if ($projectId === null) {
            $existingStmt = $pdo->prepare("SELECT ProjectId FROM " . table('IMPORT_HISTORY') . " WHERE id = ?");
            $existingStmt->execute([$importId]);
            $existing = $existingStmt->fetch();
            $projectId = $existing ? $existing['ProjectId'] : null;
        }
        $retryCount = (int)$_POST['retry_count'];
        $retryInterval = (int)$_POST['retry_interval'];
        $maxConcurrent = isset($_POST['max_concurrent']) ? (int)$_POST['max_concurrent'] : 10;
        $restart = isset($_POST['restart']) ? true : false;
        $templateId = isset($_POST['template_id']) ? (int)$_POST['template_id'] : null;
        
        // Import'un ayarlarını güncelle
        $updateSql = "UPDATE " . table('IMPORT_HISTORY') . " SET ProjectId = ?, RetryCount = ?, RetryIntervalMinutes = ?, MaxConcurrentCalls = ?";
        $updateParams = [$projectId, $retryCount, $retryInterval, $maxConcurrent];
        
        // Eğer şablon ID'si gönderildiyse onu da güncelle
        if ($templateId) {
            $updateSql .= ", TemplateId = ?";
            $updateParams[] = $templateId;
        }
        
        $updateSql .= " WHERE id = ?";
        $updateParams[] = $importId;
        
        $stmt = $pdo->prepare($updateSql);
        $stmt->execute($updateParams);
        
        // Eğer restart ise, mevcut kayıtları sıfırla (yeni kayıt oluşturma!)
        if ($restart) {
            // ✅ KAYITLARI İLK İMPORT EDİLDİĞİ GİBİ SIFIRLA!
            // Import sırasındaki değerlere döndür:
            // - Status = 1 (Bekliyor)
            // - CallCount = 0 (hiç aranmadı)
            // - TryCallCount = Tekrar sayısı (formdan)
            // - RecordDate = NOW() (kayıt tarihi güncellenir)
            // - CallTime = NOW() (hemen aranmaya hazır)
            // - LastCallTime, EndCallTime, SipCode, Durum = NULL
            // - tus1, tus2, tus3 = NULL (tuş basımları temizlenir)
            // - Locked = 0 (kilitli değil)
            // - Aktif = 0 (pasif - kullanıcı manuel "Aktif" yapacak)
            $resetStmt = $pdo->prepare("
                UPDATE " . table('CALLS') . " 
                SET Status = 1, 
                    CallCount = 0,
                    TryCallCount = ?,
                    RecordDate = NOW(),
                    CallTime = NOW(),
                    LastCallTime = NULL, 
                    EndCallTime = NULL,
                    SipCode = NULL,
                    Durum = NULL,
                    tus1 = NULL,
                    tus2 = NULL,
                    tus3 = NULL,
                    Locked = 0,
                    Aktif = 0,
                    ProjectId = ?
                WHERE ImportId = ?
            ");
            $resetStmt->execute([$retryCount, $projectId, $importId]);
            $resetCount = $resetStmt->rowCount();
            
            // Import bilgisini al
            $importInfo = $pdo->prepare("SELECT OriginalFileName FROM " . table('IMPORT_HISTORY') . " WHERE id = ?");
            $importInfo->execute([$importId]);
            $importData = $importInfo->fetch();
            
            // ✅ OTOMATIK ARAMA BAŞLATMA!
            $startedCalls = 0;
            if ($resetCount > 0) {
                require_once 'includes/AutoCallManager.php';
                $autoCallManager = new AutoCallManager($pdo);
                
                try {
                    // Sıfırlanan kayıtları hemen başlat
                    $startedCalls = $autoCallManager->startAllImportCalls($importId);
                    error_log("✅ YENİDEN BAŞLATMA - OTOMATIK BAŞLATILDI: {$startedCalls} arama (ImportId: {$importId})");
                } catch (Exception $e) {
                    error_log("⚠️ Yeniden başlatma - otomatik başlatma hatası: " . $e->getMessage());
                    // Hata olsa bile kayıtlar sıfırlandı, sadece log'la
                }
            }
            
            // Log
            logActivity('control', 'import', "Arama yeniden başlatıldı: {$importData['OriginalFileName']} ({$resetCount} kayıt sıfırlandı, {$startedCalls} arama başlatıldı)", $importId);
            
            $message = "✅ {$resetCount} kayıt yeniden başlatıldı!\n\n(Pasif konumda – aramaları başlatmak için Aktif yapın.)";
            header("Location: arama_yonetimi.php?success=" . urlencode($message));
            exit;
        }
        
        // Eğer restart değilse ve CALLS tablosunda kayıt yoksa, kayıtları oluştur
        if (!$restart) {
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM " . table('CALLS') . " WHERE ImportId = ?");
            $checkStmt->execute([$importId]);
            $recordCount = $checkStmt->fetchColumn();
            
            if ($recordCount == 0) {
                // Excel dosyasından kayıtları oku ve CALLS tablosuna ekle
                $importStmt = $pdo->prepare("SELECT FileName, TemplateId FROM " . table('IMPORT_HISTORY') . " WHERE id = ?");
                $importStmt->execute([$importId]);
                $importData = $importStmt->fetch();
                
                if ($importData && file_exists($importData['FileName'])) {
                    $imported = $importManager->importFromExcel(
                        $importData['FileName'], 
                        $projectId, 
                        $importData['TemplateId'],
                        $importId
                    );
                    
                    // Import kaydını güncelle
                    $updateStmt = $pdo->prepare("
                        UPDATE " . table('IMPORT_HISTORY') . " 
                        SET RecordCount = ? 
                        WHERE id = ?
                    ");
                    $updateStmt->execute([$imported['count'], $importId]);
                    
                    $message = "✅ {$imported['count']} kayıt tabloya eklendi ve arama başlatılıyor...";
                } else {
                    throw new Exception("Excel dosyası bulunamadı!");
                }
            }
        }
        
        // Tüm partileri yalnızca tek kilitli arka plan işçisi başlatır.
        // Doğrudan başlatma, supervisor ile yarışıp Hız limitini aşabiliyordu.
        $count = 0;
        launchBatchCallWorker($importId, false);
        
        // Import bilgisini al
        $importInfo = $pdo->prepare("SELECT OriginalFileName FROM " . table('IMPORT_HISTORY') . " WHERE id = ?");
        $importInfo->execute([$importId]);
        $importData = $importInfo->fetch();
        
        if ($restart) {
            $message = "✅ {$count} kayıt SIFIRLANDI ve yeni proje için arama başlatıldı! (Parti: {$maxConcurrent} kişi, Aralık: {$retryInterval} saniye)";
        } else {
            // Log
            logActivity('control', 'import', "Arama başlatıldı: {$importData['OriginalFileName']} ({$count} kayıt)", $importId);
            $message = "✅ {$count} arama başlatıldı! (Parti: {$maxConcurrent} kişi, Aralık: {$retryInterval} saniye)";
        }
        
        // POST sonrası redirect yap (tekrar submit önleme)
        header("Location: arama_yonetimi.php?success=" . urlencode($message));
        exit;
        
    } catch (Exception $e) {
        $message = "❌ Hata: " . $e->getMessage();
        header("Location: arama_yonetimi.php?error=" . urlencode($e->getMessage()));
        exit;
    }
}

// URL'den mesaj al (redirect sonrası)
if (isset($_GET['success'])) {
    $message = $_GET['success'];
    $messageType = 'success';
} elseif (isset($_GET['error'])) {
    $message = "❌ Hata: " . $_GET['error'];
    $messageType = 'error';
}

// Tek dosya için aramaları durdurma
if (isset($_POST['stop_import'])) {
    requirePermission('import.control');
    try {
        $importId = $_POST['import_id'];
        
        // Import bilgisini al
        $importInfo = $pdo->prepare("SELECT OriginalFileName FROM " . table('IMPORT_HISTORY') . " WHERE id = ?");
        $importInfo->execute([$importId]);
        $importData = $importInfo->fetch();
        
        $count = $autoCallManager->stopImportCalls($importId);
        
        // Log
        logActivity('control', 'import', "Arama durduruldu: {$importData['OriginalFileName']} ({$count} arama)", $importId);
        
        // POST sonrası redirect yap
        header("Location: arama_yonetimi.php?success=" . urlencode("⏹️ {$count} arama durduruldu!"));
        exit;
        
    } catch (Exception $e) {
        header("Location: arama_yonetimi.php?error=" . urlencode($e->getMessage()));
        exit;
    }
}

// Import çoğaltma (duplicate)
if (isset($_POST['duplicate_import'])) {
    requirePermission('import.create');
    try {
        $sourceImportId = $_POST['source_import_id'];
        $projectId = $_POST['dup_project_id'];
        $templateId = $_POST['dup_template_id'];
        $retryCount = (int)$_POST['dup_retry_count'];
        $retryInterval = (int)$_POST['dup_retry_interval'];
        $maxConcurrent = (int)$_POST['dup_max_concurrent'];
        
        // Arama zamanını al
        $scheduledTime = isset($_POST['dup_scheduled_time']) && !empty($_POST['dup_scheduled_time']) 
            ? $_POST['dup_scheduled_time'] 
            : date('Y-m-d H:i:s'); // ✅ BOŞ İSE ŞİMDİKİ ZAMAN (1 saat ekleme!)
        
        // Kaynak import'u al
        $sourceStmt = $pdo->prepare("SELECT * FROM " . table('IMPORT_HISTORY') . " WHERE id = ?");
        $sourceStmt->execute([$sourceImportId]);
        $sourceImport = $sourceStmt->fetch();
        
        if (!$sourceImport) {
            throw new Exception("Kaynak dosya bulunamadı!");
        }
        
        // Proje adını al
        $projectStmt = $pdo->prepare("SELECT ProjectName FROM " . table('PROJECTS') . " WHERE id = ?");
        $projectStmt->execute([$projectId]);
        $projectName = $projectStmt->fetchColumn();
        
        // Yeni dosya adı oluştur: YYYY-MM-DD-HHMM-ProjeAdı.xlsx (max 30 karakter)
        $timestamp = date('Y-m-d-Hi');
        $shortName = substr(preg_replace('/[^a-zA-Z0-9]/', '', $projectName), 0, 10);
        $newFileName = $timestamp . '-' . $shortName;
        
        // Eğer çok uzunsa kısalt
        if (strlen($newFileName) > 24) {
            $newFileName = substr($newFileName, 0, 24);
        }
        
        // Yeni import kaydı oluştur
        $newImportStmt = $pdo->prepare("
            INSERT INTO IMPORT_HISTORY 
            (ProjectId, FileName, OriginalFileName, TemplateId, Status, ImportDate, RecordCount, RetryCount, RetryIntervalMinutes, MaxConcurrentCalls)
            VALUES (?, ?, ?, ?, 'pending', NOW(), 
                   (SELECT COUNT(*) FROM " . table('CALLS') . " WHERE ImportId = ?),
                   ?, ?, ?)
        ");
        $newImportStmt->execute([
            $projectId,
            $sourceImport['FileName'], // Aynı fiziksel dosyayı kullan
            $newFileName . '.xlsx',
            $templateId,
            $sourceImportId,
            $retryCount,
            $retryInterval,
            $maxConcurrent
        ]);
        $newImportId = $pdo->lastInsertId();
        
        // Eski kayıtları kopyala ve yeni import'a ata (Aktif = 0 başlar)
        // ✅ DUPLICATE KONTROLÜ: Aynı ImportId içinde aynı numara varsa ekleme!
        $copyStmt = $pdo->prepare("
            INSERT INTO CALLS 
            (PhoneNumber, Adi, Tutar, Metin, Metin1, Metin2, Metin3, 
             ProjectId, ImportId, Status, CallCount, TryCallCount, Aktif, Aramaadi, RecordDate, CallTime)
            SELECT PhoneNumber, Adi, Tutar, Metin, Metin1, Metin2, Metin3,
                   ?, ?, 1, 0, TryCallCount, 0, Aramaadi, NOW(), ?
            FROM " . table('CALLS') . "
            WHERE ImportId = ?
            AND PhoneNumber NOT IN (
                SELECT PhoneNumber FROM " . table('CALLS') . " WHERE ImportId = ?
            )
        ");
        $copyStmt->execute([$projectId, $newImportId, $scheduledTime, $sourceImportId, $newImportId]);
        
        $copiedCount = $copyStmt->rowCount();
        
        // Kaynak import'taki toplam kayıt sayısı
        $sourceCountStmt = $pdo->prepare("SELECT COUNT(*) FROM " . table('CALLS') . " WHERE ImportId = ?");
        $sourceCountStmt->execute([$sourceImportId]);
        $sourceCount = $sourceCountStmt->fetchColumn();
        
        $skippedCount = $sourceCount - $copiedCount;
        
        // Log
        $logMsg = "Import çoğaltıldı: {$sourceImport['OriginalFileName']} -> {$newFileName}.xlsx ({$copiedCount} kayıt)";
        if ($skippedCount > 0) {
            $logMsg .= " [{$skippedCount} duplicate atlandı]";
        }
        logActivity('create', 'import', $logMsg, $newImportId);
        
        // POST sonrası redirect yap
        $successMsg = "✅ {$copiedCount} kayıt çoğaltıldı! Yeni dosya: {$newFileName}.xlsx";
        if ($skippedCount > 0) {
            $successMsg .= " ({$skippedCount} tekrar eden numara atlandı)";
        }
        header("Location: arama_yonetimi.php?success=" . urlencode($successMsg));
        exit;
        
    } catch (Exception $e) {
        header("Location: arama_yonetimi.php?error=" . urlencode($e->getMessage()));
        exit;
    }
}

// Import dosyasını ve ilgili kayıtları silme
if (isset($_POST['delete_import'])) {
    requirePermission('import.delete');
    try {
        $importId = $_POST['import_id'];
        
        // Önce import bilgisini al
        $importInfo = $pdo->prepare("SELECT OriginalFileName FROM " . table('IMPORT_HISTORY') . " WHERE id = ?");
        $importInfo->execute([$importId]);
        $importData = $importInfo->fetch();
        
        // Önce ilgili call kayıtlarını say
        $countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM " . table('CALLS') . " WHERE ImportId = ?");
        $countStmt->execute([$importId]);
        $callCount = $countStmt->fetch()['total'];
        
        // Call kayıtlarını sil
        $deleteCallsStmt = $pdo->prepare("DELETE FROM " . table('CALLS') . " WHERE ImportId = ?");
        $deleteCallsStmt->execute([$importId]);
        
        // Import kaydını sil
        $deleteImportStmt = $pdo->prepare("DELETE FROM " . table('IMPORT_HISTORY') . " WHERE id = ?");
        $deleteImportStmt->execute([$importId]);
        
        // Log
        logActivity('delete', 'import', "Import silindi: {$importData['OriginalFileName']} ({$callCount} kayıt)", $importId);
        
        // POST sonrası redirect yap
        header("Location: arama_yonetimi.php?success=" . urlencode("🗑️ Dosya ve {$callCount} arama kaydı silindi!"));
        exit;
        
    } catch (Exception $e) {
        header("Location: arama_yonetimi.php?error=" . urlencode($e->getMessage()));
        exit;
    }
}

// Tüm import'ları ve proje listesini getir
$imports = $importManager->getImportHistory(null, 100);
$projects = $projectManager->getAllProjects();

// Şablon listesini getir
require_once 'includes/TemplateManager.php';
$templateManager = new TemplateManager($pdo);
$templates = $templateManager->getTemplates(true);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Arama Yönetimi</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 1400px; margin: 0 auto; }
        .header {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 30px;
        }
        h1 { color: #667eea; font-size: 32px; margin-bottom: 10px; }
        .subtitle { color: #666; font-size: 14px; }
        .nav-links { display: flex; gap: 15px; margin-top: 20px; flex-wrap: wrap; }
        .nav-links a {
            padding: 10px 20px;
            background: #f0f0f0;
            border-radius: 8px;
            text-decoration: none;
            color: #333;
            transition: all 0.3s;
            font-size: 14px;
        }
        .nav-links a:hover { background: #667eea; color: white; }
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            background: white;
            font-weight: 600;
        }
        .alert-success { border-left: 4px solid #28a745; color: #155724; }
        .alert-error { border-left: 4px solid #dc3545; color: #721c24; }
        .card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .card h2 {
            color: #667eea;
            font-size: 24px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e0e0e0;
        }
        .import-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 10px;
        }
        .import-table thead th {
            background: #f8f9fa;
            padding: 12px 8px;
            text-align: center;
            font-size: 12px;
            font-weight: 700;
            color: #333;
            border: none;
            white-space: nowrap;
        }
        .import-table tbody tr {
            background: #f8f9fa;
            transition: all 0.3s;
        }
        .import-table tbody tr:hover {
            background: #e9ecef;
            transform: translateX(5px);
        }
        .import-table tbody tr.running {
            background: #d1ecf1;
            border-left: 4px solid #17a2b8;
        }
        .import-table tbody td {
            padding: 15px 8px;
            border: none;
            vertical-align: middle;
            font-size: 13px;
        }
        .import-table tbody tr td:first-child {
            border-radius: 10px 0 0 10px;
        }
        .import-table tbody tr td:last-child {
            border-radius: 0 10px 10px 0;
        }
        .filename {
            font-weight: 600;
            color: #333;
            font-size: 12px;
            margin-bottom: 3px;
            line-height: 1.3;
            word-break: break-word;
        }
        .import-meta {
            font-size: 11px;
            color: #666;
        }
        .stats-mini {
            display: flex;
            gap: 10px;
            font-size: 12px;
        }
        .stat-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: 600;
        }
        .stat-pending { background: #fff3cd; color: #856404; }
        .stat-calling { background: #cfe2ff; color: #084298; }
        .stat-retry { background: #e2e3e5; color: #41464b; }
        .stat-completed { background: #d1e7dd; color: #0f5132; }
        .stat-failed { background: #f8d7da; color: #842029; }
        .form-control {
            width: 100%;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.3s;
        }
        .form-control:focus {
            outline: none;
            border-color: #667eea;
        }
        .input-number {
            width: 65px;
            text-align: center;
            font-weight: 600;
            font-size: 14px;
            padding: 6px;
        }
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            justify-content: flex-start;
            gap: 8px;
            text-decoration: none;
        }
        a.btn {
            text-decoration: none !important;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .btn-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
        }
        .btn-start {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }
        .btn-start:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
        }
        .btn-stop {
            background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%);
            color: white;
        }
        .btn-stop:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.4);
        }
        .btn-delete {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            color: white;
            width: 100%;
        }
        .btn-delete:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(108, 117, 125, 0.4);
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        }
        .btn-export {
            background: #28a745 !important;
            color: white !important;
            width: 100%;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-export:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
            background: #218838 !important;
        }
        .btn-export-link {
            background: #28a745 !important;
            color: white !important;
            text-decoration: none !important;
            display: block !important;
            pointer-events: auto !important;
        }
        .btn-export-link:hover {
            background: #218838 !important;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
        }
        .export-option:hover {
            border-color: #28a745 !important;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.2);
        }
        .export-menu a:hover {
            background: #f0f0f0;
            color: #28a745;
        }
        .btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-idle { background: #e9ecef; color: #495057; }
        .status-running { background: #d1ecf1; color: #0c5460; animation: pulse 2s infinite; }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        .empty-icon { font-size: 64px; margin-bottom: 20px; }
        .action-cell {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .progress-mini {
            font-size: 11px;
            color: #666;
            margin-top: 5px;
        }
        .status-line {
            margin-top: 8px !important;
            padding: 6px 10px !important;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%) !important;
            border-radius: 6px !important;
            font-size: 11px !important;
            font-weight: 600 !important;
            text-align: center !important;
            border-left: 3px solid #667eea !important;
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            min-height: 30px !important;
        }
        .status-line span {
            display: inline-block !important;
            padding: 2px 0 !important;
            white-space: nowrap !important;
        }
        .refresh-info {
            text-align: right;
            color: #666;
            font-size: 12px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <div>
                    <h1>📋 Arama Yönetimi</h1>
                    <p class="subtitle">Her dosya için proje atayın, tekrar sayısını belirleyin ve START/STOP ile kontrol edin</p>
                </div>
                <div style="display: flex; gap: 10px;">
                    <?php if (hasPermission('import.create')): ?>
                    <button class="btn btn-success" onclick="document.getElementById('importFile').click()" title="Excel dosyası yükle">
                        <i class="fas fa-upload"></i> Import
                    </button>
                    <input type="file" id="importFile" accept=".xlsx,.xls" style="display: none;">
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="nav-links">
                <a href="otomatikarama.php">🏠 Ana Sayfa</a>
                <a href="projeler.php">📁 Projeler</a>
                <a href="sablonlar.php">📝 Şablonlar</a>
            </div>
        </div>

        <?php if (isset($message)): ?>
        <div class="alert alert-<?= $messageType ?>">
            <?= $message ?>
        </div>
        <?php endif; ?>

        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2>📦 İçe Aktarılan Dosyalar</h2>
                <button onclick="refreshImportList()" class="btn" style="background: #17a2b8; padding: 8px 16px; font-size: 14px;" title="Listeyi yenile">
                    <i class="fas fa-sync-alt" id="refreshIcon"></i> Yenile
                </button>
            </div>
            
            <div id="importListContainer">

            <?php if (empty($imports)): ?>
            <div class="empty-state">
                <div class="empty-icon">📁</div>
                <h3>Henüz içe aktarım yok</h3>
                <p><a href="import.php" style="color: #667eea;">Veri İçe Aktar</a> sayfasından başlayın</p>
            </div>
            <?php else: ?>
            
            <!-- TOPLAM ÖZET - Aradı/Aranacak -->
            <?php
            // Tüm import'lar için toplam istatistikler
            $totalAradi = 0;
            $totalAranacak = 0;
            $totalAll = 0;
            
            foreach ($imports as $imp) {
                try {
                    $statsStmt = $pdo->prepare("
                        SELECT 
                            COUNT(*) as total,
                            SUM(CASE WHEN Status != 2 THEN 1 ELSE 0 END) as pending,
                            SUM(CASE WHEN Status = 2 THEN 1 ELSE 0 END) as completed
                        FROM " . table('CALLS') . " 
                        WHERE ImportId = ?
                    ");
                    $statsStmt->execute([$imp['id']]);
                    $st = $statsStmt->fetch();
                    
                    if ($st) {
                        $totalAll += (int)$st['total'];
                        $totalAranacak += (int)($st['pending'] ?? 0);
                        $totalAradi += (int)($st['completed'] ?? 0);
                    }
                } catch (Exception $e) {
                    // Hata olursa devam et
                }
            }
            ?>
            
            <?php if ($totalAll > 0): ?>
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 15px 20px; border-radius: 10px; margin-bottom: 20px; display: flex; justify-content: space-around; align-items: center; box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
                <div style="text-align: center; color: white;">
                    <div style="font-size: 28px; font-weight: bold;"><?= $totalAradi ?></div>
                    <div style="font-size: 13px; opacity: 0.9;">✓ ARADI</div>
                </div>
                <div style="width: 3px; height: 50px; background: rgba(255,255,255,0.4);"></div>
                <div style="text-align: center; color: white;">
                    <div style="font-size: 28px; font-weight: bold;"><?= $totalAranacak ?></div>
                    <div style="font-size: 13px; opacity: 0.9;">⏳ ARANACAK</div>
                </div>
                <div style="width: 3px; height: 50px; background: rgba(255,255,255,0.4);"></div>
                <div style="text-align: center; color: white;">
                    <div style="font-size: 28px; font-weight: bold;"><?= $totalAll ?></div>
                    <div style="font-size: 13px; opacity: 0.9;">📊 TOPLAM</div>
                </div>
            </div>
            <?php endif; ?>
            
            <table class="import-table">
                <thead>
                    <tr>
                        <th style="width: 9%;">📅 Saat</th>
                        <th style="width: 22%;">📄 Dosya Adı</th>
                        <th style="width: 16%;">📁 Proje / 📄 Şablon</th>
                        <th style="width: 5%;" title="Ulaşılamazsa kaç kez daha aranacak">🔄 Tekrar</th>
                        <th style="width: 7%;" title="Arama partileri arasında kaç saniye beklenecek">⏱️ Aralık</th>
                        <th style="width: 6%;" title="Her partide kaç kişi aranacak">⚡ Hız</th>
                        <th style="width: 6%;" title="Aramayı başlat veya durdur">🎛️ Kontrol</th>
                        <th style="width: 16%;">📊 İstatistikler</th>
                        <th style="width: 17%;">⚙️ İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    // ON/OFF konumu IMPORT_HISTORY.IsActive alanında kalıcıdır.
                    ?>
                    <?php foreach ($imports as $import): ?>
                        <?php 
                        try {
                            $stats = $importManager->getImportCallStats($import['id']);
                        } catch (Exception $e) {
                            error_log("getImportCallStats error: " . $e->getMessage());
                            $stats = ['pending' => 0, 'calling' => 0, 'retry_waiting' => 0, 'completed' => 0, 'failed' => 0];
                        }
                        $isRunning = $stats['calling'] > 0;
                        $hasActive = $stats['pending'] > 0 || $stats['calling'] > 0;
                        $isActive = !empty($import['IsActive']);
                        ?>
                        <tr class="<?= $isRunning ? 'running' : '' ?>" data-import-id="<?= $import['id'] ?>">
                            <!-- Saat -->
                            <td style="text-align: center;">
                                <div style="font-size: 12px; font-weight: 600;">
                                    <?= date('d.m.Y', strtotime($import['ImportDate'])) ?><br>
                                    <?= date('H:i', strtotime($import['ImportDate'])) ?>
                                </div>
                            </td>

                            <!-- Dosya Adı -->
                            <td>
                                <?php
                                $fileName = $import['OriginalFileName'];
                                $displayName = mb_strlen($fileName) > 40 
                                    ? mb_substr($fileName, 0, 40) . '...' 
                                    : $fileName;
                                ?>
                                <div class="filename" title="<?= htmlspecialchars($fileName) ?>">
                                    <?= htmlspecialchars($displayName) ?>
                                </div>
                                <div class="import-meta">
                                    📊 <?= $import['RecordCount'] ?> kayıt
                                </div>
                            </td>

                            <!-- Proje Seçimi -->
                            <td>
                                <div style="display: flex; flex-direction: column; gap: 5px;">
                                    <!-- Proje (Sadece Görüntü) -->
                                    <select class="form-control" disabled style="font-size: 12px; padding: 4px; background: #f5f5f5; cursor: not-allowed;">
                                        <option>📁 <?= htmlspecialchars($import['ProjectName'] ?? 'Proje Yok') ?></option>
                                    </select>
                                    
                                    <!-- Şablon (Sadece Görüntü) -->
                                    <select class="form-control" disabled style="font-size: 12px; padding: 4px; background: #f5f5f5; cursor: not-allowed;">
                                        <option>📄 <?= htmlspecialchars($import['TemplateName'] ?? 'Şablon Yok') ?></option>
                                    </select>
                                </div>
                                
                                <!-- Hidden inputs for form submission -->
                                <input type="hidden" id="project_id_<?= $import['id'] ?>" value="<?= $import['ProjectId'] ?>">
                                <input type="hidden" id="template_id_<?= $import['id'] ?>" value="<?= $import['TemplateId'] ?>">
                            </td>

                            <!-- Tekrar Sayısı -->
                            <td style="text-align: center;">
                                <input type="number" 
                                       class="form-control input-number" 
                                       id="retry_count_<?= $import['id'] ?>"
                                       value="<?= $import['RetryCount'] ?? 3 ?>"
                                       min="1" 
                                       max="10"
                                       title="Ulaşılamazsa kaç kez daha aranacak">
                            </td>

                            <!-- Aralık (Dakika) -->
                            <td style="text-align: center;">
                                <input type="number" 
                                       class="form-control input-number" 
                                       id="retry_interval_<?= $import['id'] ?>"
                                       value="<?= $import['RetryIntervalMinutes'] ?? 120 ?>"
                                       min="5" 
                                       max="1440"
                                       title="Arama partileri arasında kaç saniye beklenecek">
                            </td>

                            <!-- Parti büyüklüğü (Hız) + Kaydet -->
                            <td style="text-align: center;">
                                <div style="display: flex; flex-direction: column; gap: 4px; align-items: center;">
                                    <input type="number" 
                                           class="form-control input-number" 
                                           id="max_concurrent_<?= $import['id'] ?>"
                                           value="<?= $import['MaxConcurrentCalls'] ?? 10 ?>"
                                           min="1" 
                                           max="100"
                                           title="Her partide kaç kişi aranacak">
                                    <?php if (hasPermission('import.control')): ?>
                                    <button type="button" class="btn-save-settings" onclick="saveImportSettings(<?= $import['id'] ?>)" 
                                            title="Tekrar, Aralık ve Hız değerlerini kaydet"
                                            style="font-size: 10px; padding: 4px 8px; background: #17a2b8; color: white; border: none; border-radius: 4px; cursor: pointer;">
                                        💾 Kaydet
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>

                            <!-- Tek ON/OFF anahtarı -->
                            <td style="text-align: center;">
                                <?php if (hasPermission('import.toggle_active') || hasPermission('import.control')): ?>
                                <button type="button"
                                        id="import_switch_<?= $import['id'] ?>"
                                        data-active="<?= $isActive ? 1 : 0 ?>"
                                        onclick="toggleImportSwitch(<?= $import['id'] ?>, this.dataset.active === '1')"
                                        style="position:relative;width:96px;height:38px;padding:0;border:0;border-radius:20px;cursor:pointer;transition:.25s;background:<?= $isActive ? '#28a745' : '#dc3545' ?>;">
                                    <span style="position:absolute;top:3px;left:<?= $isActive ? '51px' : '3px' ?>;width:42px;height:32px;line-height:32px;border-radius:17px;background:#fff;color:<?= $isActive ? '#28a745' : '#dc3545' ?>;font-size:11px;font-weight:800;transition:.25s;box-shadow:0 2px 5px rgba(0,0,0,.25);">
                                        <?= $isActive ? 'ON' : 'OFF' ?>
                                    </span>
                                </button>
                                <?php endif; ?>
                            </td>

                            <!-- İstatistikler -->
                            <td>
                                <?php 
                                // Değerleri hesapla
                                $aradi = isset($stats['completed']) ? (int)$stats['completed'] : 0;
                                $araniyor = isset($stats['calling']) ? (int)$stats['calling'] : 0;
                                $aranacak = isset($stats['pending']) ? (int)$stats['pending'] : 0;
                                $toplam = isset($stats['total']) ? (int)$stats['total'] : 0;
                                ?>
                                
                                <!-- Aradı-Aranacak SATIR -->
                                <div style="background:#f8f9fa;padding:5px 10px;border-radius:5px;margin-bottom:8px;font-size:11px;font-weight:bold;text-align:center;border-left:3px solid #667eea;">
                                    <span style="color:#28a745;">✓ Aradı: <?= $aradi ?></span> | 
                                    <span style="color:#ffc107;">⏳ Aranacak: <?= $aranacak ?></span>
                                </div>
                                
                                <div class="stats-mini" id="stats_<?= $import['id'] ?>">
                                    <?php if ($stats['pending'] > 0): ?>
                                    <span class="stat-badge stat-pending">🟡 <?= $stats['pending'] ?></span>
                                    <?php endif; ?>
                                    <?php if ($stats['calling'] > 0): ?>
                                    <span class="stat-badge stat-calling">🔵 <?= $stats['calling'] ?></span>
                                    <?php endif; ?>
                                    <?php if (isset($stats['retry_waiting']) && $stats['retry_waiting'] > 0): ?>
                                    <span class="stat-badge stat-retry">⏳ <?= $stats['retry_waiting'] ?></span>
                                    <?php endif; ?>
                                    <?php if ($stats['completed'] > 0): ?>
                                    <span class="stat-badge stat-completed">✅ <?= $stats['completed'] ?></span>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($stats['total'] > 0): ?>
                                <div class="progress-mini" id="progress_<?= $import['id'] ?>">
                                    <?php 
                                    $percentage = round($stats['completed'] / $stats['total'] * 100, 1);
                                    ?>
                                    <?= $percentage ?>% başarılı
                                </div>
                                <?php endif; ?>
                            </td>

                            <!-- İşlem Butonları -->
                            <td>
                                <?php if ($isRunning || $hasActive): ?>
                                    <!-- Arama kontrolü yalnızca ON/OFF anahtarıyla yapılır -->
                                    <div style="display: flex; flex-direction: column; gap: 10px; align-items: flex-start;">
                                        <!-- YENİDEN BAŞLAT butonu gizlendi -->
                                        <?php if (false): // GİZLİ ?>
                                        <form method="post" style="margin: 0; width: auto; display: none;" data-import-id="<?= $import['id'] ?>">
                                            <input type="hidden" name="import_id" value="<?= $import['id'] ?>">
                                            <input type="hidden" name="project_id" id="project_id_restart_<?= $import['id'] ?>" value="<?= $import['ProjectId'] ?>">
                                            <input type="hidden" name="template_id" id="template_id_restart_<?= $import['id'] ?>" value="<?= $import['TemplateId'] ?>">
                                            <input type="hidden" name="retry_count" id="retry_count_restart_<?= $import['id'] ?>" value="<?= $import['RetryCount'] ?? 3 ?>">
                                            <input type="hidden" name="retry_interval" id="retry_interval_restart_<?= $import['id'] ?>" value="<?= $import['RetryIntervalMinutes'] ?? 120 ?>">
                                            <input type="hidden" name="max_concurrent" id="max_concurrent_restart_<?= $import['id'] ?>" value="<?= $import['MaxConcurrentCalls'] ?? 10 ?>">
                                            <input type="hidden" name="restart" value="1">
                                            <input type="hidden" name="start_import" value="1">
                                            <button type="button" class="btn btn-start"
                                                    onclick="restartImport(event, <?= $import['id'] ?>, <?= $stats['active'] ?? 0 ?>, <?= $stats['total'] ?>)"
                                                    style="background: linear-gradient(135deg, #fd7e14 0%, #dc3545 100%); width: auto;">
                                                🔄 YENİDEN BAŞLAT
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                        <?php if (hasPermission('report.view')): ?>
                                        <button class="btn btn-report" data-import-id="<?= $import['id'] ?>" data-file-name="<?= htmlspecialchars($import['OriginalFileName']) ?>" style="background: #17a2b8; color: white; width: auto;">
                                            📊 RAPOR
                                        </button>
                                        <button class="btn" 
                                                onclick="window.open('export_filter.php?import_id=<?= $import['id'] ?>&filter=all', '_blank')"
                                                style="background: #28a745 !important; color: white !important; width: auto !important; display: inline-block !important; text-align: left !important; padding-left: 12px !important;">
                                            📥 EXPORT
                                        </button>
                                        <?php endif; ?>
                                        <?php if (hasPermission('import.create')): ?>
                                        <button class="btn btn-duplicate" data-import-id="<?= $import['id'] ?>" data-file-name="<?= htmlspecialchars($import['OriginalFileName']) ?>" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; width: auto;">
                                            📋 ÇOĞALT
                                        </button>
                                        <?php endif; ?>
                                        <?php if (hasPermission('import.delete')): ?>
                                        <form method="post" class="form-delete-import" data-file-name="<?= htmlspecialchars($import['OriginalFileName'], ENT_QUOTES, 'UTF-8') ?>" data-total="<?= (int)($stats['total'] ?? 0) ?>" style="margin: 0; width: auto;">
                                            <input type="hidden" name="import_id" value="<?= $import['id'] ?>">
                                            <input type="hidden" name="delete_import" value="1">
                                            <button type="button" class="btn btn-delete btn-delete-import" style="width: auto;">
                                                🗑️ SİL
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                    <span class="status-badge status-running" style="margin-top: 5px;">🔄 Çalışıyor</span>
                                <?php elseif (!$hasActive && $stats['total'] > 0): ?>
                                    <!-- Tamamlanmış - Yeniden Başlat -->
                                    <div style="display: flex; flex-direction: column; gap: 10px;">
                                        <!-- YENİDEN BAŞLAT butonu gizlendi -->
                                        <?php if (false && hasPermission('import.control')): // GİZLİ ?>
                                        <form method="post" style="margin: 0; display: none;" data-import-id="<?= $import['id'] ?>">
                                            <input type="hidden" name="import_id" value="<?= $import['id'] ?>">
                                            <input type="hidden" name="project_id" id="project_id_<?= $import['id'] ?>" value="<?= $import['ProjectId'] ?>">
                                            <input type="hidden" name="template_id" id="template_id_hidden_<?= $import['id'] ?>" value="<?= $import['TemplateId'] ?>">
                                            <input type="hidden" name="retry_count" id="retry_count_hidden_<?= $import['id'] ?>" value="3">
                                            <input type="hidden" name="retry_interval" id="retry_interval_hidden_<?= $import['id'] ?>" value="120">
                                            <input type="hidden" name="max_concurrent" id="max_concurrent_hidden_<?= $import['id'] ?>" value="<?= $import['MaxConcurrentCalls'] ?? 10 ?>">
                                            <input type="hidden" name="restart" value="1">
                                            <button type="button" name="start_import" class="btn btn-start"
                                                    onclick="restartImport(event, <?= $import['id'] ?>, <?= $stats['active'] ?? 0 ?>, <?= $stats['total'] ?>)"
                                                    style="background: linear-gradient(135deg, #fd7e14 0%, #dc3545 100%);">
                                                🔄 YENİDEN BAŞLAT
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                        <?php if (hasPermission('report.view')): ?>
                                        <button class="btn btn-report" data-import-id="<?= $import['id'] ?>" data-file-name="<?= htmlspecialchars($import['OriginalFileName']) ?>" style="background: #17a2b8; color: white; width: 100%;">
                                            📊 RAPOR
                                        </button>
                                        <a href="export_filter.php?import_id=<?= $import['id'] ?>&filter=all" 
                                           target="_blank"
                                           class="btn-export-link"
                                           style="background: #28a745 !important; color: white !important; width: 100% !important; padding: 10px 20px !important; border-radius: 8px !important; font-size: 14px !important; font-weight: 600 !important; text-decoration: none !important; display: block !important; text-align: center !important; position: relative !important; z-index: 10 !important; cursor: pointer !important;">
                                            📥 EXPORT
                                        </a>
                                        <?php endif; ?>
                                        <?php if (hasPermission('import.create')): ?>
                                        <button class="btn btn-duplicate" data-import-id="<?= $import['id'] ?>" data-file-name="<?= htmlspecialchars($import['OriginalFileName']) ?>" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; width: 100%;">
                                            📋 ÇOĞALT
                                        </button>
                                        <?php endif; ?>
                                        <?php if (hasPermission('import.delete')): ?>
                                        <form method="post" class="form-delete-import" data-file-name="<?= htmlspecialchars($import['OriginalFileName'], ENT_QUOTES, 'UTF-8') ?>" data-total="<?= (int)($stats['total'] ?? 0) ?>" style="margin: 0;">
                                            <input type="hidden" name="import_id" value="<?= $import['id'] ?>">
                                            <input type="hidden" name="delete_import" value="1">
                                            <button type="button" class="btn btn-delete btn-delete-import">🗑️ SİL</button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                    <span class="status-badge status-idle" style="margin-top: 5px;">✅ Tamamlandı</span>
                                <?php elseif (!$hasActive): ?>
                                    <div style="display: flex; flex-direction: column; gap: 10px;">
                                        <?php if (hasPermission('report.view')): ?>
                                        <button class="btn btn-report" data-import-id="<?= $import['id'] ?>" data-file-name="<?= htmlspecialchars($import['OriginalFileName']) ?>" style="background: #17a2b8; color: white; width: 100%;">
                                            📊 RAPOR
                                        </button>
                                        <a href="export_filter.php?import_id=<?= $import['id'] ?>&filter=all" 
                                           target="_blank"
                                           class="btn-export-link"
                                           style="background: #28a745 !important; color: white !important; width: 100% !important; padding: 10px 20px !important; border-radius: 8px !important; font-size: 14px !important; font-weight: 600 !important; text-decoration: none !important; display: block !important; text-align: center !important; position: relative !important; z-index: 10 !important; cursor: pointer !important;">
                                            📥 EXPORT
                                        </a>
                                        <?php endif; ?>
                                        <?php if (hasPermission('import.create')): ?>
                                        <button class="btn btn-duplicate" data-import-id="<?= $import['id'] ?>" data-file-name="<?= htmlspecialchars($import['OriginalFileName']) ?>" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; width: 100%;">
                                            📋 ÇOĞALT
                                        </button>
                                        <?php endif; ?>
                                        <?php if (hasPermission('import.delete')): ?>
                                        <form method="post" class="form-delete-import" data-file-name="<?= htmlspecialchars($import['OriginalFileName'], ENT_QUOTES, 'UTF-8') ?>" data-total="<?= (int)($stats['total'] ?? 0) ?>" style="margin: 0;">
                                            <input type="hidden" name="import_id" value="<?= $import['id'] ?>">
                                            <input type="hidden" name="delete_import" value="1">
                                            <button type="button" class="btn btn-delete btn-delete-import">🗑️ SİL</button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                    <span class="status-badge status-idle">✅ Tamamlandı</span>
                                <?php else: ?>
                                    <div style="display: flex; flex-direction: column; gap: 10px;">
                                        <?php if (hasPermission('import.start')): ?>
                                        <form method="post" style="margin: 0;">
                                            <input type="hidden" name="import_id" value="<?= $import['id'] ?>">
                                            <input type="hidden" name="project_id" id="project_id_<?= $import['id'] ?>" value="<?= $import['ProjectId'] ?>">
                                            <input type="hidden" name="retry_count" id="retry_count_hidden_<?= $import['id'] ?>" value="3">
                                            <input type="hidden" name="retry_interval" id="retry_interval_hidden_<?= $import['id'] ?>" value="120">
                                            <input type="hidden" name="max_concurrent" id="max_concurrent_hidden_<?= $import['id'] ?>" value="<?= $import['MaxConcurrentCalls'] ?? 10 ?>">
                                            <button type="submit" name="start_import" class="btn btn-start"
                                                    onclick="return startImport(<?= $import['id'] ?>)">
                                                ▶️ START (TÜM)
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                        <button class="btn btn-report" data-import-id="<?= $import['id'] ?>" data-file-name="<?= htmlspecialchars($import['OriginalFileName']) ?>" style="background: #17a2b8; color: white; width: 100%;">
                                            📊 RAPOR
                                        </button>
                                        <button class="btn btn-duplicate" data-import-id="<?= $import['id'] ?>" data-file-name="<?= htmlspecialchars($import['OriginalFileName']) ?>" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; width: 100%;">
                                            📋 ÇOĞALT
                                        </button>
                                        <form method="post" class="form-delete-import" data-file-name="<?= htmlspecialchars($import['OriginalFileName'], ENT_QUOTES, 'UTF-8') ?>" data-total="<?= (int)($stats['total'] ?? 0) ?>" style="margin: 0;">
                                            <input type="hidden" name="import_id" value="<?= $import['id'] ?>">
                                            <input type="hidden" name="delete_import" value="1">
                                            <button type="button" class="btn btn-delete btn-delete-import">🗑️ SİL</button>
                                        </form>
                                    </div>
                                    <div style="font-size: 11px; color: #666; margin-top: 5px; text-align: center;">
                                        <?= $stats['pending'] ?> kayıt başlatılacak
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="refresh-info">
                🔄 Sayfa otomatik yenilenecek... <?php if ($isRunning): echo '(5 saniye)'; endif; ?>
            </div>
            <?php endif; ?>
            </div> <!-- importListContainer kapanış -->
        </div>
    </div>

    <!-- Rapor Modal -->
    <div id="reportModal" style="display: none !important; position: fixed !important; top: 0 !important; left: 0 !important; width: 100% !important; height: 100% !important; background: rgba(0,0,0,0.7) !important; z-index: 999999 !important; overflow-y: auto !important; padding: 20px !important;">
        <div style="background: white; max-width: 1400px; margin: 0 auto; border-radius: 15px; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; border-radius: 15px 15px 0 0; color: white; display: flex; justify-content: space-between; align-items: center;">
                <h2 style="margin: 0; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-chart-bar"></i> <span id="reportFileName">Rapor</span>
                </h2>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <button id="exportReportBtn" onclick="exportReport()" style="background: rgba(255,255,255,0.9); border: none; color: #667eea; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 8px; transition: all 0.3s;" onmouseover="this.style.background='white'" onmouseout="this.style.background='rgba(255,255,255,0.9)'">
                        <i class="fas fa-download"></i> Export Excel
                    </button>
                    <button onclick="closeReportModal()" style="background: rgba(255,255,255,0.2); border: none; color: white; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; font-size: 20px;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            
            <!-- Özet İstatistikler -->
            <div id="reportSummary" style="padding: 30px; display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 20px;">
                <!-- Stats will be loaded here -->
            </div>
            
            <!-- Detaylı Tablo -->
            <div style="padding: 0 30px 30px 30px;">
                <h3 style="color: #667eea; margin-bottom: 15px;">📋 Detaylı Kayıtlar</h3>
                <div style="overflow-x: auto; max-height: 500px; overflow-y: auto; border: 1px solid #e0e0e0; border-radius: 8px;">
                    <table id="reportTable" style="width: 100%; border-collapse: collapse;">
                        <thead style="position: sticky; top: 0; background: #667eea; color: white; z-index: 1;">
                            <tr>
                                <th style="padding: 12px; text-align: left; font-size: 13px;">Telefon</th>
                                <th style="padding: 12px; text-align: left; font-size: 13px;">Ad Soyad</th>
                                <th style="padding: 12px; text-align: center; font-size: 13px;">Aranma</th>
                                <th style="padding: 12px; text-align: center; font-size: 13px;">Sonuç</th>
                                <th style="padding: 12px; text-align: center; font-size: 13px;">Zamanlanmış Saat</th>
                                <th style="padding: 12px; text-align: left; font-size: 13px;">Durum Detayı</th>
                                <th style="padding: 12px; text-align: left; font-size: 13px;">Son Arama</th>
                                <th style="padding: 12px; text-align: left; font-size: 13px;">Tuş</th>
                            </tr>
                        </thead>
                        <tbody id="reportTableBody">
                            <!-- Data will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Export Modal -->
    <div id="exportModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 999999; align-items: center; justify-content: center;">
        <div style="background: white; max-width: 600px; width: 90%; border-radius: 15px; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
            <div style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); padding: 20px; border-radius: 15px 15px 0 0; color: white; display: flex; justify-content: space-between; align-items: center;">
                <h2 style="margin: 0; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-file-download"></i> Excel Export
                </h2>
                <button onclick="closeExportModal()" style="background: rgba(255,255,255,0.2); border: none; color: white; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; font-size: 20px;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div style="padding: 30px;">
                <h3 style="color: #333; margin-bottom: 20px;">📁 <span id="exportFileName">Dosya Adı</span></h3>
                <p style="color: #666; margin-bottom: 30px;">Hangi kayıtları export etmek istiyorsunuz?</p>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <a href="#" onclick="exportData('all'); return false;" class="export-option" style="padding: 20px; border: 2px solid #e0e0e0; border-radius: 10px; text-decoration: none; color: #333; text-align: center; transition: all 0.3s;">
                        <i class="fas fa-list" style="font-size: 32px; color: #667eea; margin-bottom: 10px;"></i>
                        <div style="font-weight: 600; font-size: 16px;">📊 Tüm Kayıtlar</div>
                        <div style="font-size: 12px; color: #999; margin-top: 5px;">Hepsini indir</div>
                    </a>
                    
                    <a href="#" onclick="exportData('aktif'); return false;" class="export-option" style="padding: 20px; border: 2px solid #e0e0e0; border-radius: 10px; text-decoration: none; color: #333; text-align: center; transition: all 0.3s;">
                        <i class="fas fa-check-circle" style="font-size: 32px; color: #28a745; margin-bottom: 10px;"></i>
                        <div style="font-weight: 600; font-size: 16px;">✅ Aktif Kayıtlar</div>
                        <div style="font-size: 12px; color: #999; margin-top: 5px;">Sadece aktif olanlar</div>
                    </a>
                    
                    <a href="#" onclick="exportData('pasif'); return false;" class="export-option" style="padding: 20px; border: 2px solid #e0e0e0; border-radius: 10px; text-decoration: none; color: #333; text-align: center; transition: all 0.3s;">
                        <i class="fas fa-pause-circle" style="font-size: 32px; color: #6c757d; margin-bottom: 10px;"></i>
                        <div style="font-weight: 600; font-size: 16px;">⏸️ Pasif Kayıtlar</div>
                        <div style="font-size: 12px; color: #999; margin-top: 5px;">Sadece pasif olanlar</div>
                    </a>
                    
                    <a href="#" onclick="exportData('basarili'); return false;" class="export-option" style="padding: 20px; border: 2px solid #e0e0e0; border-radius: 10px; text-decoration: none; color: #333; text-align: center; transition: all 0.3s;">
                        <i class="fas fa-check" style="font-size: 32px; color: #28a745; margin-bottom: 10px;"></i>
                        <div style="font-weight: 600; font-size: 16px;">✔️ Başarılı</div>
                        <div style="font-size: 12px; color: #999; margin-top: 5px;">Arama başarılı olanlar</div>
                    </a>
                    
                    <a href="#" onclick="exportData('basarisiz'); return false;" class="export-option" style="padding: 20px; border: 2px solid #e0e0e0; border-radius: 10px; text-decoration: none; color: #333; text-align: center; transition: all 0.3s;">
                        <i class="fas fa-times" style="font-size: 32px; color: #dc3545; margin-bottom: 10px;"></i>
                        <div style="font-weight: 600; font-size: 16px;">❌ Başarısız</div>
                        <div style="font-size: 12px; color: #999; margin-top: 5px;">Cevap yok, meşgul</div>
                    </a>
                    
                    <a href="#" onclick="exportData('bekleyen'); return false;" class="export-option" style="padding: 20px; border: 2px solid #e0e0e0; border-radius: 10px; text-decoration: none; color: #333; text-align: center; transition: all 0.3s;">
                        <i class="fas fa-clock" style="font-size: 32px; color: #ffc107; margin-bottom: 10px;"></i>
                        <div style="font-weight: 600; font-size: 16px;">⏳ Bekleyen</div>
                        <div style="font-size: 12px; color: #999; margin-top: 5px;">Henüz aranmadı</div>
                    </a>
                    
                    <a href="#" onclick="exportData('aranan'); return false;" class="export-option" style="padding: 20px; border: 2px solid #e0e0e0; border-radius: 10px; text-decoration: none; color: #333; text-align: center; transition: all 0.3s;">
                        <i class="fas fa-phone" style="font-size: 32px; color: #17a2b8; margin-bottom: 10px;"></i>
                        <div style="font-weight: 600; font-size: 16px;">📞 Aranan</div>
                        <div style="font-size: 12px; color: #999; margin-top: 5px;">En az 1 kez arandı</div>
                    </a>
                    
                    <a href="#" onclick="exportData('aranmayan'); return false;" class="export-option" style="padding: 20px; border: 2px solid #e0e0e0; border-radius: 10px; text-decoration: none; color: #333; text-align: center; transition: all 0.3s;">
                        <i class="fas fa-phone-slash" style="font-size: 32px; color: #6c757d; margin-bottom: 10px;"></i>
                        <div style="font-weight: 600; font-size: 16px;">🚫 Aranmayan</div>
                        <div style="font-size: 12px; color: #999; margin-top: 5px;">Hiç aranmadı</div>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Duplicate Modal -->
    <div id="duplicateModal" style="display: none !important; position: fixed !important; top: 0 !important; left: 0 !important; width: 100% !important; height: 100% !important; background: rgba(0,0,0,0.7) !important; z-index: 999999 !important; align-items: center !important; justify-content: center !important;">
        <div style="background: white; padding: 30px; border-radius: 15px; max-width: 500px; width: 90%; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
            <h2 style="color: #28a745; margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-copy"></i> Kayıt Çoğalt
            </h2>
            
            <form method="post" action="" id="duplicateForm">
                <input type="hidden" name="duplicate_import" value="1">
                <input type="hidden" name="source_import_id" id="source_import_id" value="">
                
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; color: #555; font-weight: 600;">
                        <i class="fas fa-folder"></i> Proje Seçin:
                    </label>
                    <select name="dup_project_id" id="dup_project_id" class="form-control" required style="width: 100%; padding: 10px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px;">
                        <option value="">-- Proje Seçin --</option>
                        <?php foreach ($projects as $project): ?>
                            <option value="<?= $project['id'] ?>"><?= htmlspecialchars($project['ProjectName']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; color: #555; font-weight: 600;">
                        <i class="fas fa-file-alt"></i> Şablon Seçin:
                    </label>
                    <select name="dup_template_id" id="dup_template_id" class="form-control" required style="width: 100%; padding: 10px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px;">
                        <option value="">-- Şablon Seçin --</option>
                        <?php foreach ($templates as $template): ?>
                            <option value="<?= $template['id'] ?>"><?= htmlspecialchars($template['TemplateName']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div>
                        <label style="display: block; margin-bottom: 5px; color: #555; font-weight: 600;">
                            <i class="fas fa-redo"></i> Tekrar Sayısı:
                        </label>
                        <input type="number" name="dup_retry_count" id="dup_retry_count" value="3" min="1" max="10" class="form-control" required style="width: 100%; padding: 10px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 5px; color: #555; font-weight: 600;">
                            <i class="fas fa-clock"></i> Aralık (Dk):
                        </label>
                        <input type="number" name="dup_retry_interval" id="dup_retry_interval" value="60" min="5" max="1440" class="form-control" required style="width: 100%; padding: 10px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px;">
                    </div>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 5px; color: #555; font-weight: 600;">
                        <i class="fas fa-bolt"></i> Eşzamanlı Arama:
                    </label>
                    <input type="number" name="dup_max_concurrent" id="dup_max_concurrent" value="10" min="1" max="100" class="form-control" required style="width: 100%; padding: 10px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px;">
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 5px; color: #555; font-weight: 600;">
                        <i class="fas fa-clock"></i> Arama Zamanı:
                    </label>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <input type="datetime-local" name="dup_scheduled_time" id="dup_scheduled_time" style="flex: 1; padding: 10px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px;">
                        <button type="button" onclick="clearDuplicateScheduledTime()" style="padding: 10px 15px; background: #ff9800; color: white; border: none; border-radius: 8px; cursor: pointer; white-space: nowrap;">
                            <i class="fas fa-eraser"></i> Temizle
                        </button>
                    </div>
                    <small style="color: #666; display: block; margin-top: 5px; font-size: 12px;">
                        💡 <strong>Varsayılan:</strong> Şimdiki zaman ayarlanır | ⏰ <strong>Temizle</strong> → Hemen başlar
                    </small>
                </div>
                
                <div style="background: #e8f5e9; padding: 12px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #28a745; font-size: 13px; color: #2e7d32;">
                    <strong>ℹ️ Bilgi:</strong> Yeni dosya adı otomatik oluşturulacak:<br>
                    <code style="background: white; padding: 2px 6px; border-radius: 4px;">YYYY-MM-DD-HHMM-ProjeAdı.xlsx</code>
                </div>
                
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" onclick="closeDuplicateModal()" style="padding: 10px 20px; border: 2px solid #ddd; background: white; color: #666; border-radius: 8px; cursor: pointer; font-weight: 600;">
                        ✖️ İptal
                    </button>
                    <button type="submit" style="padding: 10px 20px; border: none; background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; border-radius: 8px; cursor: pointer; font-weight: 600;">
                        ✅ Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Import Modal -->
    <div id="importModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 9999; align-items: center; justify-content: center;">
        <div style="background: white; padding: 30px; border-radius: 15px; max-width: 600px; width: 90%; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
            <h2 style="color: #667eea; margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-upload"></i> Hızlı Import
            </h2>
            
            <div style="background: #e7f3ff; padding: 12px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #2196F3;">
                <strong>📋 Excel Formatı:</strong><br>
                <small style="color: #666;">Telefon | AdSoyad | Tutar | Metin1 | Metin2 | Metin3</small><br>
                <small style="color: #666;">Örnek: 05551234567 | Ahmet Yılmaz | 1500.50 | Bilgi1 | Bilgi2 | Bilgi3</small>
            </div>
            
            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <strong>Seçilen Dosya:</strong><br>
                <span id="selectedFileName" style="color: #667eea; font-size: 14px;"></span>
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">
                    <i class="fas fa-folder"></i> Proje Seçiniz *
                </label>
                <select id="import_project_id" style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 15px;">
                    <option value="">-- Proje Seçiniz --</option>
                    <?php foreach ($projects as $proj): ?>
                    <option value="<?= $proj['id'] ?>"><?= htmlspecialchars($proj['ProjectName']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">
                    <i class="fas fa-comment-dots"></i> Mesaj Şablonu (Opsiyonel)
                </label>
                <select id="import_template_id" style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 15px;">
                    <option value="">-- Varsayılan Şablon --</option>
                    <?php 
                    require_once 'includes/TemplateManager.php';
                    $templateManager = new TemplateManager($pdo);
                    $templates = $templateManager->getTemplates();
                    foreach ($templates as $tmpl): 
                    ?>
                    <option value="<?= $tmpl['id'] ?>"><?= htmlspecialchars($tmpl['TemplateName']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div style="margin-bottom: 25px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">
                    <i class="fas fa-clock"></i> Arama Zamanı
                </label>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <input type="datetime-local" id="import_scheduled_time" style="flex: 1; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 15px;">
                    <button type="button" onclick="clearScheduledTime()" style="padding: 12px 20px; background: #ff9800; color: white; border: none; border-radius: 8px; cursor: pointer; white-space: nowrap;">
                        <i class="fas fa-eraser"></i> Temizle
                    </button>
                </div>
                <small style="color: #666; display: block; margin-top: 5px;">
                    💡 <strong>Varsayılan:</strong> Şimdiki zaman ayarlanır<br>
                    ⏰ <strong>Temizle</strong> butonuna tıklayın → Hemen başlar
                </small>
            </div>
            
            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button onclick="closeImportModal()" class="btn" style="background: #6c757d; color: white;">
                    <i class="fas fa-times"></i> İptal
                </button>
                <button id="submitImportBtn" onclick="submitImport()" class="btn btn-success">
                    <i class="fas fa-check"></i> Import Et
                </button>
            </div>
        </div>
    </div>

    <!-- Silme Onay Modal -->
    <div id="deleteConfirmModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 999999; align-items: center; justify-content: center;">
        <div style="background: white; padding: 30px; border-radius: 15px; max-width: 450px; width: 90%; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
            <h2 style="color: #dc3545; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                🗑️ Silme Onayı
            </h2>
            <p style="margin-bottom: 20px; font-size: 15px; color: #333;" id="deleteConfirmMessage">
                Bu dosyayı silmek istediğinizden emin misiniz?
            </p>
            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" onclick="cancelDelete()" style="padding: 12px 25px; border: 2px solid #ddd; background: white; color: #666; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px;">
                    İPTAL
                </button>
                <button type="button" onclick="confirmDeleteSubmit()" style="padding: 12px 25px; border: none; background: #dc3545; color: white; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px;">
                    TAMAM - Sil
                </button>
            </div>
        </div>
    </div>

    <!-- Yeniden Başlat Onay Modal -->
    <div id="restartConfirmModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 999999; align-items: center; justify-content: center;">
        <div style="background: white; padding: 30px; border-radius: 15px; max-width: 500px; width: 90%; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
            <h2 style="color: #fd7e14; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-exclamation-triangle"></i> Yeniden Başlatma Onayı
            </h2>
            
            <div style="background: #fff3cd; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #ffc107;">
                <p style="margin: 0; color: #856404; font-size: 15px;" id="restartConfirmMessage">
                    Tüm kayıtları aramak istediğinizden emin misiniz?
                </p>
            </div>
            
            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" onclick="closeRestartConfirmModal()" style="padding: 12px 25px; border: 2px solid #ddd; background: white; color: #666; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px;">
                    <i class="fas fa-times"></i> İPTAL
                </button>
                <button type="button" onclick="confirmRestart()" style="padding: 12px 25px; border: none; background: linear-gradient(135deg, #fd7e14 0%, #dc3545 100%); color: white; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px;">
                    <i class="fas fa-check"></i> TAMAM
                </button>
            </div>
        </div>
    </div>

    <script>
        console.log('✅ SCRIPT STARTED LOADING');
        
        function saveProjectSelection(importId, projectId) {
            document.getElementById('project_id_' + importId).value = projectId;
            
            // AJAX ile sessizce kaydet
            fetch('save_project.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'import_id=' + importId + '&project_id=' + projectId
            });
        }
        
        function saveTemplateSelection(importId, templateId) {
            // Hidden inputları güncelle
            const restartInput = document.getElementById('template_id_restart_' + importId);
            const hiddenInput = document.getElementById('template_id_hidden_' + importId);
            
            if (restartInput) restartInput.value = templateId;
            if (hiddenInput) hiddenInput.value = templateId;
            
            // AJAX ile sessizce kaydet
            fetch('save_template.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'import_id=' + importId + '&template_id=' + templateId
            });
        }

        function startImport(importId) {
            const projectId = document.getElementById('project_id_' + importId).value;
            const retryCount = document.getElementById('retry_count_' + importId).value;
            const retryInterval = document.getElementById('retry_interval_' + importId).value;
            const maxConcurrent = document.getElementById('max_concurrent_' + importId).value;
            
            if (!projectId) {
                alert('❌ Lütfen önce proje seçin!');
                return false;
            }
            
            if (!retryCount || retryCount < 1 || retryCount > 10) {
                alert('❌ Tekrar sayısı 1-10 arasında olmalı!');
                return false;
            }
            
            if (!retryInterval || retryInterval < 5 || retryInterval > 1440) {
                alert('❌ Aralık 5-1440 saniye arasında olmalı!');
                return false;
            }
            
            if (!maxConcurrent || maxConcurrent < 1 || maxConcurrent > 100) {
                alert('❌ Parti büyüklüğü (Hız) 1-100 arasında olmalı!');
                return false;
            }
            
            // Hidden inputları güncelle
            document.getElementById('retry_count_hidden_' + importId).value = retryCount;
            document.getElementById('retry_interval_hidden_' + importId).value = retryInterval;
            document.getElementById('max_concurrent_hidden_' + importId).value = maxConcurrent;
            
            // Onay sor
            const pending = document.querySelector('#retry_count_' + importId).closest('tr').querySelector('.stat-pending');
            const count = pending ? pending.textContent.match(/\d+/)[0] : '?';
            
            return confirm(`${count} kayıt için arama başlatılacak.\n\nParti büyüklüğü (Hız): ${maxConcurrent} kişi\nParti aralığı: ${retryInterval} saniye\n\nDevam edilsin mi?`);
        }
        
        let deletePendingForm = null;
        
        function showDeleteModal(form, fileName, totalCount) {
            deletePendingForm = form;
            document.getElementById('deleteConfirmMessage').innerHTML = 
                'SİL: <strong>' + fileName + '</strong> - ' + totalCount + ' kayıt?<br><br>Bu dosyayı ve tüm arama kayıtlarını silmek istediğinizden emin misiniz?';
            document.getElementById('deleteConfirmModal').style.display = 'flex';
        }
        
        function confirmDeleteSubmit() {
            if (deletePendingForm) {
                deletePendingForm.submit();
                deletePendingForm = null;
            }
            document.getElementById('deleteConfirmModal').style.display = 'none';
        }
        
        function cancelDelete() {
            deletePendingForm = null;
            document.getElementById('deleteConfirmModal').style.display = 'none';
        }
        
        
        // Yeniden Başlat Modal İşlemleri
        let restartPendingImportId = null;
        let restartPendingForm = null;
        
        // AJAX refresh sonrası butonlar için showRestartModal fonksiyonu
        function showRestartModal(importId, fileName, completedCount, retryCount) {
            console.log('showRestartModal called:', importId, fileName, completedCount);
            const tryCount = retryCount || document.getElementById('retry_count_' + importId)?.value || 3;
            
            // Modal mesajını hazırla
            const message = `<strong>${fileName}</strong> dosyasını yeniden başlatmak istediğinizden emin misiniz?<br><br>` +
                          `<strong>• Tüm kayıtlar sıfırlanacak</strong><br>` +
                          `<strong>• ${completedCount} kayıt yeniden aranacak</strong>`;
            
            document.getElementById('restartConfirmMessage').innerHTML = message;
            
            // Modal'ı göster
            const modal = document.getElementById('restartConfirmModal');
            modal.style.display = 'flex';
            
            // Import ID'yi sakla (form yok çünkü AJAX refresh sonrası submit yapmayacağız)
            restartPendingImportId = importId;
            restartPendingForm = null; // Form submit değil, direkt POST yapacağız
            
            console.log('Modal açıldı, ImportId:', importId);
            
            return false;
        }
        
        function restartImport(event, importId, activeCount, totalCount) {
            console.log('restartImport called:', importId, 'Active:', activeCount, 'Total:', totalCount);
            
            // Event'i durdur
            if (event) {
                event.preventDefault();
            }
            
            // Tablodaki Tekrar, Aralık, Hız değerlerini form hidden inputlarına kopyala
            const retryCount = document.getElementById('retry_count_' + importId)?.value || document.getElementById('retry_count_restart_' + importId)?.value || 3;
            const retryInterval = document.getElementById('retry_interval_' + importId)?.value || document.getElementById('retry_interval_restart_' + importId)?.value || 120;
            const maxConcurrent = document.getElementById('max_concurrent_' + importId)?.value || document.getElementById('max_concurrent_restart_' + importId)?.value || 10;
            const retryRestart = document.getElementById('retry_count_restart_' + importId);
            const retryHidden = document.getElementById('retry_count_hidden_' + importId);
            const intervalRestart = document.getElementById('retry_interval_restart_' + importId);
            const intervalHidden = document.getElementById('retry_interval_hidden_' + importId);
            const maxRestart = document.getElementById('max_concurrent_restart_' + importId);
            const maxHidden = document.getElementById('max_concurrent_hidden_' + importId);
            if (retryRestart) retryRestart.value = retryCount;
            if (retryHidden) retryHidden.value = retryCount;
            if (intervalRestart) intervalRestart.value = retryInterval;
            if (intervalHidden) intervalHidden.value = retryInterval;
            if (maxRestart) maxRestart.value = maxConcurrent;
            if (maxHidden) maxHidden.value = maxConcurrent;
            
            // Aktif kayıt yoksa uyarı ver
            if (activeCount === 0) {
                alert('⚠️ Bu import\'ta AKTİF kayıt yok!\n\nÖnce kayıtları "Aktif" yapmalısınız.');
                return false;
            }
            
            // Proje ID kontrolü
            let projectIdInput = document.getElementById('project_id_' + importId);
            if (!projectIdInput) {
                projectIdInput = document.getElementById('project_id_restart_' + importId);
            }
            
            const projectId = projectIdInput ? projectIdInput.value : '';
            const visibleProjectSelect = document.querySelector(`#project_select_${importId}`);
            if (visibleProjectSelect && !projectId) {
                alert('❌ Lütfen önce proje seçin!');
                return false;
            }
            
            const pasifCount = totalCount - activeCount;
            
            // Modal mesajını hazırla
            const message = `Tüm kayıtları aramak istediğinizden emin misiniz?<br><br>` +
                          `<strong>• ${activeCount} AKTİF kayıt</strong> yeniden aranacak<br>` +
                          `<strong>• ${pasifCount} Pasif kayıt</strong> dokunulmayacak`;
            
            document.getElementById('restartConfirmMessage').innerHTML = message;
            
            // Modal'ı göster
            const modal = document.getElementById('restartConfirmModal');
            modal.style.display = 'flex';
            
            // Form referansını sakla
            restartPendingImportId = importId;
            const formElement = event ? event.target.closest('form') : null;
            restartPendingForm = formElement;
            
            console.log('Modal açıldı, form bulundu:', !!restartPendingForm);
            
            // Form'un normal submit'ini engelle
            return false;
        }
        
        function confirmRestart() {
            console.log('✅ TAMAM butonuna basıldı - Form submit ediliyor');
            
            // Önce form referansını yerel değişkene al
            const formToSubmit = restartPendingForm;
            const importIdToSubmit = restartPendingImportId;
            
            // Modal'ı kapat
            closeRestartConfirmModal();
            
            // Eğer form varsa (ana sayfadan tıklandıysa), form submit et
            if (formToSubmit) {
                console.log('Form submit ediliyor...');
                
                // Geçici submit button oluştur
                const submitBtn = document.createElement('input');
                submitBtn.type = 'submit';
                submitBtn.name = 'start_import';
                submitBtn.value = '1';
                submitBtn.style.display = 'none';
                formToSubmit.appendChild(submitBtn);
                
                // Button'u tıklat (gerçek submit)
                submitBtn.click();
            } 
            // Eğer form yoksa ama importId varsa (AJAX refresh'ten geldiyse)
            else if (importIdToSubmit) {
                console.log('AJAX refresh sonrası - Form aranıyor, ImportId:', importIdToSubmit);
                
                // Önce data-import-id ile form ara
                let form = document.querySelector(`form[data-import-id="${importIdToSubmit}"]`);
                
                // Bulunamazsa, input[name="import_id"] ile form ara
                if (!form) {
                    console.log('data-import-id ile bulunamadı, input ile aranıyor...');
                    const allForms = document.querySelectorAll('form');
                    for (const f of allForms) {
                        const importInput = f.querySelector('input[name="import_id"]');
                        if (importInput && importInput.value == importIdToSubmit) {
                            form = f;
                            console.log('Form bulundu!');
                            break;
                        }
                    }
                }
                
                if (form) {
                    console.log('Form bulundu ve submit ediliyor...');
                    
                    // Restart ve start_import hidden input'larını ekle
                    const restartInput = document.createElement('input');
                    restartInput.type = 'hidden';
                    restartInput.name = 'restart';
                    restartInput.value = '1';
                    form.appendChild(restartInput);
                    
                    const startInput = document.createElement('input');
                    startInput.type = 'hidden';
                    startInput.name = 'start_import';
                    startInput.value = '1';
                    form.appendChild(startInput);
                    
                    // Form'u submit et
                    form.submit();
                } else {
                    console.error('Form bulunamadı! ImportId:', importIdToSubmit);
                    alert('⚠️ Form bulunamadı! Sayfayı yenileyin.');
                }
            } else {
                console.error('Ne form ne de importId var!');
            }
        }
        
        function closeRestartConfirmModal() {
            console.log('❌ İPTAL butonuna basıldı - Modal kapatılıyor');
            const modal = document.getElementById('restartConfirmModal');
            modal.style.display = 'none';
            restartPendingImportId = null;
            restartPendingForm = null;
        }

        // Import fonksiyonu
        function importData(input) {
            if (!input.files || input.files.length === 0) {
                return;
            }
            
            const file = input.files[0];
            const validExtensions = ['.xls', '.xlsx'];
            const fileExtension = file.name.substring(file.name.lastIndexOf('.')).toLowerCase();
            
            if (!validExtensions.includes(fileExtension)) {
                alert('❌ Sadece Excel dosyaları (.xls, .xlsx) yüklenebilir!');
                input.value = '';
                return;
            }
            
            // Modal'ı aç ve dosya bilgisini göster
            document.getElementById('selectedFileName').textContent = file.name;
            document.getElementById('importModal').style.display = 'flex';
            
            // Varsayılan tarih-saat değerini ayarla (ŞİMDİKİ ZAMAN)
            const now = new Date();
            // now.setHours(now.getHours() + 1); // KALDIRILDI - Artık 1 saat eklenmiyor!
            now.setMinutes(0); // Dakikayı yuvarla
            now.setSeconds(0);
            
            // datetime-local formatına çevir (YYYY-MM-DDTHH:MM)
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const defaultDateTime = `${year}-${month}-${day}T${hours}:${minutes}`;
            
            document.getElementById('import_scheduled_time').value = defaultDateTime;
            
            // Global'e kaydet
            window.selectedImportFile = file;
        }
        
        // Import listesini yenile (AJAX)
        function refreshImportList() {
            const icon = document.getElementById('refreshIcon');
            const container = document.getElementById('importListContainer');
            
            // İkon animasyonu
            icon.classList.add('fa-spin');
            
            // AJAX ile veri çek
            fetch('get_imports_ajax.php')
                .then(response => response.text())
                .then(html => {
                    container.innerHTML = html;
                    icon.classList.remove('fa-spin');
                    console.log('✅ Import listesi yenilendi');
                    
                    // Event listener'ları yeniden bağla
                    attachAllEventListeners();
                })
                .catch(error => {
                    console.error('❌ Yenileme hatası:', error);
                    icon.classList.remove('fa-spin');
                    alert('Liste yenilenirken hata oluştu!');
                });
        }
        
        // Tüm event listener'ları bağla
        function attachAllEventListeners() {
            // Import file input
            const importFileInput = document.getElementById('importFile');
            if (importFileInput) {
                importFileInput.removeEventListener('change', handleImportFileChange);
                importFileInput.addEventListener('change', handleImportFileChange);
            }
            
            // Rapor butonları
            const reportButtons = document.querySelectorAll('.btn-report');
            reportButtons.forEach(btn => {
                btn.removeEventListener('click', handleReportClick);
                btn.addEventListener('click', handleReportClick);
            });
            
            // Duplicate butonları
            const duplicateButtons = document.querySelectorAll('.btn-duplicate');
            duplicateButtons.forEach(btn => {
                btn.removeEventListener('click', handleDuplicateClick);
                btn.addEventListener('click', handleDuplicateClick);
            });
            
            console.log('🔗 Event listeners yeniden bağlandı');
        }
        
        function handleImportFileChange(e) {
            importData(e.target);
        }
        
        function handleReportClick(e) {
            e.preventDefault();
            const importId = this.getAttribute('data-import-id');
            const fileName = this.getAttribute('data-file-name');
            if (typeof window.showReport === 'function') {
                window.showReport(importId, fileName);
            }
        }
        
        function handleDuplicateClick(e) {
            e.preventDefault();
            const importId = this.getAttribute('data-import-id');
            const fileName = this.getAttribute('data-file-name');
            if (typeof window.showDuplicateModal === 'function') {
                window.showDuplicateModal(importId, fileName);
            }
        }
        
        // SİL butonu - Event delegation (AJAX içerik için de çalışır)
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.btn-delete-import');
            if (btn) {
                e.preventDefault();
                const form = btn.closest('form.form-delete-import');
                if (form) {
                    const fileName = form.getAttribute('data-file-name') || 'dosya';
                    const total = form.getAttribute('data-total') || '0';
                    deletePendingForm = form;
                    document.getElementById('deleteConfirmMessage').innerHTML = 
                        'SİL: <strong>' + fileName + '</strong> - ' + total + ' kayıt?<br><br>Bu dosyayı ve tüm arama kayıtlarını silmek istediğinizden emin misiniz?';
                    document.getElementById('deleteConfirmModal').style.display = 'flex';
                }
            }
        });
        
        // İlk yükleme
        document.addEventListener('DOMContentLoaded', function() {
            refreshImportList();
        });
        
        // DOM hazır olduğunda import file input'a event listener ekle
        document.addEventListener('DOMContentLoaded', function() {
            const importFileInput = document.getElementById('importFile');
            if (importFileInput) {
                importFileInput.addEventListener('change', function() {
                    importData(this);
                });
                console.log('✅ Import file event listener eklendi');
            } else {
                console.error('❌ importFile elementi bulunamadı!');
            }
        });
        
        // Import ayarlarını kaydet (Tekrar, Aralık, Hız)
        function saveImportSettings(importId) {
            const retryCount = document.getElementById('retry_count_' + importId)?.value;
            const retryInterval = document.getElementById('retry_interval_' + importId)?.value;
            const maxConcurrent = document.getElementById('max_concurrent_' + importId)?.value;
            const projectId = document.getElementById('project_id_' + importId)?.value || '';
            const templateId = document.getElementById('template_id_' + importId)?.value || '';
            
            let body = 'import_id=' + importId;
            if (retryCount) body += '&retry_count=' + encodeURIComponent(retryCount);
            if (retryInterval) body += '&retry_interval=' + encodeURIComponent(retryInterval);
            if (maxConcurrent) body += '&max_concurrent=' + encodeURIComponent(maxConcurrent);
            if (projectId) body += '&project_id=' + encodeURIComponent(projectId);
            if (templateId) body += '&template_id=' + encodeURIComponent(templateId);
            
            fetch('save_import_settings.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: body
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert('✅ ' + (data.message || 'Ayarlar kaydedildi!'));
                } else {
                    alert('❌ ' + (data.message || 'Kaydetme hatası'));
                }
            })
            .catch(err => {
                alert('❌ Bağlantı hatası: ' + err.message);
            });
        }
        
        function renderImportSwitch(btn, isActive, loading = false) {
            btn.dataset.active = isActive ? '1' : '0';
            btn.disabled = loading;
            btn.style.background = loading ? '#6c757d' : (isActive ? '#28a745' : '#dc3545');
            btn.innerHTML = '<span style="position:absolute;top:3px;left:' +
                (isActive ? '51px' : '3px') +
                ';width:42px;height:32px;line-height:32px;border-radius:17px;background:#fff;color:' +
                (loading ? '#6c757d' : (isActive ? '#28a745' : '#dc3545')) +
                ';font-size:11px;font-weight:800;transition:.25s;box-shadow:0 2px 5px rgba(0,0,0,.25);">' +
                (loading ? '...' : (isActive ? 'ON' : 'OFF')) + '</span>';
        }

        const importSwitchQueue = {};

        function buildImportSwitchBody(importId, active) {
            const params = new URLSearchParams({
                import_id: importId,
                active: active ? '1' : '0'
            });

            if (active) {
                const values = {
                    retry_count: document.getElementById('retry_count_' + importId)?.value,
                    retry_interval: document.getElementById('retry_interval_' + importId)?.value,
                    max_concurrent: document.getElementById('max_concurrent_' + importId)?.value,
                    project_id: document.getElementById('project_id_' + importId)?.value ||
                        document.getElementById('project_id_restart_' + importId)?.value,
                    template_id: document.getElementById('template_id_' + importId)?.value ||
                        document.getElementById('template_id_restart_' + importId)?.value
                };
                Object.entries(values).forEach(([key, value]) => {
                    if (value) params.set(key, value);
                });
            }

            return params.toString();
        }

        async function processImportSwitchQueue(importId) {
            const state = importSwitchQueue[importId];
            if (!state || state.busy) return;

            state.busy = true;
            try {
                while (state.confirmed !== state.desired) {
                    const target = state.desired;
                    const response = await fetch('toggle_active.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: buildImportSwitchBody(importId, target)
                    });
                    if (!response.ok) throw new Error('HTTP ' + response.status);

                    const data = await response.json();
                    if (!data.success) throw new Error(data.message || 'Durum değiştirilemedi.');
                    state.confirmed = target;
                }
            } catch (error) {
                state.desired = state.confirmed;
                const btn = document.getElementById('import_switch_' + importId);
                if (btn) renderImportSwitch(btn, state.confirmed);
                alert('❌ ' + error.message);
            } finally {
                state.busy = false;
                if (state.confirmed !== state.desired) {
                    processImportSwitchQueue(importId);
                }
            }
        }

        function toggleImportSwitch(importId, isActive) {
            const btn = document.getElementById('import_switch_' + importId);
            if (!btn) return;

            const state = importSwitchQueue[importId] || {
                confirmed: isActive,
                desired: isActive,
                busy: false
            };
            importSwitchQueue[importId] = state;

            state.desired = !isActive;
            renderImportSwitch(btn, state.desired);
            processImportSwitchQueue(importId);
        }

        // Import'u ON yap
        function startImport(importId) {
            console.log('═══════════════════════════════════════');
            console.log('🟢 startImport ÇAĞRILDI!');
            console.log('📋 ImportId:', importId);
            console.log('═══════════════════════════════════════');
            
            const btn = document.getElementById('import_switch_' + importId);
            
            if (!btn) {
                console.error('❌ ON/OFF anahtarı bulunamadı! ID:', 'import_switch_' + importId);
                alert('❌ ON butonu bulunamadı!');
                return;
            }
            
            console.log('✅ ON buton bulundu:', btn);
            
            // Butonu geçici olarak devre dışı bırak
            renderImportSwitch(btn, false, true);
            console.log('⏳ Buton devre dışı bırakıldı, istek gönderiliyor...');
            
            // Form değerlerini topla
            const retryCount = document.getElementById('retry_count_' + importId)?.value || '';
            const retryInterval = document.getElementById('retry_interval_' + importId)?.value || '';
            const maxConcurrent = document.getElementById('max_concurrent_' + importId)?.value || '';
            const projectId = document.getElementById('project_id_' + importId)?.value || document.getElementById('project_id_restart_' + importId)?.value || '';
            const templateId = document.getElementById('template_id_' + importId)?.value || document.getElementById('template_id_restart_' + importId)?.value || '';
            
            let requestBody = 'import_id=' + importId + '&active=1';
            if (retryCount) requestBody += '&retry_count=' + encodeURIComponent(retryCount);
            if (retryInterval) requestBody += '&retry_interval=' + encodeURIComponent(retryInterval);
            if (maxConcurrent) requestBody += '&max_concurrent=' + encodeURIComponent(maxConcurrent);
            if (projectId) requestBody += '&project_id=' + encodeURIComponent(projectId);
            if (templateId) requestBody += '&template_id=' + encodeURIComponent(templateId);
            console.log('📤 Request Body:', requestBody);
            
            fetch('toggle_active.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: requestBody
            })
            .then(response => {
                console.log('📥 Response alındı:', response);
                console.log('📊 Response Status:', response.status);
                console.log('📊 Response OK:', response.ok);
                
                if (!response.ok) {
                    throw new Error('HTTP Error: ' + response.status);
                }
                
                return response.json();
            })
            .then(data => {
                console.log('═══════════════════════════════════════');
                console.log('✅ JSON Data alındı:');
                console.log(data);
                console.log('═══════════════════════════════════════');
                
                if (data.success) {
                    console.log('✅ İşlem BAŞARILI!');
                    console.log('📊 Etkilenen satır:', data.affected_rows);
                    console.log('⚡ Başlatılan arama:', data.started_calls);
                    
                    // Anahtarı sağa (ON) al
                    renderImportSwitch(btn, true);
                    
                    // Başarı mesajı
                    alert(data.message || '✅ Durum güncellendi!');
                    
                    console.log('✅ Buton güncellendi ve aktif edildi');
                } else {
                    console.error('❌ İşlem BAŞARISIZ!');
                    console.error('❌ Hata mesajı:', data.message);
                    
                    alert('❌ Hata: ' + (data.message || 'Bilinmeyen hata'));
                    renderImportSwitch(btn, false);
                }
            })
            .catch(error => {
                console.error('═══════════════════════════════════════');
                console.error('❌ FETCH HATASI!');
                console.error('❌ Error:', error);
                console.error('❌ Error Message:', error.message);
                console.error('❌ Error Stack:', error.stack);
                console.error('═══════════════════════════════════════');
                
                alert('❌ Bağlantı hatası: ' + error.message);
                renderImportSwitch(btn, false);
            });
        }
        
        // Modal kapat
        function closeImportModal() {
            document.getElementById('importModal').style.display = 'none';
            document.getElementById('importFile').value = '';
            window.selectedImportFile = null;
        }
        
        // Zamanlanmış saati temizle
        function clearScheduledTime() {
            document.getElementById('import_scheduled_time').value = '';
            alert('✅ Arama zamanı temizlendi!\n\nImport hemen başlayacak.');
        }
        
        function clearDuplicateScheduledTime() {
            document.getElementById('dup_scheduled_time').value = '';
            alert('✅ Arama zamanı temizlendi!\n\nÇoğaltma işlemi hemen başlayacak.');
        }
        
        // Import işlemini başlat
        function submitImport() {
            const projectId = document.getElementById('import_project_id').value;
            const templateId = document.getElementById('import_template_id').value;
            const scheduledTime = document.getElementById('import_scheduled_time').value;
            
            if (!projectId) {
                alert('❌ Lütfen proje seçin!');
                return;
            }
            
            if (!window.selectedImportFile) {
                alert('❌ Dosya seçilmedi!');
                return;
            }
            
            // FormData oluştur
            const formData = new FormData();
            formData.append('excel_file', window.selectedImportFile);
            formData.append('project_id', projectId);
            formData.append('template_id', templateId);
            formData.append('scheduled_time', scheduledTime);
            formData.append('quick_import', '1');
            
            // Loading göster
            const btn = document.getElementById('submitImportBtn');
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Yükleniyor...';
            btn.disabled = true;
            
            // Import et
            fetch('quick_import.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                btn.innerHTML = originalHTML;
                btn.disabled = false;
                
                if (data.success) {
                    alert(`✅ ${data.message}\n\n${data.recordCount} kayıt eklendi!`);
                    closeImportModal();
                    location.reload();
                } else {
                    let errorMsg = '❌ HATA: ' + data.message;
                    if (data.file) {
                        errorMsg += '\n\nDosya: ' + data.file + ':' + data.line;
                    }
                    if (data.trace) {
                        console.error('Stack Trace:', data.trace);
                        errorMsg += '\n\nDetaylar console\'da (F12)';
                    }
                    alert(errorMsg);
                }
            })
            .catch(error => {
                console.error('Import hatası:', error);
                btn.innerHTML = originalHTML;
                btn.disabled = false;
                alert('❌ Import sırasında hata oluştu!');
            });
        }
        
        // Export fonksiyonu
        function exportData() {
            if (!confirm('Tüm import kayıtlarını Excel olarak indirmek istiyor musunuz?')) {
                return;
            }
            
            // Loading göster
            const btn = event.target.closest('.btn');
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> İndiriliyor...';
            btn.disabled = true;
            
            // Export API'ye istek at
            fetch('export_imports.php')
                .then(response => {
                    if (!response.ok) throw new Error('Export başarısız');
                    return response.blob();
                })
                .then(blob => {
                    // Dosyayı indir
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = 'import_kayitlari_' + new Date().toISOString().split('T')[0] + '.xlsx';
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);
                    
                    // Butonu eski haline getir
                    btn.innerHTML = originalHTML;
                    btn.disabled = false;
                    
                    alert('✅ Export başarılı!');
                })
                .catch(error => {
                    console.error('Export hatası:', error);
                    btn.innerHTML = originalHTML;
                    btn.disabled = false;
                    alert('❌ Export sırasında hata oluştu!');
                });
        }
        
        // Global değişkenler
        let currentReportImportId = null;
        let currentReportFileName = null;
        
        // Rapor modalını aç
        window.showReport = function(importId, fileName) {
            console.log('showReport called:', importId, fileName);
            try {
                currentReportImportId = importId;
                currentReportFileName = fileName;
                
                document.getElementById('reportFileName').textContent = fileName;
                const modal = document.getElementById('reportModal');
                console.log('Report Modal element:', modal);
                if (modal) {
                    modal.style.cssText = 'display: block !important; position: fixed !important; top: 0 !important; left: 0 !important; width: 100% !important; height: 100% !important; background: rgba(0,0,0,0.7) !important; z-index: 999999 !important; overflow-y: auto !important; padding: 20px !important;';
                    document.body.style.overflow = 'hidden';
                    console.log('Report Modal displayed');
                } else {
                    console.error('reportModal element not found!');
                    alert('❌ Rapor modal elementi bulunamadı!');
                    return;
                }
            
            // Rapor verilerini yükle
            fetch('get_report.php?import_id=' + importId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Özet istatistikleri göster
                        const summary = data.summary;
                        document.getElementById('reportSummary').innerHTML = `
                            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; border-radius: 10px; color: white; text-align: center;">
                                <div style="font-size: 32px; font-weight: bold; margin-bottom: 5px;">${summary.total}</div>
                                <div style="font-size: 14px; opacity: 0.9;">Toplam Kayıt</div>
                            </div>
                            <div style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); padding: 20px; border-radius: 10px; color: white; text-align: center;">
                                <div style="font-size: 32px; font-weight: bold; margin-bottom: 5px;">${summary.answered}</div>
                                <div style="font-size: 14px; opacity: 0.9;">✅ BAŞARILI (ARANDI)</div>
                            </div>
                            <div style="background: linear-gradient(135deg, #95a5a6 0%, #7f8c8d 100%); padding: 20px; border-radius: 10px; color: white; text-align: center;">
                                <div style="font-size: 32px; font-weight: bold; margin-bottom: 5px;">${summary.retry_waiting || 0}</div>
                                <div style="font-size: 14px; opacity: 0.9;">⏳ Retry Bekliyor</div>
                            </div>
                            <div style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); padding: 20px; border-radius: 10px; color: white; text-align: center;">
                                <div style="font-size: 32px; font-weight: bold; margin-bottom: 5px;">${summary.calling}</div>
                                <div style="font-size: 14px; opacity: 0.9;">🔵 Aranıyor</div>
                            </div>
                            <div style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); padding: 20px; border-radius: 10px; color: white; text-align: center;">
                                <div style="font-size: 32px; font-weight: bold; margin-bottom: 5px;">${summary.pending}</div>
                                <div style="font-size: 14px; opacity: 0.9;">🟡 Aranacak</div>
                            </div>
                        `;
                        
                        // Detaylı tabloyu doldur
                        let tableHTML = '';
                        data.details.forEach((row, index) => {
                            const bgColor = index % 2 === 0 ? '#f8f9fa' : 'white';
                            let statusColor, statusText;
                            if (row.Status == 2) {
                                statusColor = '#28a745';
                                statusText = '✅ BAŞARILI (ARANDI)';
                            } else if (row.Status == 1 && row.CallCount > 0) {
                                statusColor = '#6c757d';
                                // Durum varsa göster, yoksa "Retry Bekliyor"
                                statusText = row.Durum ? row.Durum + ' (Tekrar aranacak)' : '⏳ Retry Bekliyor';
                            } else {
                                statusColor = '#ffc107';
                                statusText = '🟡 Aranacak';
                            }
                            
                            tableHTML += `
                                <tr style="background: ${bgColor}; border-bottom: 1px solid #e0e0e0;">
                                    <td style="padding: 12px; font-size: 13px;">${row.PhoneNumber}</td>
                                    <td style="padding: 12px; font-size: 13px;">${row.Adi || '-'}</td>
                                    <td style="padding: 12px; text-align: center; font-size: 13px;">${row.CallCount} / ${row.TryCallCount}</td>
                                    <td style="padding: 12px; text-align: center; font-size: 13px;">
                                        <span style="background: ${statusColor}; color: white; padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: 600;">
                                            ${statusText}
                                        </span>
                                    </td>
                                    <td style="padding: 12px; text-align: center; font-size: 12px; color: #333; font-weight: 600;">
                                        ${row.CallTime || '-'}
                                    </td>
                                    <td style="padding: 12px; font-size: 12px; color: #666;">${row.Durum || '-'}</td>
                                    <td style="padding: 12px; font-size: 13px;">${row.LastCallTime || '-'}</td>
                                    <td style="padding: 12px; font-size: 13px;">${row.tus1 || row.tus2 ? (row.tus1 || '') + ' ' + (row.tus2 || '') : '-'}</td>
                                </tr>
                            `;
                        });
                        
                        document.getElementById('reportTableBody').innerHTML = tableHTML;
                    } else {
                        alert('❌ Rapor yüklenemedi: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Rapor yükleme hatası:', error);
                    alert('❌ Rapor yüklenirken hata oluştu!');
                });
            } catch (e) {
                console.error('showReport error:', e);
                alert('❌ Hata: ' + e.message);
            }
        }
        
        // Rapor modalını kapat
        window.closeReportModal = function() {
            document.getElementById('reportModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }
        
        // Export modalını aç
        let currentExportImportId = null;
        window.showExportModal = function(importId, fileName) {
            console.log('✅ showExportModal called:', importId, fileName);
            currentExportImportId = importId;
            
            const fileNameElement = document.getElementById('exportFileName');
            const modal = document.getElementById('exportModal');
            
            console.log('📝 exportFileName element:', fileNameElement);
            console.log('📝 exportModal element:', modal);
            
            if (!fileNameElement) {
                alert('❌ exportFileName elementi bulunamadı!');
                return;
            }
            
            if (!modal) {
                alert('❌ exportModal elementi bulunamadı!');
                return;
            }
            
            fileNameElement.textContent = fileName;
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            
            console.log('✅ Modal açıldı!');
        }
        
        // Export modalını kapat
        window.closeExportModal = function() {
            document.getElementById('exportModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }
        
        // Export işlemini gerçekleştir
        window.exportData = function(type) {
            if (!currentExportImportId) {
                alert('❌ Import ID bulunamadı!');
                return;
            }
            
            // Yeni pencerede export sayfasını aç
            window.open('export_advanced.php?import_id=' + currentExportImportId + '&type=' + type, '_blank');
            
            // Modal'ı kapat
            closeExportModal();
        }
        
        // Çoğaltma modal'ını aç
        window.showDuplicateModal = function(importId, fileName) {
            console.log('showDuplicateModal called:', importId, fileName);
            try {
                document.getElementById('source_import_id').value = importId;
                const modal = document.getElementById('duplicateModal');
                console.log('Modal element:', modal);
                if (modal) {
                    // Varsayılan arama zamanını ayarla (ŞİMDİKİ ZAMAN)
                    const now = new Date();
                    // now.setHours(now.getHours() + 1); // KALDIRILDI - Artık 1 saat eklenmiyor!
                    const dateTimeString = now.getFullYear() + '-' + 
                        String(now.getMonth() + 1).padStart(2, '0') + '-' + 
                        String(now.getDate()).padStart(2, '0') + 'T' + 
                        String(now.getHours()).padStart(2, '0') + ':' + 
                        String(now.getMinutes()).padStart(2, '0');
                    document.getElementById('dup_scheduled_time').value = dateTimeString;
                    
                    modal.style.cssText = 'display: flex !important; position: fixed !important; top: 0 !important; left: 0 !important; width: 100% !important; height: 100% !important; background: rgba(0,0,0,0.7) !important; z-index: 999999 !important; align-items: center !important; justify-content: center !important;';
                    document.body.style.overflow = 'hidden';
                    console.log('Modal displayed');
                } else {
                    console.error('duplicateModal element not found!');
                    alert('❌ Modal elementi bulunamadı!');
                }
            } catch (e) {
                console.error('showDuplicateModal error:', e);
                alert('❌ Hata: ' + e.message);
            }
        }
        
        // Çoğaltma modal'ını kapat
        window.closeDuplicateModal = function() {
            const modal = document.getElementById('duplicateModal');
            if (modal) {
                modal.style.cssText = 'display: none !important;';
            }
            document.body.style.overflow = 'auto';
        }
        
        console.log('✅ showDuplicateModal LOADED:', typeof window.showDuplicateModal);
        console.log('✅ showReport LOADED:', typeof window.showReport);
        
        // Raporu Excel'e aktar
        function exportReport() {
            if (!currentReportImportId) {
                alert('❌ Rapor yüklenmedi!');
                return;
            }
            
            const btn = document.getElementById('exportReportBtn');
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> İndiriliyor...';
            btn.disabled = true;
            
            // Export URL
            const url = 'export_report.php?import_id=' + currentReportImportId;
            
            // Dosyayı indir
            fetch(url)
                .then(response => {
                    console.log('Export response status:', response.status);
                    console.log('Export response ok:', response.ok);
                    
                    if (!response.ok) {
                        return response.text().then(text => {
                            console.error('Export error response:', text);
                            throw new Error(text || 'Export başarısız (HTTP ' + response.status + ')');
                        });
                    }
                    return response.blob();
                })
                .then(blob => {
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = 'rapor_' + currentReportFileName.replace(/[^a-zA-Z0-9]/g, '_') + '_' + new Date().toISOString().split('T')[0] + '.csv';
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);
                    
                    btn.innerHTML = originalHTML;
                    btn.disabled = false;
                })
                .catch(error => {
                    console.error('Export hatası:', error);
                    console.error('Error message:', error.message);
                    btn.innerHTML = originalHTML;
                    btn.disabled = false;
                    
                    // Hata mesajını göster
                    const errorMsg = error.message;
                    console.log('Full error message:', errorMsg);
                    
                    if (errorMsg.includes('<!DOCTYPE') || errorMsg.includes('<html>')) {
                        // HTML hata mesajı - yeni pencerede göster
                        const win = window.open('', '_blank');
                        win.document.write(errorMsg);
                    } else {
                        // İlk 500 karakteri göster
                        const displayMsg = errorMsg.length > 500 ? errorMsg.substring(0, 500) + '...' : errorMsg;
                        alert('❌ Export hatası:\n\n' + displayMsg);
                    }
                });
        }

        // Export dropdown toggle
        window.toggleExportMenu = function(importId) {
            const menu = document.getElementById('export-menu-' + importId);
            // Diğer tüm menüleri kapat
            document.querySelectorAll('.export-menu').forEach(function(m) {
                if (m.id !== 'export-menu-' + importId) {
                    m.style.display = 'none';
                }
            });
            // Bu menüyü aç/kapat
            menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
        }
        
        // Sayfa dışına tıklanınca menüleri kapat
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.export-dropdown-btn') && !e.target.closest('.export-menu')) {
                document.querySelectorAll('.export-menu').forEach(function(m) {
                    m.style.display = 'none';
                });
            }
        });
        
        console.log('✅ SCRIPT FINISHED LOADING');
        
        // Export Modal Test
        console.log('🔍 Testing Export Modal elements:');
        console.log('  - exportModal:', document.getElementById('exportModal'));
        console.log('  - exportFileName:', document.getElementById('exportFileName'));
        console.log('  - showExportModal function:', typeof window.showExportModal);
        
        // Event Listeners - Sayfa yüklendikten sonra
        document.addEventListener('DOMContentLoaded', function() {
            console.log('✅ DOM LOADED - Attaching event listeners');
            
            // EXPORT butonlarına event listener ekle
            const exportButtons = document.querySelectorAll('.btn-export');
            console.log('📥 Found ' + exportButtons.length + ' export buttons');
            exportButtons.forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const importId = this.getAttribute('data-import-id');
                    const fileName = this.getAttribute('data-file-name');
                    console.log('📥 EXPORT clicked:', importId, fileName);
                    window.showExportModal(importId, fileName);
                });
            });
            
            // Tüm ÇOĞALT butonlarına event listener ekle
            const duplicateButtons = document.querySelectorAll('.btn-duplicate');
            console.log('📋 Found ' + duplicateButtons.length + ' duplicate buttons');
            duplicateButtons.forEach(function(btn) {
                btn.addEventListener('click', function() {
                    const importId = this.getAttribute('data-import-id');
                    const fileName = this.getAttribute('data-file-name');
                    console.log('ÇOĞALT clicked:', importId, fileName);
                    window.showDuplicateModal(importId, fileName);
                });
            });
            
            // Tüm RAPOR butonlarına event listener ekle
            document.querySelectorAll('.btn-report').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    const importId = this.getAttribute('data-import-id');
                    const fileName = this.getAttribute('data-file-name');
                    console.log('RAPOR clicked:', importId, fileName);
                    window.showReport(importId, fileName);
                });
            });
            
            console.log('✅ Event listeners attached:', {
                duplicate: document.querySelectorAll('.btn-duplicate').length,
                report: document.querySelectorAll('.btn-report').length
            });
        });
    </script>
</body>
</html>
