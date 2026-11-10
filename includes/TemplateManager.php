<?php
/**
 * Mesaj Şablonu Yönetim Sınıfı
 */

class TemplateManager
{
    private $pdo;
    
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }
    
    /**
     * Tüm şablonları getir
     */
    public function getTemplates($activeOnly = true)
    {
        $sql = "SELECT * FROM " . table('MESSAGE_TEMPLATES') . " WHERE 1=1";
        
        if ($activeOnly) {
            $sql .= " AND Active = 1";
        }
        
        $sql .= " ORDER BY CreatedDate DESC";
        
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }
    
    /**
     * Şablon getir
     */
    public function getTemplate($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM " . table('MESSAGE_TEMPLATES') . " WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Şablon oluştur
     */
    public function createTemplate($name, $description, $content, $actions = [])
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO " . table('MESSAGE_TEMPLATES') . " 
            (TemplateName, Description, TemplateContent, Key1Action, Key1TransferNumber, Key1AudioFile,
             Key2Action, Key2TransferNumber, Key2AudioFile, InvalidKeyAction, InvalidKeyAudioFile,
             TimeoutAction, TimeoutAudioFile, TimeoutSeconds, DialplanContext, CreatedDate, UpdatedDate)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        
        $contentJson = is_array($content) ? json_encode($content, JSON_UNESCAPED_UNICODE) : $content;
        
        $templateId = $this->getNextId();
        $dialplanContext = 'auto-calls-template-' . $templateId;
        
        $stmt->execute([
            $name, 
            $description, 
            $contentJson,
            $actions['key1_action'] ?? 'replay',
            $actions['key1_transfer_number'] ?? null,
            $actions['key1_audio_file'] ?? 'otomatik/transfer',
            $actions['key2_action'] ?? 'transfer',
            $actions['key2_transfer_number'] ?? 'TAHSILAT',
            $actions['key2_audio_file'] ?? 'otomatik/transfer',
            $actions['invalid_key_action'] ?? 'use_key1',
            $actions['invalid_key_audio_file'] ?? 'otomatik/invalid',
            $actions['timeout_action'] ?? 'use_key1_and_hangup',
            $actions['timeout_audio_file'] ?? 'otomatik/goodbye',
            $actions['timeout_seconds'] ?? 5,
            $dialplanContext
        ]);
        
        $id = $this->pdo->lastInsertId();
        
        // Asterisk dialplan oluştur
        $this->generateDialplanForTemplate($id);
        
        return $id;
    }
    
    /**
     * Şablon güncelle
     */
    public function updateTemplate($id, $name, $description, $content, $actions = [])
    {
        $stmt = $this->pdo->prepare("
            UPDATE " . table('MESSAGE_TEMPLATES') . " 
            SET TemplateName = ?, Description = ?, TemplateContent = ?, 
                Key1Action = ?, Key1TransferNumber = ?, Key1AudioFile = ?,
                Key2Action = ?, Key2TransferNumber = ?, Key2AudioFile = ?,
                InvalidKeyAction = ?, InvalidKeyAudioFile = ?,
                TimeoutAction = ?, TimeoutAudioFile = ?, TimeoutSeconds = ?,
                UpdatedDate = NOW()
            WHERE id = ?
        ");
        
        $contentJson = is_array($content) ? json_encode($content, JSON_UNESCAPED_UNICODE) : $content;
        
        $result = $stmt->execute([
            $name, 
            $description, 
            $contentJson,
            $actions['key1_action'] ?? 'replay',
            $actions['key1_transfer_number'] ?? null,
            $actions['key1_audio_file'] ?? 'otomatik/transfer',
            $actions['key2_action'] ?? 'transfer',
            $actions['key2_transfer_number'] ?? 'TAHSILAT',
            $actions['key2_audio_file'] ?? 'otomatik/transfer',
            $actions['invalid_key_action'] ?? 'use_key1',
            $actions['invalid_key_audio_file'] ?? 'otomatik/invalid',
            $actions['timeout_action'] ?? 'use_key1_and_hangup',
            $actions['timeout_audio_file'] ?? 'otomatik/goodbye',
            $actions['timeout_seconds'] ?? 5,
            $id
        ]);
        
        // Asterisk dialplan oluştur
        $this->generateDialplanForTemplate($id);
        
        return $result;
    }
    
    /**
     * Sonraki ID'yi al
     */
    private function getNextId()
    {
        $stmt = $this->pdo->query("SELECT MAX(id) + 1 as next_id FROM " . table('MESSAGE_TEMPLATES') . "");
        $result = $stmt->fetch();
        return $result['next_id'] ?? 1;
    }
    
    /**
     * Şablon sil
     */
    public function deleteTemplate($id)
    {
        // Varsayılan şablon kontrolü (ID = 1)
        if ($id == 1) {
            throw new Exception("Varsayılan şablon silinemez!");
        }
        
        // Asterisk'teki dialplan dosyasını sil
        try {
            // Config.php'yi yükle (eğer yüklenmemişse)
            if (!defined('ASTERISK_SSH_HOST')) {
                require_once __DIR__ . '/../config.php';
            }
            
            require_once __DIR__ . '/SshHelper.php';
            
            $sshHelper = new SshHelper(
                ASTERISK_SSH_HOST,
                ASTERISK_SSH_PORT,
                ASTERISK_SSH_USER,
                ASTERISK_SSH_PASS
            );
            
            $remoteFile = '/etc/asterisk/extensions_template_' . $id . '.conf';
            $includeStatement = "#include extensions_template_{$id}.conf";
            
            // 1. Remote dialplan dosyasını sil
            $sshHelper->exec("rm -f {$remoteFile}");
            
            // 2. extensions_custom.conf'tan include satırını kaldır
            $sshHelper->exec("sed -i '/{$includeStatement}/d' /etc/asterisk/extensions_custom.conf");
            
            // 3. Dialplan'ı reload et
            $sshHelper->exec('asterisk -rx "dialplan reload"');
            
            // 4. Local dosyayı sil
            $localPath = __DIR__ . '/../asterisk/dialplan/extensions_template_' . $id . '.conf';
            if (file_exists($localPath)) {
                @unlink($localPath);
            }
            
        } catch (Exception $e) {
            error_log("Dialplan silme hatası (Template {$id}): " . $e->getMessage());
            // Hata olsa bile veritabanından silmeye devam et
        }
        
        // Veritabanından sil
        $stmt = $this->pdo->prepare("DELETE FROM " . table('MESSAGE_TEMPLATES') . " WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * Şablonu parse et ve mesaj oluştur
     */
    public function generateMessage($templateId, $data)
    {
        $template = $this->getTemplate($templateId);
        
        if (!$template) {
            throw new Exception("Şablon bulunamadı");
        }
        
        $content = json_decode($template['TemplateContent'], true);
        $message = '';
        
        foreach ($content as $item) {
            if ($item['type'] === 'text') {
                $message .= $item['value'] . ' ';
            } elseif ($item['type'] === 'field') {
                $value = $data[$item['value']] ?? '';
                if (!empty($value)) {
                    $message .= $value . ' ';
                }
            }
        }
        
        return trim($message);
    }
    
    /**
     * Şablon için Asterisk dialplan oluştur
     */
    public function generateDialplanForTemplate($templateId)
    {
        $template = $this->getTemplate($templateId);
        
        if (!$template) {
            throw new Exception("Şablon bulunamadı");
        }
        
        // Config.php'yi yükle (eğer yüklenmemişse)
        if (!defined('ASTERISK_SSH_HOST')) {
            require_once __DIR__ . '/../config.php';
        }
        
        require_once __DIR__ . '/SshHelper.php';
        
        $sshHelper = new SshHelper(
            ASTERISK_SSH_HOST,
            ASTERISK_SSH_PORT,
            ASTERISK_SSH_USER,
            ASTERISK_SSH_PASS
        );
        
        $context = $template['DialplanContext'] ?? 'auto-calls-template-' . $templateId;
        $timeoutSeconds = $template['TimeoutSeconds'] ?? 5;
        
        $dialplan = "; ============================================\n";
        $dialplan .= "; Şablon: " . $template['TemplateName'] . "\n";
        $dialplan .= "; Context: " . $context . "\n";
        $dialplan .= "; ============================================\n\n";
        $dialplan .= "[" . $context . "]\n";
        $dialplan .= "; Otomatik arama başladığında buraya düşer\n\n";
        
        // Başlangıç - TÜM MANTIK BU EXTENSION İÇİNDE (CDR.dst korunması için)
        $dialplan .= "exten => s,1,Set(CHANNEL(hangup_handler_push)={$context},h,1)\n";
        $dialplan .= "    same => n,NoOp(=== Otomatik Arama Başladı - {$template['TemplateName']} ===)\n";
        $dialplan .= "    same => n,NoOp(Call ID: \${CALL_ID})\n";
        $dialplan .= "    same => n,NoOp(Phone: \${PHONE_NUMBER})\n";
        $dialplan .= "    same => n,NoOp(Audio: \${AUDIO_FILE})\n";
        $dialplan .= "    same => n,Set(CDR(dst)=\${PHONE_NUMBER})  ; CDR'da dst = telefon numarası\n";
        $dialplan .= "    same => n,Set(CDR(userfield)=\${CALL_ID})  ; CDR'da userfield = Call ID\n";
        $dialplan .= "    same => n,Set(CHANNEL(language)=tr)\n";
        $dialplan .= "    same => n,Set(ANSWEREDTIME=\${EPOCH})\n";
        $dialplan .= "    same => n,Set(LOOP_COUNT=0)\n";
        $dialplan .= "    same => n,Goto(play_message)\n\n";
        
        // Mesaj çalma (aynı s extension içinde label olarak)
        $dialplan .= "; Mesajı çal ve tuş bekle (s extension içinde)\n";
        $dialplan .= "    same => n(play_message),NoOp(=== Mesaj Çalınıyor ===)\n";
        $dialplan .= "    same => n,GotoIf(\$[\${LOOP_COUNT} = 0]?mark_as_answered)\n";
        $dialplan .= "    same => n(continue_loop),Set(LOOP_COUNT=\$[\${LOOP_COUNT} + 1])\n";
        $dialplan .= "    same => n,GotoIf(\$[\${LOOP_COUNT} > 1]?do_hangup)\n";
        $dialplan .= "    same => n(play_audio),Background(\${AUDIO_FILE})\n";
        $dialplan .= "    same => n,WaitExten(" . $timeoutSeconds . ")\n";
        
        // Mark as answered (aynı s extension içinde label)
        $dialplan .= "    same => n(mark_as_answered),NoOp(=== Arama Cevaplandı ===)\n";
        $dialplan .= "    same => n,AGI(update_call_status.php,\${CALL_ID},answered)\n";
        $dialplan .= "    same => n,Goto(continue_loop)\n";
        
        // Hangup (aynı s extension içinde label)
        $dialplan .= "    same => n(do_hangup),NoOp(=== Arama Sonlandırılıyor ===)\n";
        $dialplan .= "    same => n,Set(ENDTIME=\${EPOCH})\n";
        $dialplan .= "    same => n,Set(DURATION=\$[\${ENDTIME} - \${ANSWEREDTIME}])\n";
        $dialplan .= "    same => n,NoOp(Arama süresi: \${DURATION} saniye)\n";
        $dialplan .= "    same => n,Hangup()\n\n";
        
        // Timeout davranışı
        if ($template['TimeoutAction'] === 'use_key1_and_hangup') {
            if ($template['Key1Action'] === 'replay') {
                $dialplan .= "    same => n,Goto(play_message)  ; Timeout - tekrar çal\n\n";
            } elseif ($template['Key1Action'] === 'hangup') {
                $dialplan .= "    same => n,Goto(do_hangup)  ; Timeout - kapat (Key1 hangup)\n\n";
            } else {
                $dialplan .= "    same => n,Goto(1,2)  ; Timeout - 1 tuşu davranışı\n\n";
            }
        } else {
            $dialplan .= "    same => n,Goto(do_hangup)  ; Timeout - kapat\n\n";
        }
        
        // Tuş 1 davranışı
        $dialplan .= "; Tuş 1 Davranışı\n";
        if ($template['Key1Action'] === 'replay') {
            $dialplan .= "exten => 1,1,NoOp(=== Tuş 1: Tekrar Dinle ===)\n";
            $dialplan .= "    same => n,AGI(update_call_dtmf.php,\${CALL_ID},1)\n";
            $dialplan .= "    same => n,Set(LOOP_COUNT=0)\n";
            $dialplan .= "    same => n,Goto(s,play_message)\n\n";
        } elseif ($template['Key1Action'] === 'hangup') {
            $dialplan .= "exten => 1,1,NoOp(=== Tuş 1: Hattı Kapat ===)\n";
            $dialplan .= "    same => n,AGI(update_call_dtmf.php,\${CALL_ID},1)\n";
            $dialplan .= "    same => n,Playback(otomatik/goodbye)\n";
            $dialplan .= "    same => n,Goto(s,do_hangup)\n\n";
        } else {
            $transferNumber = $template['Key1TransferNumber'] ?? '133';
            $audioFile = $template['Key1AudioFile'] ?? 'otomatik/transfer';
            $dialplan .= "exten => 1,1,NoOp(=== Tuş 1: Transfer - {$transferNumber} ===)\n";
            $dialplan .= "    same => n,AGI(update_call_dtmf.php,\${CALL_ID},1)\n";
            if (!empty($audioFile)) {
                $dialplan .= "    same => n,Playback({$audioFile})\n";
            }
            $dialplan .= "    same => n,Dial(Local/{$transferNumber}@from-internal,300,t)\n";
            $dialplan .= "    same => n,Playback(otomatik/goodbye)\n";
            $dialplan .= "    same => n,Hangup()\n\n";
        }
        
        // Tuş 2 davranışı
        $dialplan .= "; Tuş 2 Davranışı\n";
        if (($template['Key2Action'] ?? 'transfer') === 'replay') {
            $dialplan .= "exten => 2,1,NoOp(=== Tuş 2: Tekrar Dinle ===)\n";
            $dialplan .= "    same => n,AGI(update_call_dtmf.php,\${CALL_ID},2)\n";
            $dialplan .= "    same => n,Set(LOOP_COUNT=0)\n";
            $dialplan .= "    same => n,Goto(s,play_message)\n\n";
        } elseif (($template['Key2Action'] ?? 'transfer') === 'hangup') {
            $dialplan .= "exten => 2,1,NoOp(=== Tuş 2: Hattı Kapat ===)\n";
            $dialplan .= "    same => n,AGI(update_call_dtmf.php,\${CALL_ID},2)\n";
            $dialplan .= "    same => n,Playback(otomatik/goodbye)\n";
            $dialplan .= "    same => n,Goto(s,do_hangup)\n\n";
        } else {
            $transferNumber2 = $template['Key2TransferNumber'] ?? 'TAHSILAT';
            $audioFile2 = $template['Key2AudioFile'] ?? 'otomatik/transfer';
            $dialplan .= "exten => 2,1,NoOp(=== Tuş 2: Transfer - {$transferNumber2} ===)\n";
            $dialplan .= "    same => n,AGI(update_call_dtmf.php,\${CALL_ID},2)\n";
            if (!empty($audioFile2)) {
                $dialplan .= "    same => n,Playback({$audioFile2})\n";
            }
            
            if (ctype_digit($transferNumber2)) {
                $dialplan .= "    same => n,Dial(Local/{$transferNumber2}@from-internal,300,t)\n";
            } else {
                $dialplan .= "    same => n,Queue({$transferNumber2},t,,,300)\n";
            }
            
            $dialplan .= "    same => n,Playback(otomatik/goodbye)\n";
            $dialplan .= "    same => n,Hangup()\n\n";
        }
        
        // Yanlış tuş
        $dialplan .= "; Yanlış tuş davranışı\n";
        $dialplan .= "exten => _X,1,NoOp(=== Yanlış Tuş: \${EXTEN} ===)\n";
        if ($template['InvalidKeyAction'] === 'use_key1') {
            $dialplan .= "    same => n,Goto(1,2)  ; 1 tuşu davranışı\n\n";
        } else {
            $dialplan .= "    same => n,Goto(s,do_hangup)\n\n";
        }
        
        // h extension - DIALSTATUS ve HANGUPCAUSE kullan (kapsamlı kontrol)
        // CALL_ID varsa otomatik arama, yoksa normal arama
        $dialplan .= "; Hangup handler\n";
        $dialplan .= "exten => h,1,NoOp(=== Arama Bitti ===)\n";
        $dialplan .= "    same => n,NoOp(CALL_ID: \${CALL_ID})\n";
        $dialplan .= "    same => n,NoOp(Dial Status: \${DIALSTATUS})\n";
        $dialplan .= "    same => n,NoOp(Hangup Cause: \${HANGUPCAUSE})\n";
        $dialplan .= "    same => n,GotoIf(\$[\"\${CALL_ID}\" = \"\"]?end)  ; CALL_ID yoksa normal arama\n\n";
        
        $dialplan .= "; DIALSTATUS kontrolü (öncelikli)\n";
        $dialplan .= "    same => n,GotoIf(\$[\"\${DIALSTATUS}\" = \"BUSY\"]?do_busy)\n";
        $dialplan .= "    same => n,GotoIf(\$[\"\${DIALSTATUS}\" = \"NOANSWER\"]?do_noanswer)\n";
        $dialplan .= "    same => n,GotoIf(\$[\"\${DIALSTATUS}\" = \"CONGESTION\"]?do_congestion)\n";
        $dialplan .= "    same => n,GotoIf(\$[\"\${DIALSTATUS}\" = \"CHANUNAVAIL\"]?do_congestion)\n";
        $dialplan .= "    same => n,GotoIf(\$[\"\${DIALSTATUS}\" = \"CANCEL\"]?do_noanswer)\n\n";
        
        $dialplan .= "; HANGUPCAUSE kontrolü (yedek)\n";
        $dialplan .= "    same => n,GotoIf(\$[\"\${HANGUPCAUSE}\" = \"17\"]?do_busy)        ; User busy\n";
        $dialplan .= "    same => n,GotoIf(\$[\"\${HANGUPCAUSE}\" = \"18\"]?do_noanswer)    ; No user responding\n";
        $dialplan .= "    same => n,GotoIf(\$[\"\${HANGUPCAUSE}\" = \"19\"]?do_noanswer)    ; No answer\n";
        $dialplan .= "    same => n,GotoIf(\$[\"\${HANGUPCAUSE}\" = \"21\"]?do_busy)        ; Call rejected\n";
        $dialplan .= "    same => n,GotoIf(\$[\"\${HANGUPCAUSE}\" = \"34\"]?do_congestion)  ; No circuit\n";
        $dialplan .= "    same => n,GotoIf(\$[\"\${HANGUPCAUSE}\" = \"41\"]?do_congestion)  ; Temporary failure\n";
        $dialplan .= "    same => n,GotoIf(\$[\"\${HANGUPCAUSE}\" = \"42\"]?do_congestion)  ; Switching congestion\n";
        $dialplan .= "    same => n,GotoIf(\$[\"\${HANGUPCAUSE}\" = \"47\"]?do_congestion)  ; Resource unavailable\n";
        $dialplan .= "    same => n,GotoIf(\$[\"\${HANGUPCAUSE}\" = \"16\"]?end)         ; Normal clearing\n\n";
        
        $dialplan .= "; Default: noanswer\n";
        $dialplan .= "    same => n,NoOp(No match, default noanswer)\n";
        $dialplan .= "    same => n,Goto(do_noanswer)\n\n";
        
        $dialplan .= "; Noanswer (h extension içinde label)\n";
        $dialplan .= "    same => n(do_noanswer),NoOp(=== Cevapsız ===)\n";
        $dialplan .= "    same => n,AGI(update_call_status.php,\${CALL_ID},noanswer)\n";
        $dialplan .= "    same => n,Goto(end)\n\n";
        
        $dialplan .= "; Busy (h extension içinde label)\n";
        $dialplan .= "    same => n(do_busy),NoOp(=== Meşgul ===)\n";
        $dialplan .= "    same => n,AGI(update_call_status.php,\${CALL_ID},busy)\n";
        $dialplan .= "    same => n,Goto(end)\n\n";
        
        $dialplan .= "; Congestion (h extension içinde label)\n";
        $dialplan .= "    same => n(do_congestion),NoOp(=== Tıkanıklık ===)\n";
        $dialplan .= "    same => n,AGI(update_call_status.php,\${CALL_ID},congestion)\n";
        $dialplan .= "    same => n,Goto(end)\n\n";
        
        $dialplan .= "; End\n";
        $dialplan .= "    same => n(end),Hangup()\n\n";
        
        // Dialplan dosyasını oluştur
        $localPath = __DIR__ . '/../asterisk/dialplan/extensions_template_' . $templateId . '.conf';
        $localDir = dirname($localPath);
        
        // Klasörü oluştur
        if (!file_exists($localDir)) {
            mkdir($localDir, 0777, true);
        }
        
        // Dosyayı kaydet
        file_put_contents($localPath, $dialplan);
        
        // SSH ile Asterisk sunucusuna gönder
        try {
            $remoteFile = '/etc/asterisk/extensions_template_' . $templateId . '.conf';
            
            // 1. Dialplan dosyasını gönder
            $sshHelper->sendFile($localPath, $remoteFile);
            
            // 2. extensions_custom.conf'a include ekle (varsa ekleme)
            $includeStatement = "#include extensions_template_{$templateId}.conf";
            $checkCmd = "grep -q '{$includeStatement}' /etc/asterisk/extensions_custom.conf || echo '{$includeStatement}' >> /etc/asterisk/extensions_custom.conf";
            $sshHelper->exec($checkCmd);
            
            // 3. Dialplan'ı reload et
            $sshHelper->exec('asterisk -rx "dialplan reload"');
            
            // 4. Context'in yüklendiğini doğrula
            $verifyCmd = "asterisk -rx 'dialplan show {$context}' | head -3";
            $result = $sshHelper->exec($verifyCmd);
            
            // Başarı mesajı
            if (strpos($result, "Context '{$context}' created") !== false || strpos($result, $context) !== false) {
                error_log("Template {$templateId} dialplan başarıyla yüklendi: {$context}");
            } else {
                throw new Exception("Dialplan yüklendi ama context doğrulanamadı");
            }
            
        } catch (Exception $e) {
            error_log("Template {$templateId} dialplan SSH hatası: " . $e->getMessage());
            throw new Exception("Dialplan oluşturuldu ancak Asterisk'e gönderilemedi: " . $e->getMessage());
        }
        
        return $dialplan;
    }
}
