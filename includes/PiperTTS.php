<?php

require_once __DIR__ . '/TTSSettings.php';

class PiperTTS
{
    private $settingsManager;
    private $settings;

    public function __construct(TTSSettings $settingsManager = null)
    {
        $this->settingsManager = $settingsManager ?: new TTSSettings();
        $this->settings = $this->settingsManager->getAll();
    }

    public function textToSpeech($text, $outputFile, $language = 'tr-TR')
    {
        $python = $this->findPython();
        $modelSetting = $this->settings['engines']['piper']['models'][$language] ?? '';
        $model = $this->settingsManager->resolvePath($modelSetting);
        $config = $model . '.json';

        if (!$python) {
            throw new RuntimeException('Piper için Python bulunamadı.');
        }
        if (!$model || !is_file($model)) {
            throw new RuntimeException("Piper modeli bulunamadı: {$model}");
        }
        if (!is_file($config)) {
            throw new RuntimeException("Piper model ayarı bulunamadı: {$config}");
        }

        $inputFile = dirname($outputFile) . '/piper_input_' . uniqid() . '.txt';
        file_put_contents($inputFile, $text, LOCK_EX);

        $command = escapeshellarg($python) .
            ' -m piper --model ' . escapeshellarg($model) .
            ' --config ' . escapeshellarg($config) .
            ' --input-file ' . escapeshellarg($inputFile) .
            ' --output-file ' . escapeshellarg($outputFile) .
            ' 2>&1';

        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);
        @unlink($inputFile);

        if ($returnCode !== 0 || !is_file($outputFile) || filesize($outputFile) === 0) {
            @unlink($outputFile);
            throw new RuntimeException('Piper TTS hatası: ' . implode("\n", $output));
        }

        return true;
    }

    public function isAvailable($language = 'tr-TR')
    {
        $modelSetting = $this->settings['engines']['piper']['models'][$language] ?? '';
        $model = $this->settingsManager->resolvePath($modelSetting);
        return $this->findPython() !== null && is_file($model) && is_file($model . '.json');
    }

    public function findPython()
    {
        $configured = trim($this->settings['engines']['piper']['python_path'] ?? '');
        if ($configured !== '' && is_file($configured)) {
            return $configured;
        }

        $candidates = [
            getenv('LOCALAPPDATA') . '/Programs/Python/Python313/python.exe',
            getenv('LOCALAPPDATA') . '/Programs/Python/Python312/python.exe',
            getenv('LOCALAPPDATA') . '/Programs/Python/Python311/python.exe',
            'C:/Python313/python.exe',
            'C:/Python312/python.exe',
        ];

        foreach ($candidates as $candidate) {
            if ($candidate && is_file($candidate)) {
                return str_replace('/', DIRECTORY_SEPARATOR, $candidate);
            }
        }

        return null;
    }
}
