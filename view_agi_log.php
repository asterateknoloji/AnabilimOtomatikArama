<?php
/**
 * AGI Log Görüntüleyici
 * Asterisk AGI script loglarını gösterir
 */

session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    die('Login gerekli!');
}

// SSH ile log dosyasını oku
$host = '192.168.181.16';
$port = 45555;
$user = 'root';
$pass = 'KKAstera*2o21@.!';
$logFile = '/tmp/agi_call_end.log';

// plink ile log oku
$command = "tools\\plink.exe -P $port -pw \"$pass\" -batch $user@$host \"tail -100 $logFile 2>&1\"";
$output = shell_exec($command);

echo "<h1>📋 AGI Script Log (Son 100 Satır)</h1>";
echo "<p style='font-size: 14px; color: #666;'>Dosya: <code>/tmp/agi_call_end.log</code></p>";

if (empty($output)) {
    echo "<div style='background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 5px;'>";
    echo "<p>⚠️ Log dosyası boş veya okunamadı!</p>";
    echo "<p>Henüz arama yapılmamış olabilir.</p>";
    echo "</div>";
} else {
    echo "<div style='background: #f8f9fa; border: 1px solid #dee2e6; padding: 15px; border-radius: 5px; font-family: monospace; font-size: 12px; white-space: pre-wrap; max-height: 600px; overflow-y: auto;'>";
    echo htmlspecialchars($output);
    echo "</div>";
}

echo "<br>";
echo "<div style='display: flex; gap: 10px;'>";
echo "<a href='arama_yonetimi.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>← Arama Yönetimi</a>";
echo "<button onclick='location.reload()' style='background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>🔄 Yenile</button>";
echo "<button onclick='clearLog()' style='background: #dc3545; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>🗑️ Logu Temizle</button>";
echo "</div>";

echo "<script>
function clearLog() {
    if (confirm('Log dosyası temizlenecek. Emin misiniz?')) {
        fetch('clear_agi_log.php', {method: 'POST'})
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Log temizlendi!');
                    location.reload();
                } else {
                    alert('❌ Hata: ' + data.message);
                }
            });
    }
}
</script>";
