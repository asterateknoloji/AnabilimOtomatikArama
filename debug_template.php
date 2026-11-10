<?php
require_once 'config.php';

// ABC şablonunu bul
$stmt = $pdo->prepare("SELECT id, TemplateName, TemplateContent FROM " . table('MESSAGE_TEMPLATES') . " WHERE TemplateName LIKE '%ABC%' ORDER BY id DESC LIMIT 1");
$stmt->execute();
$template = $stmt->fetch();

header('Content-Type: text/html; charset=utf-8');

if ($template) {
    echo "<h2>Şablon Bulundu: " . htmlspecialchars($template['TemplateName']) . "</h2>";
    echo "<h3>Şablon İçeriği (JSON):</h3>";
    echo "<pre>";
    $content = json_decode($template['TemplateContent'], true);
    print_r($content);
    echo "</pre>";
    
    echo "<h3>Şablon İçeriği (Ham JSON):</h3>";
    echo "<pre>";
    echo htmlspecialchars($template['TemplateContent']);
    echo "</pre>";
} else {
    echo "<h2>ABC şablonu bulunamadı!</h2>";
    
    // Tüm şablonları listele
    $stmt = $pdo->query("SELECT id, TemplateName FROM " . table('MESSAGE_TEMPLATES') . " ORDER BY id DESC");
    $templates = $stmt->fetchAll();
    
    echo "<h3>Mevcut Şablonlar:</h3>";
    echo "<ul>";
    foreach ($templates as $t) {
        echo "<li>" . htmlspecialchars($t['TemplateName']) . " (ID: {$t['id']})</li>";
    }
    echo "</ul>";
}
?>
