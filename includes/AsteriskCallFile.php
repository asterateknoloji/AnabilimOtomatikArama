<?php
/**
 * Asterisk Call File Yönetimi
 * Otomatik arama için Asterisk call file oluşturur ve SSH ile gönderir
 */

require_once __DIR__ . '/SshHelper.php';

class AsteriskCallFile
{
    private $callFilePath;
    private $asteriskSpoolPath;
    private $context;
    private $extension;
    private $callerID;
    private $trunk; // Giden hat bilgisi
    private $ssh;
    
    public function __construct()
    {
        // Call file geçici oluşturma yeri
        $this->callFilePath = __DIR__ . '/../asterisk/callfiles/';
        
        // Config'den SSH ve spool ayarlarını al
        if (defined('ASTERISK_SPOOL_PATH')) {
            $this->asteriskSpoolPath = ASTERISK_SPOOL_PATH;
        } else {
            $this->asteriskSpoolPath = '/var/spool/asterisk/outgoing/';
        }
        
        // Dialplan context ve extension
        $this->context = 'auto-calls';
        $this->extension = 's';
        
        // Arayan numara (config'den veya varsayılan)
        if (defined('CALLER_ID')) {
            $this->callerID = CALLER_ID;
        } else {
            $this->callerID = '02129999999';
        }
        
        // Trunk (Giden hat) - varsayılan yok, proje bazlı ayarlanacak
        $this->trunk = null;
        
        // SSH bağlantısı
        if (defined('ASTERISK_SSH_HOST')) {
            $this->ssh = new SshHelper(
                ASTERISK_SSH_HOST,
                ASTERISK_SSH_PORT,
                ASTERISK_SSH_USER,
                ASTERISK_SSH_PASS
            );
        }
        
        // Klasörü oluştur
        if (!file_exists($this->callFilePath)) {
            mkdir($this->callFilePath, 0777, true);
        }
    }
    
    /**
     * Call file oluştur ve SSH ile Asterisk sunucusuna gönder
     */
    public function createCallFile($phoneNumber, $callId, $audioFile, $context = null)
    {
        if (!$this->ssh) {
            throw new Exception("SSH bağlantısı yapılandırılmamış");
        }
        
        // Call file içeriği
        $callFileContent = $this->generateCallFileContent($phoneNumber, $callId, $audioFile, $context);
        
        // Geçici dosya adı
        $tempFile = $this->callFilePath . "call_{$callId}_" . time() . ".call";
        
        // Call file'ı yaz
        file_put_contents($tempFile, $callFileContent);
        
        // Dosya izinlerini ayarla
        chmod($tempFile, 0644);
        
        // Önce /tmp/ klasörüne gönder (atomic operation için)
        $remoteTempFile = '/tmp/' . basename($tempFile);
        $remoteFile = $this->asteriskSpoolPath . basename($tempFile);
        
        try {
            // 1. Dosyayı /tmp/'ye gönder
            $this->ssh->sendFile($tempFile, $remoteTempFile);
            
            // 2. Atomic olarak spool klasörüne taşı (asterisk user'ı ile)
            $this->ssh->exec("chown asterisk:asterisk {$remoteTempFile}");
            $this->ssh->exec("chmod 644 {$remoteTempFile}");
            $this->ssh->exec("mv {$remoteTempFile} {$remoteFile}");
            
            // Yerel geçici dosyayı sil
            @unlink($tempFile);
            
            return $remoteFile;
            
        } catch (Exception $e) {
            // Yerel dosyayı temizle
            @unlink($tempFile);
            throw new Exception("Call file SSH ile gönderilemedi: " . $e->getMessage());
        }
    }
    
    /**
     * Call file içeriğini oluştur
     */
    private function generateCallFileContent($phoneNumber, $callId, $audioFile, $context = null)
    {
        // Context'i belirle
        $useContext = $context ?? $this->context;
        
        // Ses dosyasının uzantısız yolu (Asterisk .wav uzantısını otomatik ekler)
        $audioPath = str_replace('.wav', '', $audioFile);
        
        // Channel formatını belirle
        // Trunk varsa: SIP/[Trunk]/[PhoneNumber]
        // Trunk yoksa: Local/[PhoneNumber]@otomatik-arama-out
        if (!empty($this->trunk)) {
            $channel = "SIP/{$this->trunk}/{$phoneNumber}";
        } else {
            $channel = "Local/{$phoneNumber}@otomatik-arama-out";
        }
        
        // Call file formatı
        $content = <<<CALLFILE
Channel: {$channel}
Callerid: {$this->callerID}
MaxRetries: 0
WaitTime: 45
Context: {$useContext}
Extension: {$this->extension}
Priority: 1
AlwaysDelete: no
Account: {$callId}
Setvar: CALL_ID={$callId}
Setvar: AUDIO_FILE={$audioPath}
Setvar: PHONE_NUMBER={$phoneNumber}
Setvar: __DYNAMIC_FEATURES=
Archive: yes
CALLFILE;
        
        return $content;
    }
    
    /**
     * Asterisk spool dizinini ayarla
     */
    public function setSpoolPath($path)
    {
        $this->asteriskSpoolPath = rtrim($path, '/\\') . DIRECTORY_SEPARATOR;
    }
    
    /**
     * Context ayarla
     */
    public function setContext($context)
    {
        $this->context = $context;
    }
    
    /**
     * Caller ID ayarla
     */
    public function setCallerID($callerID)
    {
        $this->callerID = $callerID;
    }
    
    /**
     * Trunk (Giden Hat) ayarla
     */
    public function setTrunk($trunk)
    {
        $this->trunk = $trunk;
    }
}
