<?php

namespace App\Service;

class MlModelScoringService
{
    /** @var array<string, mixed>|null */
    private ?array $model = null;
    private bool $loaded = false;

    public function __construct(
        private readonly string $modelPath,
    ) {
    }

    /** @param array<string, float|int> $features */
    public function predictProbability(array $features): ?float
    {
        $model = $this->loadModel();
        if ($model === null) {
            return null;
        }

        $featureNames = $model['feature_names'] ?? [];
        $means = $model['means'] ?? [];
        $stds = $model['stds'] ?? [];
        $weights = $model['weights'] ?? [];
        $bias = (float) ($model['bias'] ?? 0.0);

        if (!is_array($featureNames) || !is_array($weights)) {
            return null;
        }

        $z = $bias;
        foreach ($featureNames as $index => $name) {
            $raw = (float) ($features[$name] ?? 0.0);
            $mean = (float) ($means[$index] ?? 0.0);
            $std = (float) ($stds[$index] ?? 1.0);
            if ($std <= 0.0) {
                $std = 1.0;
            }

            $normalized = ($raw - $mean) / $std;
            $z += ((float) ($weights[$index] ?? 0.0)) * $normalized;
        }

        return $this->sigmoid($z);
    }

    public function getTrainingRows(): int
    {
        $model = $this->loadModel();
        if ($model === null) {
            return 0;
        }

        return (int) (($model['metrics']['train_rows'] ?? 0));
    }

    /** @return array<string, mixed>|null */
    private function loadModel(): ?array
    {
        if ($this->loaded) {
            return $this->model;
        }

        $this->loaded = true;

        if (!is_file($this->modelPath)) {
            return null;
        }

        $json = file_get_contents($this->modelPath);
        if ($json === false || $json === '') {
            return null;
        }

        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            return null;
        }

        $this->model = $decoded;

        return $this->model;
    }

    private function sigmoid(float $z): float
    {
        if ($z < -35.0) {
            return 0.0;
        }

        if ($z > 35.0) {
            return 1.0;
        }

        return 1.0 / (1.0 + exp(-$z));
    }
}
