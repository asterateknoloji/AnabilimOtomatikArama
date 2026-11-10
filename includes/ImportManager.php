<?php
/**
 * Import Yönetim Sınıfı
 */

class ImportManager
{
    private $pdo;
    
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }
    
    /**
     * Import geçmişini getir
     */
    public function getImportHistory($projectId = null, $limit = 50)
    {
        // MySQL 5.5 için LIMIT parametresini doğrudan SQL'e ekle
        $limit = intval($limit); // Güvenlik için integer'a çevir
        
        $sql = "SELECT ih.*, p.ProjectName, mt.TemplateName 
                FROM " . table('IMPORT_HISTORY') . " ih
                LEFT JOIN PROJECTS p ON ih.ProjectId = p.id
                LEFT JOIN MESSAGE_TEMPLATES mt ON ih.TemplateId = mt.id
                WHERE 1=1";
        
        $params = [];
        
        if ($projectId) {
            $sql .= " AND ih.ProjectId = ?";
            $params[] = $projectId;
        }
        
        $sql .= " ORDER BY ih.ImportDate DESC LIMIT {$limit}";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Import kaydı oluştur
     */
    public function createImport($projectId, $fileName, $originalFileName, $templateId = null, $scheduledTime = null, $retryIntervalMinutes = 60)
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO " . table('IMPORT_HISTORY') . " 
            (ProjectId, FileName, OriginalFileName, TemplateId, ScheduledTime, Status, ImportDate, RetryIntervalMinutes)
            VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)
        ");
        
        $status = $scheduledTime ? 'scheduled' : 'pending';
        
        $stmt->execute([
            $projectId,
            $fileName,
            $originalFileName,
            $templateId,
            $scheduledTime,
            $status,
            $retryIntervalMinutes
        ]);
        
        return $this->pdo->lastInsertId();
    }
    
    /**
     * Import durumunu güncelle
     */
    public function updateImportStatus($importId, $status, $recordCount = null)
    {
        $sql = "UPDATE " . table('IMPORT_HISTORY') . " SET Status = ?";
        $params = [$status];
        
        if ($recordCount !== null) {
            $sql .= ", RecordCount = ?";
            $params[] = $recordCount;
        }
        
        if ($status === 'in_progress') {
            $sql .= ", StartedAt = NOW()";
        } elseif (in_array($status, ['completed', 'failed'])) {
            $sql .= ", CompletedAt = NOW()";
        }
        
        $sql .= " WHERE id = ?";
        $params[] = $importId;
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }
    
    /**
     * Import detaylarını getir
     */
    public function getImportDetails($importId)
    {
        $stmt = $this->pdo->prepare("
            SELECT ih.*, p.ProjectName, mt.TemplateName,
                   COUNT(c.id) as CallCount,
                   SUM(CASE WHEN c.Status = 1 THEN 1 ELSE 0 END) as PendingCount,
                   SUM(CASE WHEN c.Status = 2 THEN 1 ELSE 0 END) as CallingCount,
                   SUM(CASE WHEN c.Status = 3 THEN 1 ELSE 0 END) as CompletedCount,
                   SUM(CASE WHEN c.Status = 4 THEN 1 ELSE 0 END) as FailedCount
            FROM " . table('IMPORT_HISTORY') . " ih
            LEFT JOIN PROJECTS p ON ih.ProjectId = p.id
            LEFT JOIN MESSAGE_TEMPLATES mt ON ih.TemplateId = mt.id
            LEFT JOIN CALLS c ON ih.id = c.ImportId
            WHERE ih.id = ?
            GROUP BY ih.id
        ");
        
        $stmt->execute([$importId]);
        return $stmt->fetch();
    }
    
    /**
     * Zamanlanmış importları getir
     */
    public function getScheduledImports()
    {
        $stmt = $this->pdo->query("
            SELECT * FROM " . table('IMPORT_HISTORY') . " 
            WHERE Status = 'scheduled' 
            AND ScheduledTime <= NOW()
            ORDER BY ScheduledTime ASC
        ");
        
        return $stmt->fetchAll();
    }
    
    /**
     * Import için arama istatistikleri
     */
    public function getImportCallStats($importId)
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN Aktif = 1 THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN Status != 2 THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN Status = 1 OR Locked = 1 THEN 1 ELSE 0 END) as calling,
                SUM(CASE WHEN Status = 2 THEN 1 ELSE 0 END) as completed
            FROM " . table('CALLS') . "
            WHERE ImportId = ?
        ");
        
        $stmt->execute([$importId]);
        $result = $stmt->fetch();
        
        // NULL değerleri 0'a çevir
        return [
            'total' => (int)$result['total'],
            'active' => (int)($result['active'] ?? 0),
            'pending' => (int)($result['pending'] ?? 0),
            'calling' => (int)($result['calling'] ?? 0),
            'retry_waiting' => 0,
            'completed' => (int)($result['completed'] ?? 0),
            'failed' => 0
        ];
    }
    
    /**
     * Excel dosyasından CALLS tablosuna kayıt ekle
     */
    public function importFromExcel($filePath, $projectId, $templateId, $importId)
    {
        if (!file_exists($filePath)) {
            throw new Exception("Excel dosyası bulunamadı: {$filePath}");
        }
        
        // Template adını al
        $templateName = 'Varsayılan';
        if ($templateId) {
            $templateStmt = $this->pdo->prepare("SELECT TemplateName FROM " . table('MESSAGE_TEMPLATES') . " WHERE id = ?");
            $templateStmt->execute([$templateId]);
            $templateResult = $templateStmt->fetch();
            if ($templateResult) {
                $templateName = $templateResult['TemplateName'];
            }
        }
        
        // SimpleXLSX kütüphanesini kullan
        require_once __DIR__ . '/../lib/simplexlsx/SimpleXLSX.php';
        
        $xlsx = SimpleXLSX::parse($filePath);
        if (!$xlsx) {
            throw new Exception("Excel dosyası okunamadı: " . SimpleXLSX::parseError());
        }
        
        $rows = $xlsx->rows();
        $success = 0;
        $errors = 0;
        $duplicates = 0; // Tekrar eden numaralar
        
        // İlk satır başlık olabilir, kontrol et
        $startRow = 0;
        if (isset($rows[0]) && !is_numeric($rows[0][0])) {
            $startRow = 1; // Başlık satırını atla
        }
        
        // Excel içinde tekrar eden numaraları takip et
        $seenPhones = [];
        
        for ($i = $startRow; $i < count($rows); $i++) {
            $row = $rows[$i];
            
            // Boş satırları atla
            if (empty($row[0])) {
                continue;
            }
            
            try {
                $telefon = $this->formatPhone($row[0]);
                
                // ✅ KONTROL 1: Excel içinde bu numara daha önce eklendi mi?
                if (isset($seenPhones[$telefon])) {
                    $duplicates++;
                    error_log("Duplicate phone in Excel: {$telefon} (satır " . ($i + 1) . ")");
                    continue; // Bu numarayı atla
                }
                
                // ✅ KONTROL 2: Veritabanında bu ImportId için bu numara var mı?
                $checkStmt = $this->pdo->prepare("
                    SELECT COUNT(*) FROM " . table('CALLS') . " 
                    WHERE ImportId = ? AND PhoneNumber = ?
                ");
                $checkStmt->execute([$importId, $telefon]);
                $exists = $checkStmt->fetchColumn();
                
                if ($exists > 0) {
                    $duplicates++;
                    error_log("Duplicate phone in DB: {$telefon} (ImportId: {$importId})");
                    continue; // Bu numarayı atla
                }
                
                $adSoyad = $row[1] ?? '';
                $tutar = floatval($row[2] ?? 0);
                $metin1 = $row[3] ?? '';
                $metin2 = $row[4] ?? '';
                $metin3 = $row[5] ?? '';
                
                // Veritabanına ekle (Aktif = 0 - PASIF BAŞLAR, MANUEL AKTİF YAPILACAK!)
                $stmt = $this->pdo->prepare("
                    INSERT INTO " . table('CALLS') . " 
                    (ProjectId, ImportId, PhoneNumber, Adi, Tutar, Metin1, Metin2, Metin3, 
                     TemplateId, TemplateName,
                     Status, CallCount, TryCallCount, Aktif, RecordDate, CallTime)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 0, 3, 0, NOW(), NOW())
                ");
                
                $stmt->execute([
                    $projectId,
                    $importId,
                    $telefon,
                    $adSoyad,
                    $tutar,
                    $metin1,
                    $metin2,
                    $metin3,
                    $templateId,
                    $templateName
                ]);
                
                // Bu numarayı işaretle
                $seenPhones[$telefon] = true;
                $success++;
                
            } catch (Exception $e) {
                $errors++;
                error_log("Import error for row {$i}: " . $e->getMessage());
            }
        }
        
        // Duplicates varsa log'a yaz
        if ($duplicates > 0) {
            error_log("Import tamamlandı - {$duplicates} tekrar eden numara atlandı");
        }
        
        return [
            'count' => $success,
            'errors' => $errors,
            'duplicates' => $duplicates // Tekrar eden sayısını döndür
        ];
    }
    
    /**
     * Telefon numarasını formatla
     */
    private function formatPhone($phone)
    {
        // Sadece rakamları al
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Başındaki 0'ı kaldır
        if (substr($phone, 0, 1) === '0') {
            $phone = substr($phone, 1);
        }
        
        // 90 ile başlıyorsa onu da kaldır
        if (substr($phone, 0, 2) === '90') {
            $phone = substr($phone, 2);
        }
        
        return $phone;
    }
}
