<?php
require_once 'includes/check_auth.php';
requirePermission('template.view');
require_once 'config.php';
require_once 'includes/TTSSettings.php';
require_once 'includes/TTSSecrets.php';
require_once 'includes/EMATTS.php';
require_once 'includes/WindowsTTS.php';

$settingsManager = new TTSSettings();
$secretsManager = new TTSSecrets();
$message = null;
$messageType = 'success';
$testAudioUrl = null;

function getWindowsVoices()
{
    $script = <<<'PS'
Add-Type -AssemblyName System.Speech
$s = New-Object System.Speech.Synthesis.SpeechSynthesizer
$items = $s.GetInstalledVoices() | ForEach-Object {
    [PSCustomObject]@{
        name = $_.VoiceInfo.Name
        language = $_.VoiceInfo.Culture.Name
        gender = $_.VoiceInfo.Gender.ToString()
    }
}
$items | ConvertTo-Json -Compress
$s.Dispose()
PS;
    $encoded = base64_encode(mb_convert_encoding($script, 'UTF-16LE', 'UTF-8'));
    $json = shell_exec('powershell.exe -NoProfile -EncodedCommand ' . escapeshellarg($encoded));
    $voices = json_decode(trim((string)$json), true);
    if (!$voices) {
        return [];
    }
    return isset($voices['name']) ? [$voices] : $voices;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // EMA ilk çalıştırmada modeli indirip yüklediği için 30 saniyeyi aşabilir.
    @set_time_limit(300);
    @ini_set('max_execution_time', '300');
    requirePermission('template.edit');
    try {
        $priority = [
            $_POST['priority_1'] ?? 'windows',
            $_POST['priority_2'] ?? 'ema',
            $_POST['priority_3'] ?? 'google',
            $_POST['priority_4'] ?? 'azure',
        ];

        $settingsManager->save([
            'default_language' => $_POST['default_language'] ?? 'tr-TR',
            'speech_speed' => $_POST['speech_speed'] ?? 0.85,
            'engine_priority' => $priority,
            'engines' => [
                'windows' => [
                    'enabled' => isset($_POST['windows_enabled']),
                    'voices' => [
                        'tr-TR' => $_POST['windows_voice_tr'] ?? '',
                        'en-US' => $_POST['windows_voice_en'] ?? '',
                    ],
                ],
                'ema' => [
                    'enabled' => isset($_POST['ema_enabled']),
                    'python_path' => $_POST['ema_python_path'] ?? '',
                ],
                'google' => [
                    'enabled' => isset($_POST['google_enabled']),
                    'voices' => [
                        'tr-TR' => $_POST['google_voice_tr'] ?? '',
                        'en-US' => $_POST['google_voice_en'] ?? '',
                    ],
                ],
                'azure' => [
                    'enabled' => isset($_POST['azure_enabled']),
                    'region' => $_POST['azure_region'] ?? 'westeurope',
                    'voices' => [
                        'tr-TR' => $_POST['azure_voice_tr'] ?? '',
                        'en-US' => $_POST['azure_voice_en'] ?? '',
                    ],
                ],
            ],
        ]);
        $secretsManager->save(
            $_POST['google_api_key'] ?? null,
            $_POST['azure_key'] ?? null
        );

        $message = 'TTS tanımları kaydedildi.';

        if (isset($_POST['test_tts'])) {
            $testText = trim($_POST['test_text'] ?? '');
            if ($testText === '') {
                throw new RuntimeException('Test metni boş olamaz.');
            }
            error_log('[TTS TEST] Başladı - Motor sırası: ' . implode(', ', $priority));

            $testDir = __DIR__ . '/tmp';
            if (!is_dir($testDir)) {
                mkdir($testDir, 0777, true);
            }
            $testFile = $testDir . '/tts_definition_test.wav';
            @unlink($testFile);

            $tts = new WindowsTTS();
            $tts->textToSpeech($testText, $testFile, $_POST['default_language'] ?? 'tr-TR');
            $testAudioUrl = 'tmp/tts_definition_test.wav?v=' . time();
            $message = 'TTS testi başarıyla oluşturuldu.';
            error_log('[TTS TEST] Başarılı - Dosya: ' . $testFile . ' - Boyut: ' . filesize($testFile));
        }
    } catch (Throwable $e) {
        $message = $e->getMessage();
        $messageType = 'error';
        error_log('[TTS TEST] Hata: ' . $e->getMessage());
    }
}

$settings = $settingsManager->getAll();
$secrets = $secretsManager->getAll();
$hasGoogleKey = $secrets['google_api_key'] !== '';
$hasAzureKey = $secrets['azure_key'] !== '' || (defined('AZURE_TTS_KEY') && AZURE_TTS_KEY !== '');
$windowsVoices = getWindowsVoices();
$ema = new EMATTS($settingsManager);
$emaAvailable = $ema->isAvailable();
$engineLabels = [
    'windows' => 'Windows SAPI (Yerel)',
    'ema' => 'EMA Lightning (Türkçe/Yerel)',
    'google' => 'Google TTS (İnternet)',
    'azure' => 'Azure Neural (İnternet)',
];

function voiceOptions($voices, $language, $selected)
{
    $html = '';
    foreach ($voices as $voice) {
        if (($voice['language'] ?? '') !== $language) {
            continue;
        }
        $name = $voice['name'];
        $isSelected = $name === $selected ? ' selected' : '';
        $html .= '<option value="' . htmlspecialchars($name) . '"' . $isSelected . '>' .
            htmlspecialchars($name . ' (' . ($voice['gender'] ?? '') . ')') . '</option>';
    }
    if ($html === '' && $selected !== '') {
        $html = '<option value="' . htmlspecialchars($selected) . '" selected>' . htmlspecialchars($selected) . '</option>';
    }
    return $html;
}

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TTS Tanımları</title>
    <style>
        *{box-sizing:border-box} body{margin:0;padding:24px;background:#f4f6fb;font-family:Segoe UI,Arial,sans-serif;color:#263238}
        .container{max-width:1100px;margin:auto}.header,.card{background:#fff;border-radius:14px;padding:22px;box-shadow:0 5px 18px rgba(0,0,0,.08);margin-bottom:18px}
        h1{margin:0 0 8px;color:#3949ab}.grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}
        label{display:block;font-weight:600;margin:10px 0 6px}select,input[type=text],input[type=password],input[type=number],textarea{width:100%;padding:10px;border:1px solid #cfd8dc;border-radius:8px}
        .engine{border:1px solid #e0e0e0;border-radius:10px;padding:14px;margin-top:12px}.engine-title{display:flex;align-items:center;gap:9px;font-weight:700}
        .status-ok{color:#1b8f3a}.status-bad{color:#c62828}.btn{border:0;border-radius:8px;padding:11px 18px;color:#fff;font-weight:700;cursor:pointer}
        .save{background:#3949ab}.test{background:#00897b}.back{display:inline-block;text-decoration:none;color:#3949ab;margin-top:8px}
        .message{padding:12px;border-radius:8px;margin-bottom:18px}.success{background:#dff5e5;color:#176b31}.error{background:#fde3e3;color:#a51f1f}
        .hint{font-size:12px;color:#607d8b;margin-top:5px}@media(max-width:760px){.grid{grid-template-columns:1fr}}
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>🔊 TTS Tanımları</h1>
        <div>Motor önceliğini, dili ve kullanılacak sesleri yönetin.</div>
        <a class="back" href="otomatikarama.php">← Ana menüye dön</a>
    </div>

    <?php if ($message): ?>
        <div class="message <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="post">
        <div class="card">
            <div class="grid">
                <div>
                    <label>Varsayılan dil</label>
                    <select name="default_language">
                        <option value="tr-TR" <?= $settings['default_language']==='tr-TR'?'selected':'' ?>>Türkçe (tr-TR)</option>
                        <option value="en-US" <?= $settings['default_language']==='en-US'?'selected':'' ?>>İngilizce (en-US)</option>
                    </select>
                    <label>Konuşma hızı</label>
                    <input type="number" name="speech_speed" min="0.50" max="1.50" step="0.05" value="<?= htmlspecialchars((string)($settings['speech_speed'] ?? 0.85)) ?>">
                    <div class="hint">0.85 yavaş, 1.00 normal, 1.20 hızlı</div>
                </div>
                <div>
                    <label>Motor önceliği</label>
                    <div class="grid">
                        <?php for ($i=0; $i<4; $i++): ?>
                            <select name="priority_<?= $i+1 ?>">
                                <?php foreach ($engineLabels as $id=>$label): ?>
                                    <option value="<?= $id ?>" <?= ($settings['engine_priority'][$i]??'')===$id?'selected':'' ?>><?= $i+1 ?>. <?= htmlspecialchars($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="engine">
                <div class="engine-title"><input type="checkbox" name="windows_enabled" <?= !empty($settings['engines']['windows']['enabled'])?'checked':'' ?>> Windows SAPI — Birinci tercih</div>
                <div class="grid">
                    <div><label>Türkçe ses</label><select name="windows_voice_tr"><?= voiceOptions($windowsVoices,'tr-TR',$settings['engines']['windows']['voices']['tr-TR']) ?></select></div>
                    <div><label>İngilizce ses</label><select name="windows_voice_en"><?= voiceOptions($windowsVoices,'en-US',$settings['engines']['windows']['voices']['en-US']) ?></select></div>
                </div>
            </div>

            <div class="engine">
                <div class="engine-title"><input type="checkbox" name="ema_enabled" <?= !empty($settings['engines']['ema']['enabled'])?'checked':'' ?>> EMA Lightning — Türkçe, yerel ve Yıllık</div>
                <p>
                    <span class="<?= $emaAvailable?'status-ok':'status-bad' ?>"><?= $emaAvailable?'✓ EMA Lightning hazır':'⏳ EMA Lightning kuruluyor/hazır değil' ?></span>
                </p>
                <label>Python yolu</label><input type="text" name="ema_python_path" value="<?= htmlspecialchars($settings['engines']['ema']['python_path']) ?>" placeholder="Otomatik bulunur">
                <div class="hint">Tek Türkçe ses kullanır. İngilizce için sıradaki etkin motor devreye girer.</div>
                <div class="hint">İlk EMA testi model indirme/yükleme nedeniyle 1-2 dakika sürebilir.</div>
            </div>

            <div class="engine">
                <div class="engine-title"><input type="checkbox" name="google_enabled" <?= !empty($settings['engines']['google']['enabled'])?'checked':'' ?>> Google TTS — İnternet yedeği</div>
                <p class="<?= $hasGoogleKey?'status-ok':'hint' ?>"><?= $hasGoogleKey?'✓ Google Cloud API anahtarı kayıtlı':'Anahtar boşsa ücretsiz Google Translate TTS kullanılır.' ?></p>
                <label>Google Cloud API Key</label>
                <input type="password" name="google_api_key" value="" placeholder="<?= $hasGoogleKey?'Kayıtlı — değiştirmek için yeni anahtar girin':'API anahtarını girin' ?>" autocomplete="new-password">
                <div class="grid">
                    <div><label>Türkçe ses</label><input type="text" name="google_voice_tr" value="<?= htmlspecialchars($settings['engines']['google']['voices']['tr-TR']) ?>"></div>
                    <div><label>İngilizce ses</label><input type="text" name="google_voice_en" value="<?= htmlspecialchars($settings['engines']['google']['voices']['en-US']) ?>"></div>
                </div>
            </div>
            <div class="engine"><div class="engine-title"><input type="checkbox" name="azure_enabled" <?= !empty($settings['engines']['azure']['enabled'])?'checked':'' ?>> Azure Neural — İnternet yedeği</div>
                <p class="<?= $hasAzureKey?'status-ok':'status-bad' ?>"><?= $hasAzureKey?'✓ Azure anahtarı kayıtlı':'✗ Azure anahtarı girilmedi' ?></p>
                <div class="grid">
                    <div><label>Azure Speech Key</label><input type="password" name="azure_key" value="" placeholder="<?= $hasAzureKey?'Kayıtlı — değiştirmek için yeni anahtar girin':'Speech Key girin' ?>" autocomplete="new-password"></div>
                    <div><label>Azure Region</label><input type="text" name="azure_region" value="<?= htmlspecialchars($settings['engines']['azure']['region']) ?>" placeholder="örn. westeurope"></div>
                </div>
                <div class="grid">
                    <div><label>Türkçe Azure sesi</label><input type="text" name="azure_voice_tr" value="<?= htmlspecialchars($settings['engines']['azure']['voices']['tr-TR']) ?>"></div>
                    <div><label>İngilizce Azure sesi</label><input type="text" name="azure_voice_en" value="<?= htmlspecialchars($settings['engines']['azure']['voices']['en-US']) ?>"></div>
                </div>
            </div>
        </div>

        <div class="card">
            <label>Test metni</label>
            <textarea name="test_text" rows="3">Merhaba, bu seçilen metin okuma motorunun testidir.</textarea>
            <div style="margin-top:14px;display:flex;gap:10px">
                <button class="btn save" type="submit" name="save_tts">💾 Kaydet</button>
                <button class="btn test" type="submit" name="test_tts">▶ Test Sesi Oluştur</button>
            </div>
            <?php if ($testAudioUrl): ?><audio controls autoplay style="width:100%;margin-top:16px" src="<?= htmlspecialchars($testAudioUrl) ?>"></audio><?php endif; ?>
        </div>
    </form>
</div>
</body>
</html>
