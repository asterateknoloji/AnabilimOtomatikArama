<?php

require_once __DIR__ . '/TTSSettings.php';

class EMATTS
{
    private $settings;

    public function __construct(TTSSettings $settingsManager = null)
    {
        $manager = $settingsManager ?: new TTSSettings();
        $this->settings = $manager->getAll();
    }

    public function textToSpeech($text, $outputFile, $language = 'tr-TR')
    {
        if ($language !== 'tr-TR') {
            throw new RuntimeException('EMA Lightning yalnızca Türkçe destekliyor.');
        }

        $python = $this->findPython();
        if (!$python) {
            throw new RuntimeException('EMA Lightning için Python bulunamadı.');
        }

        $script = dirname(__DIR__) . '/scripts/ema_tts.py';
        $inputFile = dirname($outputFile) . '/ema_input_' . uniqid() . '.txt';
        $speed = max(0.5, min(1.5, (float)($this->settings['speech_speed'] ?? 0.85)));
        file_put_contents($inputFile, $text, LOCK_EX);

        $command = escapeshellarg($python) .
            ' ' . escapeshellarg($script) .
            ' --input-file ' . escapeshellarg($inputFile) .
            ' --output-file ' . escapeshellarg($outputFile) .
            ' --sample-rate 48000' .
            ' --speed ' . escapeshellarg((string)$speed) .
            ' 2>&1';

        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);
        @unlink($inputFile);

        if ($returnCode !== 0 || !is_file($outputFile) || filesize($outputFile) === 0) {
            @unlink($outputFile);
            throw new RuntimeException('EMA Lightning TTS hatası: ' . implode("\n", $output));
        }

        return true;
    }

    public function isAvailable()
    {
        $python = $this->findPython();
        if (!$python) {
            return false;
        }

        // pip kaydı yerine gerçek üretim betiğinin import edilebildiğini doğrula.
        $script = dirname(__DIR__) . '/scripts/ema_tts.py';
        if (!is_file($script)) {
            return false;
        }
        $command = escapeshellarg($python) . ' ' . escapeshellarg($script) . ' --help 2>&1';
        exec($command, $output, $returnCode);
        return $returnCode === 0;
    }

    public function findPython()
    {
        $configured = trim($this->settings['engines']['ema']['python_path'] ?? '');
        if ($configured !== '' &&
            stripos(str_replace('\\', '/', $configured), '/Microsoft/WindowsApps/') === false &&
            is_file($configured)) {
            return $configured;
        }

        $localAppData = getenv('LOCALAPPDATA');
        $candidates = [
            $localAppData . '/Programs/Python/Python313/python.exe',
            $localAppData . '/Programs/Python/Python312/python.exe',
            $localAppData . '/Programs/Python/Python311/python.exe',
            'C:/Python313/python.exe',
            'C:/Python312/python.exe',
            'C:/Python311/python.exe',
        ];

        foreach ($candidates as $candidate) {
            if ($candidate && is_file($candidate)) {
                return str_replace('/', DIRECTORY_SEPARATOR, $candidate);
            }
        }

        return null;
    }
}
