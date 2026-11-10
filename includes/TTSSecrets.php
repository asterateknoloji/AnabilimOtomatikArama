<?php

class TTSSecrets
{
    private $file;

    public function __construct($file = null)
    {
        if ($file !== null) {
            $this->file = $file;
            return;
        }

        // Proje kökündeki JSON dosyaları .htaccess ile dış erişime kapalıdır.
        // Bu konum XAMPP'nin kendi klasörüne göre daha taşınabilir ve yazılabilirdir.
        $this->file = dirname(__DIR__) . '/tts_secrets.json';

        // Önceki sürümde XAMPP köküne kaydedilmiş dosya varsa bir kez taşı.
        $legacyFile = dirname(__DIR__, 3) . '/tts_secrets.json';
        if (!is_file($this->file) && is_file($legacyFile)) {
            @copy($legacyFile, $this->file);
        }
    }

    public function getAll()
    {
        if (!is_file($this->file)) {
            return ['google_api_key' => '', 'azure_key' => ''];
        }

        $data = json_decode(file_get_contents($this->file), true);
        return is_array($data)
            ? array_merge(['google_api_key' => '', 'azure_key' => ''], $data)
            : ['google_api_key' => '', 'azure_key' => ''];
    }

    public function save($googleApiKey = null, $azureKey = null)
    {
        $current = $this->getAll();
        if ($googleApiKey !== null && trim($googleApiKey) !== '') {
            $current['google_api_key'] = trim($googleApiKey);
        }
        if ($azureKey !== null && trim($azureKey) !== '') {
            $current['azure_key'] = trim($azureKey);
        }

        $json = json_encode($current, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if (file_put_contents($this->file, $json . PHP_EOL, LOCK_EX) === false) {
            throw new RuntimeException('TTS API anahtarları kaydedilemedi: ' . $this->file);
        }

        return $current;
    }
}
