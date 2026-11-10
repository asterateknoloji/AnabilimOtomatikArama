# =====================================================
# TÜM TASK SCHEDULER GÖREVLERİNİ KUR
# =====================================================

Write-Host "==========================================" -ForegroundColor Cyan
Write-Host "TASK SCHEDULER KURULUM" -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host ""

$workingDir = "D:\xampp\htdocs\WindowsOtomatikAramason97"
$phpExe = "d:\xampp\php\php.exe"

# Yönetici kontrolü
$isAdmin = ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
if (-not $isAdmin) {
    Write-Host "HATA: Bu script Yönetici olarak çalıştırılmalı!" -ForegroundColor Red
    Write-Host "Sağ tık -> Run as Administrator" -ForegroundColor Yellow
    pause
    exit 1
}

Write-Host "Çalışma Dizini: $workingDir" -ForegroundColor White
Write-Host "PHP: $phpExe" -ForegroundColor White
Write-Host ""

# =====================================================
# 1. CLEANUP_STUCK_CALLS (Her 1 dakika)
# =====================================================
Write-Host "1/3 - cleanup_stuck_calls.php kurulum..." -ForegroundColor Yellow

$taskName1 = "OtomatikAramaUnlock"
$scriptPath1 = "$workingDir\cleanup_stuck_calls.php"

# Eski task varsa sil
schtasks /delete /tn $taskName1 /f 2>$null

$action1 = New-ScheduledTaskAction `
    -Execute $phpExe `
    -Argument $scriptPath1 `
    -WorkingDirectory $workingDir

$trigger1 = New-ScheduledTaskTrigger `
    -Once `
    -At (Get-Date) `
    -RepetitionInterval (New-TimeSpan -Minutes 1) `
    -RepetitionDuration ([TimeSpan]::MaxValue)

$principal1 = New-ScheduledTaskPrincipal `
    -UserId "SYSTEM" `
    -LogonType ServiceAccount `
    -RunLevel Highest

Register-ScheduledTask `
    -TaskName $taskName1 `
    -Action $action1 `
    -Trigger $trigger1 `
    -Principal $principal1 `
    -Description "Takılı kalmış aramaları otomatik unlock eder (Her 1 dakika)" `
    -Force | Out-Null

Write-Host "   ✅ $taskName1 kuruldu" -ForegroundColor Green

# =====================================================
# 2. RETRY_SCHEDULER (Her 5 dakika)
# =====================================================
Write-Host "2/3 - retry_scheduler.php kurulum..." -ForegroundColor Yellow

$taskName2 = "OtomatikAramaTekrar"
$scriptPath2 = "$workingDir\retry_scheduler.php"

# Eski task varsa sil
schtasks /delete /tn $taskName2 /f 2>$null

$action2 = New-ScheduledTaskAction `
    -Execute $phpExe `
    -Argument $scriptPath2 `
    -WorkingDirectory $workingDir

$trigger2 = New-ScheduledTaskTrigger `
    -Once `
    -At (Get-Date) `
    -RepetitionInterval (New-TimeSpan -Minutes 5) `
    -RepetitionDuration ([TimeSpan]::MaxValue)

$principal2 = New-ScheduledTaskPrincipal `
    -UserId "SYSTEM" `
    -LogonType ServiceAccount `
    -RunLevel Highest

Register-ScheduledTask `
    -TaskName $taskName2 `
    -Action $action2 `
    -Trigger $trigger2 `
    -Principal $principal2 `
    -Description "Ötelenmiş aramaları otomatik başlatır (Her 5 dakika)" `
    -Force | Out-Null

Write-Host "   ✅ $taskName2 kuruldu" -ForegroundColor Green

# =====================================================
# 3. SCHEDULED_CALLS (Her 2 dakika) - Opsiyonel
# =====================================================
Write-Host "3/3 - cron\scheduled_calls.php kurulum..." -ForegroundColor Yellow

$taskName3 = "OtomatikAramaZamanlanmis"
$scriptPath3 = "$workingDir\cron\scheduled_calls.php"

# Eski task varsa sil
schtasks /delete /tn $taskName3 /f 2>$null

$action3 = New-ScheduledTaskAction `
    -Execute $phpExe `
    -Argument $scriptPath3 `
    -WorkingDirectory $workingDir

$trigger3 = New-ScheduledTaskTrigger `
    -Once `
    -At (Get-Date) `
    -RepetitionInterval (New-TimeSpan -Minutes 2) `
    -RepetitionDuration ([TimeSpan]::MaxValue)

$principal3 = New-ScheduledTaskPrincipal `
    -UserId "SYSTEM" `
    -LogonType ServiceAccount `
    -RunLevel Highest

Register-ScheduledTask `
    -TaskName $taskName3 `
    -Action $action3 `
    -Trigger $trigger3 `
    -Principal $principal3 `
    -Description "Zamanlanmış import'ları otomatik başlatır (Her 2 dakika)" `
    -Force | Out-Null

Write-Host "   ✅ $taskName3 kuruldu" -ForegroundColor Green

# =====================================================
# SONUÇ
# =====================================================
Write-Host ""
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host "KURULUM TAMAMLANDI!" -ForegroundColor Green
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host ""

Write-Host "Kurulu Görevler:" -ForegroundColor White
Get-ScheduledTask -TaskName "OtomatikArama*" | Format-Table TaskName, State, @{Label="NextRun";Expression={$_.NextRunTime}} -AutoSize

Write-Host ""
Write-Host "Tüm görevler başarıyla kuruldu ve aktif!" -ForegroundColor Green
Write-Host ""
Write-Host "Logları kontrol etmek için:" -ForegroundColor Yellow
Write-Host "  dir $workingDir\logs\" -ForegroundColor Gray
Write-Host ""
