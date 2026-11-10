# Windows Task Scheduler - Otomatik Unlock Task Kurulumu
# Bu script, takılı aramaları otomatik unlock eden bir görev oluşturur
# Her 5 dakikada bir çalışır

$taskName = "OtomatikArama-UnlockStuckCalls"
$scriptPath = "D:\xampp\htdocs\WindowsOtomatikAramason97\unlock_stuck_calls.php"
$phpPath = "D:\xampp\php\php.exe"

Write-Host "=================================="
Write-Host "Otomatik Unlock Task Kurulumu"
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
$action = New-ScheduledTaskAction -Execute $phpPath -Argument $scriptPath
$trigger = New-ScheduledTaskTrigger -Once -At (Get-Date) -RepetitionInterval (New-TimeSpan -Minutes 1) -RepetitionDuration ([TimeSpan]::MaxValue)
$settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable

Write-Host "Task oluşturuluyor..."
Register-ScheduledTask -TaskName $taskName -Action $action -Trigger $trigger -Settings $settings -Description "Takılı aramaları otomatik unlock eder (5 dakika aralıklarla)"

Write-Host "✅ Task başarıyla oluşturuldu!"
Write-Host ""
Write-Host "Task Detayları:"
Write-Host "  Adı: $taskName"
Write-Host "  Çalışma Aralığı: Her 1 dakika (30 saniye timeout)"
Write-Host "  Script: $scriptPath"
Write-Host ""
Write-Host "Task'ı kontrol etmek için:"
Write-Host "  Task Scheduler'ı açın (taskschd.msc)"
Write-Host "  veya:"
Write-Host "  Get-ScheduledTask -TaskName '$taskName' | Get-ScheduledTaskInfo"
Write-Host ""
Write-Host "Test etmek için:"
Write-Host "  Start-ScheduledTask -TaskName '$taskName'"
Write-Host ""
Write-Host "=================================="
