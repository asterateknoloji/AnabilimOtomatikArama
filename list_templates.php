<?php
require_once __DIR__ . '/config.php';

echo "MESSAGE_TEMPLATES Listesi:\n";
echo "===========================\n\n";

$stmt = $pdo->query("SELECT id, TemplateName, DialplanContext, Active FROM MESSAGE_TEMPLATES ORDER BY id");
while ($row = $stmt->fetch()) {
    echo "ID: {$row['id']}\n";
    echo "Name: {$row['TemplateName']}\n";
    echo "Context: {$row['DialplanContext']}\n";
    echo "Active: " . ($row['Active'] ? 'Yes' : 'No') . "\n";
    echo "---\n";
}
