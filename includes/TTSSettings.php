<?php

class TTSSettings
{
    private $settingsFile;

    public function __construct($settingsFile = null)
    {
        $this->settingsFile = $settingsFile ?: dirname(__DIR__) . '/tts_settings.json';
    }

    public function getAll()
    {
        $defaults = $this->getDefaults();
        if (!is_file($this->settingsFile)) {
            return $defaults;
        }

        $saved = json_decode(file_get_contents($this->settingsFile), true);
        if (!is_array($saved)) {
            return $defaults;
        }

        return array_replace_recursive($defaults, $saved);
    }

    public function save(array $settings)
    {
        $allowedEngines = ['windows', 'ema', 'google', 'azure'];
        $language = in_array($settings['default_language'] ?? '', ['tr-TR', 'en-US'], true)
            ? $settings['default_language']
            : 'tr-TR';

        $priority = array_values(array_unique(array_filter(
            $settings['engine_priority'] ?? [],
            function ($engine) use ($allowedEngines) {
                return in_array($engine, $allowedEngines, true);
            }
        )));

        foreach ($allowedEngines as $engine) {
            if (!in_array($engine, $priority, true)) {
                $priority[] = $engine;
            }
        }

        $current = $this->getAll();
        $current['default_language'] = $language;
        $current['engine_priority'] = $priority;
        $speed = (float)($settings['speech_speed'] ?? 0.85);
        $current['speech_speed'] = max(0.5, min(1.5, $speed));

        foreach ($allowedEngines as $engine) {
            $current['engines'][$engine]['enabled'] =
                !empty($settings['engines'][$engine]['enabled']);
        }

        $current['engines']['windows']['voices']['tr-TR'] =
            trim($settings['engines']['windows']['voices']['tr-TR'] ?? '');
        $current['engines']['windows']['voices']['en-US'] =
            trim($settings['engines']['windows']['voices']['en-US'] ?? '');
        $current['engines']['ema']['python_path'] =
            trim($settings['engines']['ema']['python_path'] ?? '');
        $current['engines']['google']['voices']['tr-TR'] =
            trim($settings['engines']['google']['voices']['tr-TR'] ?? '');
        $current['engines']['google']['voices']['en-US'] =
            trim($settings['engines']['google']['voices']['en-US'] ?? '');
        $current['engines']['azure']['region'] =
            trim($settings['engines']['azure']['region'] ?? 'westeurope');
        $current['engines']['azure']['voices']['tr-TR'] =
            trim($settings['engines']['azure']['voices']['tr-TR'] ?? '');
        $current['engines']['azure']['voices']['en-US'] =
            trim($settings['engines']['azure']['voices']['en-US'] ?? '');

        $json = json_encode($current, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (file_put_contents($this->settingsFile, $json . PHP_EOL, LOCK_EX) === false) {
            throw new RuntimeException('TTS ayarları kaydedilemedi.');
        }

        return $current;
    }

    public function resolvePath($path)
    {
        if ($path === '') {
            return '';
        }

        if (preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) || strpos($path, '/') === 0) {
            return $path;
        }

        return dirname(__DIR__) . '/' . ltrim($path, '/\\');
    }

    private function getDefaults()
    {
        return [
            'default_language' => 'tr-TR',
            'speech_speed' => 0.85,
            'engine_priority' => ['windows', 'ema', 'google', 'azure'],
            'engines' => [
                'windows' => [
                    'enabled' => true,
                    'voices' => [
                        'tr-TR' => 'Vocalizer Expressive Yelda Harpo 22kHz',
                        'en-US' => 'Microsoft Zira Desktop',
                    ],
                ],
                'ema' => [
                    'enabled' => true,
                    'python_path' => '',
                ],
                'google' => [
                    'enabled' => true,
                    'voices' => [
                        'tr-TR' => 'tr-TR-Wavenet-A',
                        'en-US' => 'en-US-Wavenet-F',
                    ],
                ],
                'azure' => [
                    'enabled' => false,
                    'region' => 'westeurope',
                    'voices' => [
                        'tr-TR' => 'tr-TR-EmelNeural',
                        'en-US' => 'en-US-JennyNeural',
                    ],
                ],
            ],
        ];
    }
}
