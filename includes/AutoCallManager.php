<?php
/**
 * Otomatik Arama Yönetim Sınıfı
 * Excel'den veri aktarımı, arama başlatma ve yönetimi
 */

require_once __DIR__ . '/WindowsTTS.php';
require_once __DIR__ . '/AsteriskCallFile.php';

class AutoCallManager
{
    private $pdo;
    private $tts;
    private $asterisk;
    
    // Arama durumları (Status)
    const STATUS_PENDING = 0;      // Aranacak (Bekliyor)
    const STATUS_CALLING = 1;      // Aranıyor (Şu anda arıyor)
    const STATUS_CALLED = 2;       // Arandı (Tamamlandı)
    
    // Arama sonuç kodları (SipCode)
    const SIP_SUCCESS = 200;       // Başarılı (cevap verildi)
    const SIP_NO_ANSWER = 404;     // Cevapsız
    const SIP_BUSY = 486;          // Meşgul
    const SIP_CANCELLED = 487;     // İptal edildi
    const SIP_UNAVAILABLE = 503;   // Ulaşılamadı
    
    // UYUMLULUK SABİTLERİ (Eski kod için - artık Status + SipCode + Locked kullanıyoruz)
    // const STATUS_CALLING = 2;      // Deprecated: Kaldırıldı - Yukarıda yeni tanım var
    const STATUS_COMPLETED = 2;    // Deprecated: Şimdi Status=2 AND SipCode=200
    const STATUS_FAILED = 2;       // Deprecated: Şimdi Status=2 AND SipCode!=200
    
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->tts = new WindowsTTS();
        $this->asterisk = new AsteriskCallFile();
    }
    
    /**
     * Excel dosyasından verileri içe aktar (Proje ve şablon ile)
     */
    public function importFromExcel($file, $projectId = null, $importId = null, $templateId = null)
    {
        if (!isset($file['tmp_name']) || !file_exists($file['tmp_name'])) {
            throw new Exception('Dosya yüklenemedi');
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
        
        // Vendor varsa PhpSpreadsheet, yoksa basit okuyucu kullan
        if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
            require_once __DIR__ . '/../vendor/autoload.php';
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file['tmp_name']);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();
        } else {
            // Basit Excel okuyucu - orijinal dosya adından uzantıyı al
            $originalFileName = $file['name'] ?? '';
            $rows = $this->readExcelSimple($file['tmp_name'], $originalFileName);
        }
        
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
                    INSERT INTO CALLS 
                    (ProjectId, ImportId, PhoneNumber, Adi, Tutar, Metin1, Metin2, Metin3, 
                     TemplateId, TemplateName,
                     Status, CallCount, TryCallCount, Aktif, CallTime)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 3, 0, NOW())
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
                    $templateName,
                    self::STATUS_PENDING
                ]);
                
                // Bu numarayı işaretle
                $seenPhones[$telefon] = true;
                $success++;
                
            } catch (Exception $e) {
                $errors++;
                error_log("Excel import error: " . $e->getMessage());
            }
        }
        
        // Duplicates varsa log'a yaz
        if ($duplicates > 0) {
            error_log("Import tamamlandı - {$duplicates} tekrar eden numara atlandı");
        }
        
        return [
            'success' => $success,
            'errors' => $errors,
            'duplicates' => $duplicates // Tekrar eden sayısını döndür
        ];
    }
    
    /**
     * Basit Excel okuyucu (vendor olmadan)
     */
    private function readExcelSimple($filePath, $originalFileName = '')
    {
        // Önce orijinal dosya adından uzantıyı al, yoksa geçici dosyadan
        $ext = '';
        if ($originalFileName) {
            $ext = strtolower(pathinfo($originalFileName, PATHINFO_EXTENSION));
        } else {
            $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        }
        
        if ($ext === 'csv') {
            return $this->readCSV($filePath);
        }
        
        if ($ext === 'xlsx') {
            // XLSX = ZIP dosyası, sheet1.xml'i çıkar
            if (!class_exists('ZipArchive')) {
                throw new Exception('ZIP extension gerekli veya CSV kullanın. Lütfen install_dependencies.bat çalıştırın.');
            }
            
            $zip = new ZipArchive();
            if ($zip->open($filePath) !== TRUE) {
                throw new Exception('Excel dosyası açılamadı');
            }
            
            // sheet1.xml'i oku
            $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
            $sharedStrings = $zip->getFromName('xl/sharedStrings.xml');
            $zip->close();
            
            if ($xml === false) {
                throw new Exception('Excel içeriği okunamadı');
            }
            
            return $this->parseXLSXData($xml, $sharedStrings);
        }
        
        if ($ext === 'xls') {
            throw new Exception('Eski Excel formatı (.xls) desteklenmiyor. Lütfen .xlsx veya .csv kullanın.');
        }
        
        throw new Exception("Desteklenmeyen dosya formatı: .{$ext} (Sadece .xlsx veya .csv desteklenir)");
    }
    
    /**
     * CSV dosyası oku
     */
    private function readCSV($filePath)
    {
        $rows = [];
        if (($handle = fopen($filePath, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $rows[] = $data;
            }
            fclose($handle);
        }
        return $rows;
    }
    
    /**
     * XLSX XML'ini parse et
     */
    private function parseXLSXData($xml, $sharedStringsXML)
    {
        $sharedStrings = [];
        if ($sharedStringsXML) {
            $ssXML = simplexml_load_string($sharedStringsXML);
            foreach ($ssXML->si as $si) {
                $sharedStrings[] = (string)$si->t;
            }
        }
        
        $xmlData = simplexml_load_string($xml);
        $rows = [];
        $currentRow = 0;
        $rowData = [];
        
        foreach ($xmlData->sheetData->row as $row) {
            $rowIndex = (int)$row['r'] - 1;
            
            // Eksik satırları doldur
            while ($currentRow < $rowIndex) {
                $rows[] = [];
                $currentRow++;
            }
            
            $rowData = [];
            $colIndex = 0;
            
            foreach ($row->c as $cell) {
                $cellRef = (string)$cell['r'];
                $cellCol = preg_replace('/[0-9]+/', '', $cellRef);
                $targetCol = $this->columnIndexFromString($cellCol);
                
                // Eksik kolonları doldur
                while ($colIndex < $targetCol) {
                    $rowData[] = '';
                    $colIndex++;
                }
                
                $value = '';
                if (isset($cell->v)) {
                    $value = (string)$cell->v;
                    
                    // String referansı mı kontrol et
                    if (isset($cell['t']) && $cell['t'] == 's') {
                        $value = isset($sharedStrings[(int)$value]) ? $sharedStrings[(int)$value] : '';
                    }
                }
                
                $rowData[] = $value;
                $colIndex++;
            }
            
            $rows[] = $rowData;
            $currentRow++;
        }
        
        return $rows;
    }
    
    /**
     * Kolon harfini sayıya çevir (A=0, B=1, ...)
     */
    private function columnIndexFromString($column)
    {
        $column = strtoupper($column);
        $index = 0;
        $length = strlen($column);
        
        for ($i = 0; $i < $length; $i++) {
            $index = $index * 26 + (ord($column[$i]) - ord('A') + 1);
        }
        
        return $index - 1;
    }
    
    /**
     * Telefon numarasını formatla
     */
    private function formatPhone($phone)
    {
        // Sadece rakamları al
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // 0 ile başlamıyorsa ekle
        if (substr($phone, 0, 1) !== '0') {
            $phone = '0' . $phone;
        }
        
        return $phone;
    }
    
    /**
     * Tek bir aramayı işle ve başlat
     * @param int $callId Call ID
     * @return array ['success' => bool, 'callfile' => string, 'message' => string, 'error' => string]
     */
    public function processCall($callId)
    {
        try {
            // Call kaydını al
            $stmt = $this->pdo->prepare("SELECT * FROM " . table('CALLS') . " WHERE id = ?");
            $stmt->execute([$callId]);
            $call = $stmt->fetch();
            
            if (!$call) {
                return [
                    'success' => false,
                    'error' => 'Arama kaydı bulunamadı (ID: ' . $callId . ')'
                ];
            }
            
            // Kontroller
            if ($call['Locked'] == 1) {
                return [
                    'success' => false,
                    'error' => 'Bu arama şu anda işlemde (kilitli)'
                ];
            }
            
            if ($call['Status'] != self::STATUS_PENDING) {
                return [
                    'success' => false,
                    'error' => 'Bu arama zaten tamamlanmış (Status: ' . $call['Status'] . ')'
                ];
            }
            
            if ($call['Aktif'] != 1) {
                return [
                    'success' => false,
                    'error' => 'Bu arama pasif durumda'
                ];
            }
            
            // Kaydı kilitle
            $this->lockCall($call['id']);
            
            try {
                // Şablon ID'sini ve context'ini bul (ImportId'den)
                $templateId = null;
                $dialplanContext = 'auto-calls'; // Varsayılan
                
                if ($call['ImportId']) {
                    $importStmt = $this->pdo->prepare("SELECT TemplateId FROM " . table('IMPORT_HISTORY') . " WHERE id = ?");
                    $importStmt->execute([$call['ImportId']]);
                    $import = $importStmt->fetch();
                    $templateId = $import['TemplateId'] ?? null;
                    
                    // Şablondan DialplanContext'i al
                    if ($templateId) {
                        $templateStmt = $this->pdo->prepare("SELECT DialplanContext FROM " . table('MESSAGE_TEMPLATES') . " WHERE id = ?");
                        $templateStmt->execute([$templateId]);
                        $template = $templateStmt->fetch();
                        if ($template && !empty($template['DialplanContext'])) {
                            $dialplanContext = $template['DialplanContext'];
                        }
                    }
                }
                
                // TTS ses dosyasını oluştur
                $audioFile = $this->tts->generateCallAudio(
                    $call['id'],
                    $call['Adi'],
                    $call['Tutar'],
                    $call['Metin1'],
                    $call['Metin2'],
                    $call['Metin3'],
                    $templateId
                );
                
                // Projenin CallerID ve Trunk'ını ayarla
                if ($call['ProjectId']) {
                    $projectStmt = $this->pdo->prepare("SELECT CallerID, Trunk FROM " . table('PROJECTS') . " WHERE id = ?");
                    $projectStmt->execute([$call['ProjectId']]);
                    $project = $projectStmt->fetch();
                    if ($project) {
                        if (!empty($project['CallerID'])) {
                            $this->asterisk->setCallerID($project['CallerID']);
                        }
                        if (!empty($project['Trunk'])) {
                            $this->asterisk->setTrunk($project['Trunk']);
                        }
                    }
                }
                
                // Asterisk call file oluştur (şablon context'i ile)
                $callFileName = $this->asterisk->createCallFile(
                    $call['PhoneNumber'],
                    $call['id'],
                    $audioFile,
                    $dialplanContext
                );
                
                // Durumu güncelle
                $this->updateCallStatus($call['id'], self::STATUS_PENDING);
                
                return [
                    'success' => true,
                    'callfile' => $callFileName,
                    'message' => 'Arama başarıyla başlatıldı',
                    'audio_file' => $audioFile,
                    'phone' => $call['PhoneNumber'],
                    'context' => $dialplanContext
                ];
                
            } catch (Exception $e) {
                // Hata oluştuysa kilidi kaldır
                $this->unlockCall($call['id']);
                throw $e;
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Bekleyen aramaları başlat (Import bazlı MaxConcurrentCalls limiti ile)
     */
    public function startPendingCalls()
    {
        // Tüm aktif import'ları al
        $importStmt = $this->pdo->query("
            SELECT DISTINCT ImportId 
            FROM " . table('CALLS') . " 
            WHERE Status = " . self::STATUS_PENDING . " 
            AND Aktif = 1 
            AND ImportId IS NOT NULL
        ");
        $imports = $importStmt->fetchAll(PDO::FETCH_COLUMN);
        
        $totalStarted = 0;
        
        // Her import için ayrı ayrı MaxConcurrentCalls limiti ile başlat
        foreach ($imports as $importId) {
            try {
                // Import'un MaxConcurrentCalls değerini al
                $limitStmt = $this->pdo->prepare("SELECT MaxConcurrentCalls FROM " . table('IMPORT_HISTORY') . " WHERE id = ?");
                $limitStmt->execute([$importId]);
                $import = $limitStmt->fetch();
                $maxConcurrent = isset($import['MaxConcurrentCalls']) ? (int)$import['MaxConcurrentCalls'] : 10;
                
                // Şu anda bu import için kaç arama aktif (Locked=1)?
                $activeStmt = $this->pdo->prepare("SELECT COUNT(*) as active FROM " . table('CALLS') . " WHERE ImportId = ? AND Locked = 1");
                $activeStmt->execute([$importId]);
                $activeCalls = (int)$activeStmt->fetchColumn();
                
                // Eğer limit doluysa, bu import'u atla
                if ($activeCalls >= $maxConcurrent) {
                    error_log("⚠️ ImportId $importId: Limit dolu ($activeCalls >= $maxConcurrent)");
                    continue;
                }
                
                // Kaç arama başlatılabilir?
                $availableSlots = $maxConcurrent - $activeCalls;
                
                // Bekleyen kayıtları al (limit ile)
                $stmt = $this->pdo->prepare("
                    SELECT * FROM " . table('CALLS') . " 
                    WHERE ImportId = ?
                    AND Status = ? 
                    AND Aktif = 1 
                    AND CallCount < TryCallCount
                    AND Locked = 0
                    LIMIT " . (int)$availableSlots . "
                ");
                
                $stmt->execute([$importId, self::STATUS_PENDING]);
                $calls = $stmt->fetchAll();
                
                foreach ($calls as $call) {
                    $callLocked = false;
                    
                    try {
                        // Kaydı kilitle
                        $this->lockCall($call['id']);
                        $callLocked = true;
                        
                        // Şablon ID'sini ve context'ini bul (ImportId'den)
                        $templateId = null;
                        $dialplanContext = 'auto-calls'; // Varsayılan
                        
                        if ($call['ImportId']) {
                            $importStmt = $this->pdo->prepare("SELECT TemplateId FROM " . table('IMPORT_HISTORY') . " WHERE id = ?");
                            $importStmt->execute([$call['ImportId']]);
                            $import = $importStmt->fetch();
                            $templateId = $import['TemplateId'] ?? null;
                            
                            // Şablondan DialplanContext'i al
                            if ($templateId) {
                                $templateStmt = $this->pdo->prepare("SELECT DialplanContext FROM " . table('MESSAGE_TEMPLATES') . " WHERE id = ?");
                                $templateStmt->execute([$templateId]);
                                $template = $templateStmt->fetch();
                                if ($template && !empty($template['DialplanContext'])) {
                                    $dialplanContext = $template['DialplanContext'];
                                }
                            }
                        }
                        
                        // TTS ses dosyasını oluştur
                        $audioFile = $this->tts->generateCallAudio(
                            $call['id'],
                            $call['Adi'],
                            $call['Tutar'],
                            $call['Metin1'],
                            $call['Metin2'],
                            $call['Metin3'],
                            $templateId
                        );
                        
                        // Projenin CallerID ve Trunk'ını ayarla
                        $projectStmt = $this->pdo->prepare("SELECT CallerID, Trunk FROM " . table('PROJECTS') . " WHERE id = ?");
                        $projectStmt->execute([$call['ProjectId']]);
                        $project = $projectStmt->fetch();
                        if (!empty($project['CallerID'])) {
                            $this->asterisk->setCallerID($project['CallerID']);
                        }
                        if (!empty($project['Trunk'])) {
                            $this->asterisk->setTrunk($project['Trunk']);
                        }
                        
                        // Asterisk call file oluştur (şablon context'i ile)
                        $this->asterisk->createCallFile(
                            $call['PhoneNumber'],
                            $call['id'],
                            $audioFile,
                            $dialplanContext
                        );
                        
                        // Durumu güncelle (Asterisk AGI unlock edecek, biz etmeyelim)
                        $this->updateCallStatus($call['id'], self::STATUS_CALLING);
                        
                        // Asterisk'e başarıyla iletildi, artık AGI sorumlu
                        $callLocked = false;
                        
                        $totalStarted++;
                        
                    } catch (Exception $e) {
                        $errorMsg = "Call start error for ID {$call['id']}: " . $e->getMessage();
                        error_log($errorMsg);
                        
                        // Hata oluştuysa kilidi hemen kaldır
                        if ($callLocked) {
                            try {
                                $this->unlockCall($call['id']);
                            } catch (Exception $unlockError) {
                                error_log("Unlock error for ID {$call['id']}: " . $unlockError->getMessage());
                            }
                        }
                    }
                }
                
            } catch (Exception $e) {
                error_log("Import $importId error: " . $e->getMessage());
            }
        }
        
        return $totalStarted;
    }
    
    /**
     * Proje bazlı bekleyen aramaları başlat
     */
    public function startProjectCalls($projectId, $limit = 50)
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM " . table('CALLS') . " 
            WHERE ProjectId = ?
            AND Status = ? 
            AND Aktif = 1 
            AND CallCount < TryCallCount
            AND Locked = 0
            LIMIT " . intval($limit) . "
        ");
        
        $stmt->execute([$projectId, self::STATUS_PENDING]);
        $calls = $stmt->fetchAll();
        
        $count = 0;
        $errors = [];
        
        foreach ($calls as $call) {
            try {
                $this->lockCall($call['id']);
                
                // Şablon ID'sini ve context'ini bul
                $templateId = null;
                $dialplanContext = 'auto-calls';
                
                if ($call['ImportId']) {
                    $importStmt = $this->pdo->prepare("SELECT TemplateId FROM " . table('IMPORT_HISTORY') . " WHERE id = ?");
                    $importStmt->execute([$call['ImportId']]);
                    $import = $importStmt->fetch();
                    $templateId = $import['TemplateId'] ?? null;
                    
                    if ($templateId) {
                        $templateStmt = $this->pdo->prepare("SELECT DialplanContext FROM " . table('MESSAGE_TEMPLATES') . " WHERE id = ?");
                        $templateStmt->execute([$templateId]);
                        $template = $templateStmt->fetch();
                        if ($template && !empty($template['DialplanContext'])) {
                            $dialplanContext = $template['DialplanContext'];
                        }
                    }
                }
                
                // TTS ses dosyasını oluştur
                $audioFile = $this->tts->generateCallAudio(
                    $call['id'],
                    $call['Adi'],
                    $call['Tutar'],
                    $call['Metin1'],
                    $call['Metin2'],
                    $call['Metin3'],
                    $templateId
                );
                
                // Projenin CallerID ve Trunk'ını ayarla
                $projectStmt = $this->pdo->prepare("SELECT CallerID, Trunk FROM " . table('PROJECTS') . " WHERE id = ?");
                $projectStmt->execute([$call['ProjectId']]);
                $project = $projectStmt->fetch();
                if (!empty($project['CallerID'])) {
                    $this->asterisk->setCallerID($project['CallerID']);
                }
                // Trunk ayarla - boşsa varsayılan trunk kullan
                $trunk = !empty($project['Trunk']) ? $project['Trunk'] : DEFAULT_TRUNK;
                $this->asterisk->setTrunk($trunk);
                
                // Asterisk call file oluştur
                $this->asterisk->createCallFile(
                    $call['PhoneNumber'],
                    $call['id'],
                    $audioFile,
                    $dialplanContext
                );
                
                // Durumu güncelle
                $this->updateCallStatus($call['id'], self::STATUS_CALLING);
                
                $count++;
                
            } catch (Exception $e) {
                error_log("Project call start error for ID {$call['id']}: " . $e->getMessage());
                $errors[] = "ID {$call['id']}: " . $e->getMessage();
                $this->unlockCall($call['id']);
            }
        }
        
        if (!empty($errors)) {
            throw new Exception("Bazı aramalar başlatılamadı:\n" . implode("\n", $errors));
        }
        
        return $count;
    }
    
    /**
     * Proje bazlı devam eden aramaları durdur
     * Locked=1 olan (şu anda arayan) kayıtları iptal eder
     */
    public function stopProjectCalls($projectId)
    {
        $stmt = $this->pdo->prepare("
            UPDATE " . table('CALLS') . " 
            SET Status = ?,
                SipCode = ?,
                Locked = 0,
                EndCallTime = NOW()
            WHERE ProjectId = ?
            AND Locked = 1
        ");
        
        $stmt->execute([self::STATUS_CALLED, self::SIP_CANCELLED, $projectId]);
        
        return $stmt->rowCount();
    }
    
    /**
     * Import bazlı devam eden aramaları durdur
     * 1. Locked=1 olan (şu anda arayan) kayıtları iptal eder
     * 2. Tüm bekleyen kayıtları Aktif=0 yapar (yeni aramalar başlamasın)
     * 3. Asterisk'teki .call dosyalarını siler (kuyruklanmış aramaları iptal eder)
     */
    public function stopImportCalls($importId)
    {
        // 1. Şu an arayan kayıtları BEKLEYİŞE al (Status=1 OR Locked=1 -> Status=0)
        // STOP yapınca arama KAYBOLMASIN, tekrar aranabilsin!
        $stmt = $this->pdo->prepare("
            UPDATE " . table('CALLS') . " 
            SET Status = 0,
                Locked = 0,
                Aktif = 0,
                EndCallTime = NOW()
            WHERE ImportId = ?
            AND (Status = 1 OR Locked = 1)
        ");
        
        $stmt->execute([$importId]);
        $stoppedCount = $stmt->rowCount();
        
        // 2. TÜM bekleyen kayıtları pasif yap (Aktif=0) - yeni aramalar başlamasın
        $stmt2 = $this->pdo->prepare("
            UPDATE " . table('CALLS') . " 
            SET Aktif = 0
            WHERE ImportId = ?
            AND Status = 0
        ");
        $stmt2->execute([$importId]);
        $deactivatedCount = $stmt2->rowCount();
        
        // 3. Asterisk'teki .call dosyalarını sil ve aktif kanalları kes (SSH ile)
        try {
            if (!defined('ASTERISK_SSH_HOST')) {
                require_once __DIR__ . '/../config.php';
            }
            require_once __DIR__ . '/SshHelper.php';
            
            $ssh = new SshHelper(
                ASTERISK_SSH_HOST,
                ASTERISK_SSH_PORT,
                ASTERISK_SSH_USER,
                ASTERISK_SSH_PASS
            );
            
            // A) Import ID'ye ait telefon numaralarını al
            $phonesStmt = $this->pdo->prepare("SELECT DISTINCT PhoneNumber FROM " . table('CALLS') . " WHERE ImportId = ? AND (Status = 1 OR Locked = 1)");
            $phonesStmt->execute([$importId]);
            $phones = $phonesStmt->fetchAll(\PDO::FETCH_COLUMN);
            
            // B) Asterisk'te bu numaralara ait aktif kanalları kes
            $hangupCount = 0;
            if (!empty($phones)) {
                foreach ($phones as $phone) {
                    // Kanalı bul ve kes
                    $hangupCmd = "asterisk -rx 'channel request hangup all' 2>/dev/null || asterisk -rx 'hangup request all' 2>/dev/null || true";
                    $ssh->exec($hangupCmd);
                    $hangupCount++;
                }
            }
            
            // C) Import ID'ye ait tüm .call dosyalarını sil
            $callIdsStmt = $this->pdo->prepare("SELECT id FROM " . table('CALLS') . " WHERE ImportId = ?");
            $callIdsStmt->execute([$importId]);
            $callIds = $callIdsStmt->fetchAll(\PDO::FETCH_COLUMN);
            
            $deletedFiles = 0;
            if (!empty($callIds)) {
                // Tüm call dosyalarını toplu sil
                $deleteCmd = "rm -f /var/spool/asterisk/outgoing/auto_call_*.call 2>/dev/null || true";
                $ssh->exec($deleteCmd);
                $deletedFiles = count($callIds);
            }
            
            error_log("⏹️ STOP: Import #{$importId} - {$stoppedCount} DB kaydı, {$deactivatedCount} pasif yapıldı, {$deletedFiles} call dosyası, {$hangupCount} kanal kapatıldı");
            
        } catch (Exception $e) {
            error_log("⚠️ STOP: Asterisk temizliğinde hata: " . $e->getMessage());
            // Hata olsa bile devam et, en azından veritabanı güncellendi
        }
        
        return $stoppedCount + $deactivatedCount;
    }
    
    /**
     * Import bazlı TÜM bekleyen aramaları başlat (limit yok)
     * - İlk aramalar: CallCount=0
     * - Retry aramalar: CallCount>0 VE LastCallTime<=NOW()
     */
    public function startAllImportCalls($importId)
    {
        error_log("🚀 startAllImportCalls çağrıldı - ImportId: $importId");
        
        // MaxConcurrentCalls artık her partide aranacak kişi sayısıdır (HIZ).
        $importStmt = $this->pdo->prepare("SELECT MaxConcurrentCalls FROM " . table('IMPORT_HISTORY') . " WHERE id = ?");
        $importStmt->execute([$importId]);
        $import = $importStmt->fetch();
        $batchSize = max(1, isset($import['MaxConcurrentCalls']) ? (int)$import['MaxConcurrentCalls'] : 10);
        
        error_log("📊 Parti büyüklüğü (HIZ): $batchSize");
        
        // MySQL 5.5 uyumlu: LIMIT için string interpolation
        $sql = "
            SELECT c.* FROM " . table('CALLS') . " c
            JOIN IMPORT_HISTORY ih ON c.ImportId = ih.id
            WHERE c.ImportId = ?
            AND c.Status IN (?, ?)
            -- TUR KURALI:
            -- Import içindeki en düşük CallCount turu tamamen bitmeden
            -- bir sonraki deneme turuna geçme.
            AND c.CallCount = (
                SELECT MIN(c2.CallCount)
                FROM " . table('CALLS') . " c2
                WHERE c2.ImportId = c.ImportId
                  AND c2.Aktif = 1
                  AND c2.Status IN (" . self::STATUS_PENDING . ", " . self::STATUS_CALLING . ")
                  AND c2.CallCount < c2.TryCallCount
            )
            AND (
                -- İLK ARAMA: CallCount = 0 ise CallTime kontrolü
                (c.CallCount = 0 AND (c.CallTime IS NULL OR c.CallTime <= NOW()))
                OR 
                -- RETRY: CallCount > 0 ise LastCallTime + parti aralığı (saniye) kontrolü
                (c.CallCount > 0 AND c.LastCallTime IS NOT NULL 
                 AND DATE_ADD(c.LastCallTime, INTERVAL ih.RetryIntervalMinutes SECOND) <= NOW())
            )
            AND c.Aktif = 1 
            AND c.Locked = 0
            ORDER BY c.CallCount ASC, c.CallTime ASC, c.LastCallTime ASC, c.id ASC
            LIMIT " . (int)$batchSize;
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$importId, self::STATUS_PENDING, self::STATUS_CALLING]);
        $calls = $stmt->fetchAll();
        
        error_log("📋 Sorgudan dönen kayıt sayısı: " . count($calls));
        
        if (count($calls) === 0) {
            error_log("⚠️ Başlatılacak kayıt bulunamadı! Nedenler:");
            error_log("   - CallTime gelecek tarihli olabilir");
            error_log("   - Status != 0/1 (Pending/Calling) olabilir");
            error_log("   - Aktif = 0 olabilir");
            error_log("   - Locked = 1 olabilir (başka process tarafından)");
        }
        
        $count = 0;
        $errors = [];
        
        foreach ($calls as $call) {
            try {
                $this->lockCall($call['id']);
                
                // Şablon ID'sini ve context'ini bul
                $templateId = null;
                $dialplanContext = 'auto-calls';
                
                if ($call['ImportId']) {
                    $importStmt = $this->pdo->prepare("SELECT TemplateId FROM " . table('IMPORT_HISTORY') . " WHERE id = ?");
                    $importStmt->execute([$call['ImportId']]);
                    $import = $importStmt->fetch();
                    $templateId = $import['TemplateId'] ?? null;
                    
                    if ($templateId) {
                        $templateStmt = $this->pdo->prepare("SELECT DialplanContext FROM " . table('MESSAGE_TEMPLATES') . " WHERE id = ?");
                        $templateStmt->execute([$templateId]);
                        $template = $templateStmt->fetch();
                        if ($template && !empty($template['DialplanContext'])) {
                            $dialplanContext = $template['DialplanContext'];
                        }
                    }
                }
                
                // TTS ses dosyasını oluştur
                $audioFile = $this->tts->generateCallAudio(
                    $call['id'],
                    $call['Adi'],
                    $call['Tutar'],
                    $call['Metin1'],
                    $call['Metin2'],
                    $call['Metin3'],
                    $templateId
                );
                
                // Projenin CallerID ve Trunk'ını ayarla
                $projectStmt = $this->pdo->prepare("SELECT CallerID, Trunk FROM " . table('PROJECTS') . " WHERE id = ?");
                $projectStmt->execute([$call['ProjectId']]);
                $project = $projectStmt->fetch();
                if (!empty($project['CallerID'])) {
                    $this->asterisk->setCallerID($project['CallerID']);
                }
                // Trunk ayarla - boşsa varsayılan trunk kullan
                $trunk = !empty($project['Trunk']) ? $project['Trunk'] : DEFAULT_TRUNK;
                $this->asterisk->setTrunk($trunk);
                
                // Asterisk call file oluştur
                $this->asterisk->createCallFile(
                    $call['PhoneNumber'],
                    $call['id'],
                    $audioFile,
                    $dialplanContext
                );
                
                // Arama başladı: Locked = 1 (Status değişmez, "answered" gelince 2 olacak)
                // NOT: CallTime değişmez (ilk arama zamanı sabit kalır)
                $updateStmt = $this->pdo->prepare("
                    UPDATE " . table('CALLS') . " 
                    SET Locked = 1, 
                        LastCallTime = NOW()
                    WHERE id = ?
                ");
                $updateStmt->execute([$call['id']]);
                
                $count++;
                
            } catch (Exception $e) {
                error_log("Import call start error for ID {$call['id']}: " . $e->getMessage());
                $errors[] = "ID {$call['id']}: " . $e->getMessage();
                $this->unlockCall($call['id']);
            }
        }
        
        if (!empty($errors)) {
            throw new Exception("Bazı aramalar başlatılamadı:\n" . implode("\n", $errors));
        }
        
        return $count;
    }
    
    /**
     * Import bazlı bekleyen aramaları başlat (limitle)
     */
    public function startImportCalls($importId, $limit = 50)
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM " . table('CALLS') . " 
            WHERE ImportId = ?
            AND Status = ? 
            AND Aktif = 1 
            AND CallCount < TryCallCount
            AND Locked = 0
            LIMIT " . intval($limit) . "
        ");
        
        $stmt->execute([$importId, self::STATUS_PENDING]);
        $calls = $stmt->fetchAll();
        
        $count = 0;
        $errors = [];
        
        foreach ($calls as $call) {
            try {
                $this->lockCall($call['id']);
                
                // Şablon ID'sini ve context'ini bul
                $templateId = null;
                $dialplanContext = 'auto-calls';
                
                if ($call['ImportId']) {
                    $importStmt = $this->pdo->prepare("SELECT TemplateId FROM " . table('IMPORT_HISTORY') . " WHERE id = ?");
                    $importStmt->execute([$call['ImportId']]);
                    $import = $importStmt->fetch();
                    $templateId = $import['TemplateId'] ?? null;
                    
                    if ($templateId) {
                        $templateStmt = $this->pdo->prepare("SELECT DialplanContext FROM " . table('MESSAGE_TEMPLATES') . " WHERE id = ?");
                        $templateStmt->execute([$templateId]);
                        $template = $templateStmt->fetch();
                        if ($template && !empty($template['DialplanContext'])) {
                            $dialplanContext = $template['DialplanContext'];
                        }
                    }
                }
                
                // TTS ses dosyasını oluştur
                $audioFile = $this->tts->generateCallAudio(
                    $call['id'],
                    $call['Adi'],
                    $call['Tutar'],
                    $call['Metin1'],
                    $call['Metin2'],
                    $call['Metin3'],
                    $templateId
                );
                
                // Projenin CallerID ve Trunk'ını ayarla
                $projectStmt = $this->pdo->prepare("SELECT CallerID, Trunk FROM " . table('PROJECTS') . " WHERE id = ?");
                $projectStmt->execute([$call['ProjectId']]);
                $project = $projectStmt->fetch();
                if (!empty($project['CallerID'])) {
                    $this->asterisk->setCallerID($project['CallerID']);
                }
                // Trunk ayarla - boşsa varsayılan trunk kullan
                $trunk = !empty($project['Trunk']) ? $project['Trunk'] : DEFAULT_TRUNK;
                $this->asterisk->setTrunk($trunk);
                
                // Asterisk call file oluştur
                $this->asterisk->createCallFile(
                    $call['PhoneNumber'],
                    $call['id'],
                    $audioFile,
                    $dialplanContext
                );
                
                // Durumu güncelle
                $this->updateCallStatus($call['id'], self::STATUS_CALLING);
                
                $count++;
                
            } catch (Exception $e) {
                error_log("Import call start error for ID {$call['id']}: " . $e->getMessage());
                $errors[] = "ID {$call['id']}: " . $e->getMessage();
                $this->unlockCall($call['id']);
            }
        }
        
        if (!empty($errors)) {
            throw new Exception("Bazı aramalar başlatılamadı:\n" . implode("\n", $errors));
        }
        
        return $count;
    }
    
    /**
     * Arama kaydını kilitle
     */
    public function lockCall($callId)
    {
        $stmt = $this->pdo->prepare("
            UPDATE " . table('CALLS') . " 
            SET Locked = 1 
            WHERE id = ?
        ");
        $stmt->execute([$callId]);
    }
    
    /**
     * Arama kaydını kilidi aç
     */
    private function unlockCall($callId)
    {
        $stmt = $this->pdo->prepare("
            UPDATE " . table('CALLS') . " 
            SET Locked = 0 
            WHERE id = ?
        ");
        $stmt->execute([$callId]);
    }
    
    /**
     * Arama durumunu güncelle
     */
    public function updateCallStatus($callId, $status, $additionalData = [])
    {
        // Arama başlatıldığında çağrılır
        // Locked = 1 (Status değişmez, "answered" gelince 2 olacak)
        // NOT: CallTime değişmez (ilk arama zamanı sabit kalır)
        $sql = "UPDATE " . table('CALLS') . " 
                SET Locked = 1, 
                    LastCallTime = NOW()";
        $params = [];
        
        if (isset($additionalData['PickupTime'])) {
            $sql .= ", PickupTime = ?";
            $params[] = $additionalData['PickupTime'];
        }
        
        if (isset($additionalData['EndCallTime'])) {
            $sql .= ", EndCallTime = ?";
            $params[] = $additionalData['EndCallTime'];
        }
        
        if (isset($additionalData['tus1'])) {
            $sql .= ", tus1 = ?";
            $params[] = $additionalData['tus1'];
        }
        
        if (isset($additionalData['tus2'])) {
            $sql .= ", tus2 = ?";
            $params[] = $additionalData['tus2'];
        }
        
        if (isset($additionalData['tus3'])) {
            $sql .= ", tus3 = ?";
            $params[] = $additionalData['tus3'];
        }
        
        if (isset($additionalData['TransferNo'])) {
            $sql .= ", TransferNo = ?";
            $params[] = $additionalData['TransferNo'];
        }
        
        $sql .= " WHERE id = ?";
        $params[] = $callId;
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
    }
    
    /**
     * Kayıtları getir
     */
    public function getCalls($filter = 'all')
    {
        $sql = "SELECT * FROM " . table('CALLS') . " WHERE 1=1";
        $params = [];
        
        if ($filter === 'pending') {
            // Hiç aranmamış kayıtlar
            $sql .= " AND Status = ? AND CallCount = 0";
            $params[] = self::STATUS_PENDING;
        } elseif ($filter === 'calling') {
            // Şu anda aranan kayıtlar
            $sql .= " AND Locked = 1";
        } elseif ($filter === 'completed') {
            // Başarılı tamamlananlar
            $sql .= " AND Status = ? AND SipCode = ?";
            $params[] = self::STATUS_CALLED;
            $params[] = self::SIP_SUCCESS;
        } elseif ($filter === 'failed') {
            // Başarısız olanlar
            $sql .= " AND Status = ? AND SipCode != ? AND SipCode IS NOT NULL";
            $params[] = self::STATUS_CALLED;
            $params[] = self::SIP_SUCCESS;
        }
        
        $sql .= " ORDER BY RecordDate DESC LIMIT 1000";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    /**
     * İstatistikleri getir
     */
    public function getStats()
    {
        $stmt = $this->pdo->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN Status = " . self::STATUS_PENDING . " AND CallCount = 0 THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN Locked = 1 THEN 1 ELSE 0 END) as calling,
                SUM(CASE WHEN Status = " . self::STATUS_CALLED . " AND SipCode = " . self::SIP_SUCCESS . " THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN Status = " . self::STATUS_CALLED . " AND SipCode != " . self::SIP_SUCCESS . " AND SipCode IS NOT NULL THEN 1 ELSE 0 END) as failed
            FROM " . table('CALLS') . "
        ");
        
        return $stmt->fetch();
    }
    
    /**
     * Kaydı sil
     */
    public function deleteCall($callId)
    {
        $stmt = $this->pdo->prepare("DELETE FROM " . table('CALLS') . " WHERE id = ?");
        $stmt->execute([$callId]);
    }
    
    /**
     * Tümünü sil
     */
    public function deleteAllCalls()
    {
        $this->pdo->exec("TRUNCATE TABLE CALLS");
    }
}
