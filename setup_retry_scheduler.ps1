# Windows Task Scheduler - Retry Scheduler Kurulumu
# Bu script, ötelenen aramaları otomatik başlatan bir görev oluşturur
# Her 5 dakikada bir çalışır

$taskName = "OtomatikAramaTekrar"
$scriptPath = "D:\xampp\htdocs\WindowsOtomatikAramason97\retry_scheduler.php"
$phpPath = "D:\xampp\php\php.exe"

Write-Host "=================================="
Write-Host "Retry Scheduler Task Kurulumu"
Write-Host "=================================="
Write-Host ""

# Mevcut task varsa sil
$existingTask = Get-ScheduledTask -TaskName $taskName -ErrorAction SilentlyContinue
if ($existingTask) {
    Write-Host "Mevcut task bulundu, siliniyor..."
    Unregister-ScheduledTask -TaskName $taskName -Confirm:$false
    Write-Host "✅ Eski task silindi"
}

# Yeni task oluştur
Write-Host "Task oluşturuluyor..."
schtasks /create /tn $taskName /tr "`"$phpPath`" `"$scriptPath`"" /sc minute /mo 5 /f

Write-Host ""
Write-Host "✅ Task başarıyla oluşturuldu!"
Write-Host ""
Write-Host "Task Detayları:"
Write-Host "  Adı: $taskName"
Write-Host "  Çalışma Aralığı: Her 5 dakika"
Write-Host "  Script: $scriptPath"
Write-Host ""
Write-Host "Task'ı kontrol etmek için:"
Write-Host "  Get-ScheduledTask -TaskName '$taskName' | Get-ScheduledTaskInfo"
Write-Host ""
Write-Host "Test etmek için:"
Write-Host "  Start-ScheduledTask -TaskName '$taskName'"
Write-Host ""
Write-Host "=================================="
