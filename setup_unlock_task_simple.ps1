# Windows Task Scheduler - Basit Kurulum
# Her 1 dakikada unlock script çalıştır

$taskName = "OtomatikArama-UnlockStuckCalls"
$scriptPath = "D:\xampp\htdocs\WindowsOtomatikAramason97\unlock_stuck_calls.php"
$phpPath = "D:\xampp\php\php.exe"

Write-Host "=================================="
Write-Host "Task Kurulumu"
Write-Host "=================================="

# Mevcut task'ı sil
schtasks /delete /tn $taskName /f 2>$null

# Yeni task oluştur
schtasks /create /tn $taskName /tr "`"$phpPath`" `"$scriptPath`"" /sc minute /mo 1 /f

Write-Host ""
Write-Host "✅ Task oluşturuldu!"
Write-Host "  Adı: $taskName"
Write-Host "  Çalışma: Her 1 dakika"
Write-Host "  Timeout: 30 saniye"
Write-Host ""
Write-Host "Test için: Start-ScheduledTask -TaskName '$taskName'"
Write-Host "=================================="
