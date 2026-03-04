<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class ForumModerationAiService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly ?string $apiKey,
        private readonly string $apiUrl,
        private readonly string $model,
    ) {
    }

    /**
     * @return array{
     *   status: 'safe'|'review'|'blocked',
     *   toxicity: float,
     *   sensitive: float,
     *   medicalRisk: float,
     *   reasons: list<string>,
     *   source: string
     * }
     */
    public function analyze(string $text): array
    {
        $text = trim($text);
        if ($text === '') {
            return [
                'status' => 'safe',
                'toxicity' => 0.0,
                'sensitive' => 0.0,
                'medicalRisk' => 0.0,
                'reasons' => [],
                'source' => 'fallback',
            ];
        }

        if (!$this->apiKey) {
            return $this->fallbackAnalyze($text);
        }

        try {
            $prompt = <<<PROMPT
Tu es un moderateur IA d'un forum sante.
Donne uniquement un JSON strict avec:
- toxicity (0..1)
- sensitive (0..1)
- medical_risk (0..1)
- reasons (array de 1 a 4 chaines courtes)
Ne mets aucun texte hors JSON.
PROMPT;

            $response = $this->httpClient->request('POST', $this->apiUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $this->model,
                    'messages' => [
                        ['role' => 'system', 'content' => $prompt],
                        ['role' => 'user', 'content' => $text],
                    ],
                    'temperature' => 0.0,
                    'response_format' => ['type' => 'json_object'],
                ],
                'timeout' => 20,
            ]);

            $payload = $response->toArray(false);
            $contentRaw = $payload['choices'][0]['message']['content'] ?? '';
            $decoded = json_decode((string) $contentRaw, true);

            if (!\is_array($decoded)) {
                return $this->fallbackAnalyze($text);
            }

            $toxicity = $this->clamp01((float) ($decoded['toxicity'] ?? 0.0));
            $sensitive = $this->clamp01((float) ($decoded['sensitive'] ?? 0.0));
            $medicalRisk = $this->clamp01((float) ($decoded['medical_risk'] ?? 0.0));
            $reasons = $this->normalizeReasons($decoded['reasons'] ?? []);

            return [
                'status' => $this->resolveStatus($toxicity, $sensitive, $medicalRisk),
                'toxicity' => $toxicity,
                'sensitive' => $sensitive,
                'medicalRisk' => $medicalRisk,
                'reasons' => $reasons,
                'source' => 'ai',
            ];
        } catch (\Throwable) {
            return $this->fallbackAnalyze($text);
        }
    }

    /** @return array{status: 'safe'|'review'|'blocked', toxicity: float, sensitive: float, medicalRisk: float, reasons: list<string>, source: string} */
    private function fallbackAnalyze(string $text): array
    {
        $content = mb_strtolower($text);

        $aggressivePatterns = ['idiot', 'stupide', 'imbecile', 'imbécile', 'nul', 'haine', 'ta gueule', 'ferme la'];
        $sensitivePatterns = ['suicide', 'tuer', 'tue', 'poison', 'overdose', 'violence', 'arme'];
        $medicalRiskPatterns = [
            'arrete ton traitement',
            'arrête ton traitement',
            'ne prends plus tes medicaments',
            'ne prends plus tes médicaments',
            'les vaccins tuent',
            'insuline inutile',
            'antibiotique pour virus',
            'dose double',
        ];

        $toxicityHits = $this->countHits($content, $aggressivePatterns);
        $sensitiveHits = $this->countHits($content, $sensitivePatterns);
        $medicalHits = $this->countHits($content, $medicalRiskPatterns);

        $toxicity = $this->clamp01($toxicityHits / 3.0);
        $sensitive = $this->clamp01($sensitiveHits / 2.0);
        $medicalRisk = $this->clamp01($medicalHits / 2.0);

        $reasons = [];
        if ($toxicityHits > 0) {
            $reasons[] = 'Langage agressif detecte';
        }
        if ($sensitiveHits > 0) {
            $reasons[] = 'Contenu potentiellement dangereux detecte';
        }
        if ($medicalHits > 0) {
            $reasons[] = 'Conseil medical risque detecte';
        }

        return [
            'status' => $this->resolveStatus($toxicity, $sensitive, $medicalRisk),
            'toxicity' => $toxicity,
            'sensitive' => $sensitive,
            'medicalRisk' => $medicalRisk,
            'reasons' => $reasons,
            'source' => 'fallback',
        ];
    }

    /** @return 'safe'|'review'|'blocked' */
    private function resolveStatus(float $toxicity, float $sensitive, float $medicalRisk): string
    {
        if ($toxicity >= 0.85 || $sensitive >= 0.90 || $medicalRisk >= 0.90) {
            return 'blocked';
        }

        if ($toxicity >= 0.55 || $sensitive >= 0.60 || $medicalRisk >= 0.60) {
            return 'review';
        }

        return 'safe';
    }

    /**
     * @param array<int, mixed> $raw
     * @return list<string>
     */
    private function normalizeReasons(array $raw): array
    {
        $reasons = [];
        foreach ($raw as $reason) {
            if (!\is_string($reason)) {
                continue;
            }
            $value = trim($reason);
            if ($value !== '') {
                $reasons[] = $value;
            }
        }

        return array_slice(array_values(array_unique($reasons)), 0, 4);
    }

    /** @param list<string> $patterns */
    private function countHits(string $content, array $patterns): int
    {
        $hits = 0;
        foreach ($patterns as $pattern) {
            if (str_contains($content, $pattern)) {
                ++$hits;
            }
        }

        return $hits;
    }

    private function clamp01(float $value): float
    {
        return max(0.0, min(1.0, $value));
    }
}
