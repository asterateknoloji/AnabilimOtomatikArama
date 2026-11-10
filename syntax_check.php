<?php
echo "<h2>🔍 Syntax Kontrolü</h2>";

$file = 'd:\xampp\htdocs\WindowsOtomatikAramason97\arama_yonetimi.php';

// PHP syntax check
$output = shell_exec("D:\\xampp\\php\\php.exe -l " . escapeshellarg($file) . " 2>&1");

echo "<h3>arama_yonetimi.php</h3>";
echo "<pre>" . htmlspecialchars($output) . "</pre>";

if (strpos($output, 'No syntax errors') !== false) {
    echo "<p style='color: green; font-size: 20px;'>✅ SYNTAX OK!</p>";
    echo "<p>Syntax hatası yok ama sayfa hala açılmıyorsa:</p>";
    echo "<ul>";
    echo "<li>PHP memory_limit çok düşük olabilir (256M yapın)</li>";
    echo "<li>max_execution_time çok düşük olabilir (60s yapın)</li>";
    echo "<li>Dosya çok büyük (2268 satır)</li>";
    echo "</ul>";
    
    echo "<h3>🔧 Önerilen php.ini Ayarları:</h3>";
    echo "<pre>memory_limit = 256M\nmax_execution_time = 60\npost_max_size = 100M\nupload_max_filesize = 100M</pre>";
    
    echo "<p><a href='arama_yonetimi_lite.php' style='padding: 15px 30px; background: #4CAF50; color: white; text-decoration: none; border-radius: 5px;'>📱 Lite Version Kullan</a></p>";
} else {
    echo "<p style='color: red; font-size: 20px;'>❌ SYNTAX HATASI VAR!</p>";
}
