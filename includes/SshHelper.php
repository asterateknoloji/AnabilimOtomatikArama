<?php
/**
 * SSH/SCP Helper sınıfı
 * PuTTY plink ve pscp kullanarak SSH bağlantısı ve dosya transferi
 */

class SshHelper
{
    private $host;
    private $port;
    private $user;
    private $pass;
    
    public function __construct($host, $port, $user, $pass)
    {
        $this->host = $host;
        $this->port = $port;
        $this->user = $user;
        $this->pass = $pass;
    }
    
    /**
     * Dosya gönder (SCP)
     * @param string $localFile Yerel dosya yolu
     * @param string $remotePath Uzak sunucudaki hedef yol
     */
    public function sendFile($localFile, $remotePath)
    {
        if (!file_exists($localFile)) {
            throw new Exception("Yerel dosya bulunamadı: {$localFile}");
        }
        
        // Önce pscp.exe var mı kontrol et (password authentication destekler)
        $pscpPath = $this->findPscp();
        
        if ($pscpPath) {
            return $this->sendWithPscp($pscpPath, $localFile, $remotePath);
        }
        
        // plink.exe ile scp dene
        $plinkPath = $this->findPlink();
        
        if ($plinkPath) {
            return $this->sendWithPlink($plinkPath, $localFile, $remotePath);
        }
        
        // Son çare: Windows OpenSSH (ama password authentication desteklemeyebilir)
        if ($this->commandExists('scp')) {
            return $this->sendWithOpenSSH($localFile, $remotePath);
        }
        
        throw new Exception("SSH/SCP araçları bulunamadı. Lütfen PuTTY (plink.exe/pscp.exe) kurun.");
    }
    
    /**
     * Komutun sistemde olup olmadığını kontrol et
     */
    private function commandExists($command)
    {
        exec("where {$command} 2>nul", $output, $code);
        return $code === 0;
    }
    
    /**
     * Windows OpenSSH ile dosya gönder
     */
    private function sendWithOpenSSH($localFile, $remotePath)
    {
        // sshpass olmadığı için, SSH key veya expect kullanmamız gerekir
        // Ama basit bir çözüm: plink gibi password'ü direkt veremiyoruz
        // Bu yüzden PowerShell ile Expect-like davranış oluşturalım
        
        $psScript = sys_get_temp_dir() . '/scp_' . uniqid() . '.ps1';
        
        // PowerShell script ile SCP (password otomatik gönder)
        $psContent = <<<POWERSHELL
\$password = '{$this->pass}'
\$securePassword = ConvertTo-SecureString \$password -AsPlainText -Force

# SCP komutu
\$command = "scp -P {$this->port} -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null '{$localFile}' {$this->user}@{$this->host}:'{$remotePath}'"

# Expect benzeri davranış için plink kullan (eğer varsa)
# Aksi halde direkt çalıştır
& cmd /c "\$command" 2>&1
POWERSHELL;
        
        file_put_contents($psScript, $psContent);
        
        // SSHPASS benzeri çözüm: expect veya PowerShell Plink
        // En basit yol: sshpass alternatifi kullanmak
        // Ama Windows'ta yok, bu yüzden plink kullanacağız veya SSH key
        
        // Alternatif: WinSCP .NET assembly veya direkt plink
        @unlink($psScript);
        
        // Basit çözüm: CMD ile password gönder
        $command = sprintf(
            'echo y | pscp -P %d -pw "%s" -batch "%s" "%s@%s:%s" 2>&1',
            $this->port,
            $this->pass,
            $localFile,
            $this->user,
            $this->host,
            $remotePath
        );
        
        // Eğer pscp yoksa, base64 ile gönder
        return $this->sendWithBase64($localFile, $remotePath);
    }
    
    /**
     * Base64 ile dosya gönder (SSH command kullanarak)
     */
    private function sendWithBase64($localFile, $remotePath)
    {
        // Dosya içeriğini oku ve base64 encode et
        $content = file_get_contents($localFile);
        $base64Content = base64_encode($content);
        
        // Büyük dosyalar için parçalara böl (4000 karakter)
        $chunkSize = 4000;
        $chunks = str_split($base64Content, $chunkSize);
        
        // PowerShell script oluştur
        $psScript = sys_get_temp_dir() . '/ssh_transfer_' . uniqid() . '.ps1';
        
        $commands = [];
        $commands[] = "echo '' > '{$remotePath}.b64'"; // Temp dosya oluştur
        
        foreach ($chunks as $chunk) {
            $commands[] = "echo '{$chunk}' >> '{$remotePath}.b64'";
        }
        
        $commands[] = "base64 -d '{$remotePath}.b64' > '{$remotePath}'";
        $commands[] = "rm '{$remotePath}.b64'";
        $commands[] = "chmod 644 '{$remotePath}'";
        
        $fullCommand = implode(' && ', $commands);
        
        // SSH komutu çalıştır
        $sshCommand = sprintf(
            'ssh -p %d -o StrictHostKeyChecking=no -o UserKnownHostsFile=nul -o PreferredAuthentications=password %s@%s "%s"',
            $this->port,
            $this->user,
            $this->host,
            str_replace('"', '\\"', $fullCommand)
        );
        
        // sshpass olmadığı için plink kullanmalıyız veya başka bir yöntem
        // En garantili yol: plink.exe'yi indirip tools klasörüne koymak
        
        throw new Exception("OpenSSH password authentication desteklenmiyor. Lütfen PuTTY araçlarını manuel olarak kurun:\n1. https://www.chiark.greenend.org.uk/~sgtatham/putty/latest.html adresinden plink.exe ve pscp.exe indirin\n2. d:\\xampp\\htdocs\\OtomatikArama\\tools\\ klasörüne kopyalayın");
    }
    
    /**
     * pscp.exe'yi bul
     */
    private function findPscp()
    {
        // Yaygın konumlar
        $paths = [
            'C:\Program Files\PuTTY\pscp.exe',
            'C:\Program Files (x86)\PuTTY\pscp.exe',
            'C:\PuTTY\pscp.exe',
            __DIR__ . '/../tools/pscp.exe'
        ];
        
        foreach ($paths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }
        
        // PATH'te var mı kontrol et
        exec('where pscp.exe 2>nul', $output, $code);
        if ($code === 0 && !empty($output)) {
            return trim($output[0]);
        }
        
        return null;
    }
    
    /**
     * plink.exe'yi bul
     */
    private function findPlink()
    {
        // Yaygın konumlar
        $paths = [
            'C:\Program Files\PuTTY\plink.exe',
            'C:\Program Files (x86)\PuTTY\plink.exe',
            'C:\PuTTY\plink.exe',
            __DIR__ . '/../tools/plink.exe'
        ];
        
        foreach ($paths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }
        
        // PATH'te var mı kontrol et
        exec('where plink.exe 2>nul', $output, $code);
        if ($code === 0 && !empty($output)) {
            return trim($output[0]);
        }
        
        return null;
    }
    
    /**
     * pscp ile dosya gönder
     */
    private function sendWithPscp($pscpPath, $localFile, $remotePath)
    {
        $command = sprintf(
            '"%s" -P %d -pw "%s" -batch "%s" "%s@%s:%s" 2>&1',
            $pscpPath,
            $this->port,
            $this->pass,
            $localFile,
            $this->user,
            $this->host,
            $remotePath
        );
        
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new Exception("pscp dosya gönderimi başarısız: " . implode("\n", $output));
        }
        
        return true;
    }
    
    /**
     * plink ile dosya gönder (base64 encoded)
     */
    private function sendWithPlink($plinkPath, $localFile, $remotePath)
    {
        // Dosya içeriğini base64 encode et
        $content = base64_encode(file_get_contents($localFile));
        
        // plink ile uzak sunucuda dosyayı oluştur
        $command = sprintf(
            '"%s" -P %d -pw "%s" -batch %s@%s "echo %s | base64 -d > %s && chmod 644 %s" 2>&1',
            $plinkPath,
            $this->port,
            $this->pass,
            $this->user,
            $this->host,
            escapeshellarg($content),
            escapeshellarg($remotePath),
            escapeshellarg($remotePath)
        );
        
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new Exception("plink dosya gönderimi başarısız: " . implode("\n", $output));
        }
        
        return true;
    }
    
    /**
     * SSH komutu çalıştır
     */
    public function executeCommand($command)
    {
        $plinkPath = $this->findPlink();
        
        if (!$plinkPath) {
            throw new Exception("plink.exe bulunamadı");
        }
        
        $cmd = sprintf(
            '"%s" -P %d -pw "%s" -batch %s@%s "%s" 2>&1',
            $plinkPath,
            $this->port,
            $this->pass,
            $this->user,
            $this->host,
            str_replace('"', '\"', $command)
        );
        
        exec($cmd, $output, $returnCode);
        
        return [
            'code' => $returnCode,
            'output' => $output
        ];
    }
    
    /**
     * SSH komutu çalıştır (kısa alias)
     * @return string Komut çıktısı
     */
    public function exec($command)
    {
        $result = $this->executeCommand($command);
        return implode("\n", $result['output']);
    }
    
    /**
     * Dosya indir (SCP)
     * @param string $remotePath Uzak sunucudaki dosya yolu
     * @param string $localFile Yerel hedef dosya yolu
     */
    public function getFile($remotePath, $localFile)
    {
        // Önce pscp.exe var mı kontrol et
        $pscpPath = $this->findPscp();
        
        if ($pscpPath) {
            return $this->getWithPscp($pscpPath, $remotePath, $localFile);
        }
        
        // plink.exe ile base64 encoded olarak indir
        $plinkPath = $this->findPlink();
        
        if ($plinkPath) {
            return $this->getWithPlink($plinkPath, $remotePath, $localFile);
        }
        
        throw new Exception("SSH/SCP araçları bulunamadı. Lütfen PuTTY (plink.exe/pscp.exe) kurun.");
    }
    
    /**
     * pscp ile dosya indir
     */
    private function getWithPscp($pscpPath, $remotePath, $localFile)
    {
        $command = sprintf(
            '"%s" -P %d -pw "%s" -batch "%s@%s:%s" "%s" 2>&1',
            $pscpPath,
            $this->port,
            $this->pass,
            $this->user,
            $this->host,
            $remotePath,
            $localFile
        );
        
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new Exception("pscp dosya indirme başarısız: " . implode("\n", $output));
        }
        
        if (!file_exists($localFile)) {
            throw new Exception("Dosya indirilemedi: " . $localFile);
        }
        
        return true;
    }
    
    /**
     * plink ile dosya indir (base64 encoded)
     */
    private function getWithPlink($plinkPath, $remotePath, $localFile)
    {
        // plink ile uzak sunucudan dosyayı base64 encode ederek al
        $command = sprintf(
            '"%s" -P %d -pw "%s" -batch %s@%s "base64 %s" 2>&1',
            $plinkPath,
            $this->port,
            $this->pass,
            $this->user,
            $this->host,
            escapeshellarg($remotePath)
        );
        
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new Exception("plink dosya indirme başarısız: " . implode("\n", $output));
        }
        
        // base64 decode et ve kaydet
        $base64Content = implode('', $output);
        $content = base64_decode($base64Content);
        
        if ($content === false) {
            throw new Exception("Base64 decode hatası");
        }
        
        file_put_contents($localFile, $content);
        
        return true;
    }
}
