<?php
require_once 'config.php';
require_once 'includes/TemplateManager.php';

echo "🔄 Yeniden oluştur...\n\n";

$templateManager = new TemplateManager($pdo);
$templates = $pdo->query("SELECT id, TemplateName FROM " . table('MESSAGE_TEMPLATES') . " ORDER BY id")->fetchAll();

foreach ($templates as $t) {
    echo "Template #{$t['id']}: {$t['TemplateName']}\n";
    $dialplan = $templateManager->generateDialplanForTemplate($t['id']);
    $file = "asterisk/extensions_template_{$t['id']}.conf";
    file_put_contents($file, $dialplan);
    echo "  ✅ Oluşturuldu\n";
    
    shell_exec("tools\\pscp.exe -P " . ASTERISK_SSH_PORT . " -pw \"" . ASTERISK_SSH_PASS . "\" -batch \"$file\" \"" . ASTERISK_SSH_USER . "@" . ASTERISK_SSH_HOST . ":/etc/asterisk/extensions_template_{$t['id']}.conf\" 2>&1");
    echo "  ✅ Gönderildi\n\n";
}

// AGI script gönder
echo "🔄 AGI script gönder...\n";
shell_exec("tools\\pscp.exe -P " . ASTERISK_SSH_PORT . " -pw \"" . ASTERISK_SSH_PASS . "\" -batch \"asterisk\\agi\\update_call_status.php\" \"" . ASTERISK_SSH_USER . "@" . ASTERISK_SSH_HOST . ":/var/lib/asterisk/agi-bin/update_call_status.php\" 2>&1");
echo "✅ update_call_status.php gönderildi\n\n";

echo "🔄 Dialplan reload...\n";
shell_exec("tools\\plink.exe -P " . ASTERISK_SSH_PORT . " -pw \"" . ASTERISK_SSH_PASS . "\" -batch " . ASTERISK_SSH_USER . "@" . ASTERISK_SSH_HOST . " \"asterisk -rx 'dialplan reload'\" 2>&1");
echo "✅ TAMAMLANDI!\n";
