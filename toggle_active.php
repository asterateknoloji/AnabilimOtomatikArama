<?php
/**
 * Import Aktif/Pasif Toggle
 */

// EN BAŞTA TEST LOGU
error_log("═══════════════════════════════════════");
error_log("📥 toggle_active.php ÇAĞRILDI!");
error_log("🕐 Zaman: " . date('Y-m-d H:i:s'));
error_log("📊 POST Data: " . json_encode($_POST));
error_log("═══════════════════════════════════════");

require_once 'includes/check_auth.php';

require_once 'config.php';
require_once 'includes/SchemaManager.php';
require_once 'includes/BatchWorkerLauncher.php';

header('Content-Type: application/json');
ensureApplicationSchema($pdo);

// Kontrol yetkisi olan kullanıcı da ON/OFF anahtarını kullanabilmeli.
if (!hasPermission('import.toggle_active') && !hasPermission('import.control')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Bu işlem için yetkiniz yok.']);
    exit;
}

try {
    if (!isset($_POST['import_id']) || !isset($_POST['active'])) {
        throw new Exception('Eksik parametre!');
    }
    
    $importId = (int)$_POST['import_id'];
    $active = (int)$_POST['active'];
    
    // 0 veya 1 olmalı
    if (!in_array($active, [0, 1])) {
        throw new Exception('Geçersiz aktif değeri!');
    }

    // Anahtarın konumunu import seviyesinde kalıcı sakla.
    $pdo->prepare("
        UPDATE " . table('IMPORT_HISTORY') . "
        SET IsActive = ?
        WHERE id = ?
    ")->execute([$active, $importId]);
    
    // Aktif=1 yapılıyorsa: Önce form değerlerini (tekrar, aralık, hız, proje) IMPORT_HISTORY'ye kaydet
    // ve proje değerlerini tüm aranmamış (Status=1) kayıtlara uygula
    if ($active === 1) {
        $retryCount = isset($_POST['retry_count']) ? (int)$_POST['retry_count'] : null;
        $retryInterval = isset($_POST['retry_interval']) ? (int)$_POST['retry_interval'] : null;
        $maxConcurrent = isset($_POST['max_concurrent']) ? (int)$_POST['max_concurrent'] : null;
        $projectId = isset($_POST['project_id']) ? (int)$_POST['project_id'] : null;
        $templateId = isset($_POST['template_id']) ? (int)$_POST['template_id'] : null;
        
        $updates = [];
        $params = [];
        
        if ($retryCount !== null && $retryCount >= 1 && $retryCount <= 10) {
            $updates[] = "RetryCount = ?";
            $params[] = $retryCount;
        }
        if ($retryInterval !== null && $retryInterval >= 5 && $retryInterval <= 1440) {
            $updates[] = "RetryIntervalMinutes = ?";
            $params[] = $retryInterval;
        }
        if ($maxConcurrent !== null && $maxConcurrent >= 1 && $maxConcurrent <= 100) {
            $updates[] = "MaxConcurrentCalls = ?";
            $params[] = $maxConcurrent;
        }
        if ($projectId !== null && $projectId > 0) {
            $updates[] = "ProjectId = ?";
            $params[] = $projectId;
        }
        if ($templateId !== null && $templateId > 0) {
            $updates[] = "TemplateId = ?";
            $params[] = $templateId;
        }
        
        if (!empty($updates)) {
            $params[] = $importId;
            $updateSql = "UPDATE " . table('IMPORT_HISTORY') . " SET " . implode(", ", $updates) . " WHERE id = ?";
            $pdo->prepare($updateSql)->execute($params);
            
            // Tekrar (RetryCount) → CALLS.TryCallCount'u aranmamış kayıtlara uygula
            if ($retryCount !== null && $retryCount >= 1 && $retryCount <= 10) {
                $applyRetryStmt = $pdo->prepare("
                    UPDATE " . table('CALLS') . " 
                    SET TryCallCount = ? 
                    WHERE ImportId = ? AND Status IN (0, 1)
                ");
                $applyRetryStmt->execute([$retryCount, $importId]);
            }
            
            // Proje değerlerini tüm aranmamış (Status=1) kayıtlara uygula
            if ($projectId !== null && $projectId > 0) {
                $applyStmt = $pdo->prepare("
                    UPDATE " . table('CALLS') . " 
                    SET ProjectId = ? 
                    WHERE ImportId = ? AND Status IN (0, 1)
                ");
                $applyStmt->execute([$projectId, $importId]);
            }
        }
    }
    
    // OFF bir duraklatmadır: yeni parti çıkmaz, bekleyen kayıtlar silinmez.
    // Halihazırda Asterisk'e gönderilmiş çağrı tamamlanabilir.
    $stoppedCalls = 0;
    
    // Bu import'a ait tüm CALLS kayıtlarının Aktif durumunu güncelle
    $stmt = $pdo->prepare("
        UPDATE " . table('CALLS') . " 
        SET Aktif = ? 
        WHERE ImportId = ?
    ");
    $stmt->execute([$active, $importId]);
    
    $affectedRows = $stmt->rowCount();
    
    // ON yalnızca tek kilitli parti işçisini başlatır.
    // Burada doğrudan arama başlatmak supervisor ile yarışarak HIZ=1 iken
    // iki çağrı çıkmasına neden oluyordu.
    $startedCalls = 0;
    if ($active === 1) {
        error_log("═══════════════════════════════════════");
        error_log("⚡ AKTİF YAPILDI - Parti işçisi başlatılıyor");
        error_log("📋 ImportId: $importId");
        error_log("📊 Etkilenen kayıt sayısı: $affectedRows");

        // İşçi ilk partiyi de kendisi başlatır; ikinci bir başlatma yolu yoktur.
        if (!launchBatchCallWorker($importId, false)) {
            error_log("⚠️ Parti arama işçisi başlatılamadı - ImportId: {$importId}");
        }
        error_log("═══════════════════════════════════════");
    }
    
    // Import bilgisini al
    $importInfo = $pdo->prepare("SELECT OriginalFileName FROM " . table('IMPORT_HISTORY') . " WHERE id = ?");
    $importInfo->execute([$importId]);
    $importData = $importInfo->fetch();
    
    // Log
    $statusText = $active === 1 ? 'AKTİF' : 'PASİF';
    logActivity('control', 'import', "Import {$statusText} yapıldı: {$importData['OriginalFileName']} ({$affectedRows} kayıt)", $importId);
    
    // Mesaj oluştur
    if ($active === 1) {
        if ($affectedRows > 0) {
            $message = "✅ {$affectedRows} kayıt ON yapıldı!\n\n⚡ İlk parti arka plan işçisine gönderildi.";
        } else {
            $message = "✅ Import zaten ON konumunda.\n\n⚡ Parti işçisi kontrol edildi.";
        }
    } else {
        $message = "⏸️ Arama OFF konumuna alındı.\n\nBekleyen kayıtlar korunuyor; yeniden ON yaptığınızda devam edecek.";
    }
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'affected_rows' => $affectedRows,
        'started_calls' => $startedCalls,
        'stopped_calls' => $stoppedCalls ?? 0
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
