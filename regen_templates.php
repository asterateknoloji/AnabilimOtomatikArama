<?php
require_once 'config.php';
require_once 'includes/TemplateManager.php';

echo "🔄 Template'leri yeniden oluştur ve gönder...\n\n";

$templateManager = new TemplateManager($pdo);
$stmt = $pdo->query("SELECT id, TemplateName FROM " . table('MESSAGE_TEMPLATES') . " ORDER BY id");
$templates = $stmt->fetchAll();

foreach ($templates as $template) {
    echo "Template #{$template['id']}: {$template['TemplateName']}\n";
    
    $dialplan = $templateManager->generateDialplanForTemplate($template['id']);
    if ($dialplan) {
        $filename = "asterisk/extensions_template_{$template['id']}.conf";
        file_put_contents($filename, $dialplan);
        echo "  ✅ Oluşturuldu: $filename\n";
        
        $remoteFile = "/etc/asterisk/extensions_template_{$template['id']}.conf";
        $cmd = "tools\\pscp.exe -P " . ASTERISK_SSH_PORT . " -pw \"" . ASTERISK_SSH_PASS . "\" -batch \"$filename\" \"" . ASTERISK_SSH_USER . "@" . ASTERISK_SSH_HOST . ":$remoteFile\" 2>&1";
        shell_exec($cmd);
        echo "  ✅ Gönderildi\n";
    }
    echo "\n";
}

echo "🔄 Dialplan reload...\n";
$reloadCmd = "tools\\plink.exe -P " . ASTERISK_SSH_PORT . " -pw \"" . ASTERISK_SSH_PASS . "\" -batch " . ASTERISK_SSH_USER . "@" . ASTERISK_SSH_HOST . " \"asterisk -rx 'dialplan reload'\" 2>&1";
shell_exec($reloadCmd);
echo "✅ TAMAMLANDI!\n";
