<?php
require_once 'config.php';
require_once 'includes/TemplateManager.php';

$templateManager = new TemplateManager($pdo);
$templates = $pdo->query("SELECT id FROM " . table('MESSAGE_TEMPLATES') . " ORDER BY id")->fetchAll();

foreach ($templates as $t) {
    $dialplan = $templateManager->generateDialplanForTemplate($t['id']);
    $file = "asterisk/extensions_template_{$t['id']}.conf";
    file_put_contents($file, $dialplan);
    shell_exec("tools\\pscp.exe -P " . ASTERISK_SSH_PORT . " -pw \"" . ASTERISK_SSH_PASS . "\" -batch \"$file\" \"" . ASTERISK_SSH_USER . "@" . ASTERISK_SSH_HOST . ":/etc/asterisk/extensions_template_{$t['id']}.conf\" 2>&1");
}

shell_exec("tools\\plink.exe -P " . ASTERISK_SSH_PORT . " -pw \"" . ASTERISK_SSH_PASS . "\" -batch " . ASTERISK_SSH_USER . "@" . ASTERISK_SSH_HOST . " \"asterisk -rx 'dialplan reload'\" 2>&1");
echo "✅ TAMAM!\n";
