<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class VolunteerAiAssistant
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly ?string $apiKey,
        private readonly string $apiUrl,
        private readonly string $model,
    ) {
    }

    /** @return array{advice: string, source: string} */
    public function suggestForMission(string $title, string $description, string $location, string $period): array
    {
        $title = trim($title);
        $description = trim($description);
        $location = trim($location);
        $period = trim($period);

        if ($title === '' || $description === '') {
            throw new \InvalidArgumentException('Mission invalide pour assistance IA.');
        }

        if (!$this->apiKey) {
            return [
                'advice' => "Voici des conseils rapides avant de postuler:\n"
                    . "- Verifiez que vos disponibilites correspondent aux horaires.\n"
                    . "- Preparez une motivation courte et concrete.\n"
                    . "- Confirmez le lieu et le moyen de transport.",
                'source' => 'fallback',
            ];
        }

        $prompt = <<<PROMPT
Tu es assistant IA pour une plateforme de benevolat.
Donne une aide pratique au benevole pour cette mission.
Contraintes:
- Reponse en francais
- 90 a 140 mots
- 1 paragraphe + 3 puces actionnables
- Ne jamais inventer des informations non fournies
Reponds en JSON strict avec la cle:
- advice
PROMPT;

        $userMessage = sprintf(
            "Mission: %s\nLieu: %s\nPeriode: %s\nDescription: %s",
            $title,
            $location,
            $period,
            $description
        );

        $response = $this->httpClient->request('POST', $this->apiUrl, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => $prompt],
                    ['role' => 'user', 'content' => $userMessage],
                ],
                'temperature' => 0.4,
                'response_format' => ['type' => 'json_object'],
            ],
            'timeout' => 20,
        ]);

        $payload = $response->toArray(false);
        $raw = $payload['choices'][0]['message']['content'] ?? null;
        if (!is_string($raw) || trim($raw) === '') {
            throw new \RuntimeException('Reponse Gemini invalide.');
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            throw new \RuntimeException('Format Gemini JSON invalide.');
        }

        $advice = $this->sanitizeText($decoded['advice'] ?? '', true);
        if ($advice === '') {
            throw new \RuntimeException('Reponse Gemini vide.');
        }

        return [
            'advice' => $advice,
            'source' => 'ai',
        ];
    }

    private function sanitizeText(mixed $text, bool $allowParagraphs = false): string
    {
        if (!is_string($text)) {
            return '';
        }

        return $allowParagraphs
            ? $this->collapseSpacesWithParagraphs($text)
            : $this->collapseSpaces($text);
    }

    private function collapseSpaces(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        return preg_replace('/\s+/u', ' ', trim($text)) ?? trim($text);
    }

    private function collapseSpacesWithParagraphs(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", trim($text));
        $paragraphs = array_filter(array_map(static function (string $p): string {
            return preg_replace('/\s+/u', ' ', trim($p)) ?? trim($p);
        }, explode("\n", $text)));

        return implode("\n\n", $paragraphs);
    }
}
