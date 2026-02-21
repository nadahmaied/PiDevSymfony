<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class ForumAiAssistant
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly ?string $apiKey,
        private readonly string $apiUrl,
        private readonly string $model,
    ) {
    }

    public function enhanceQuestion(string $title, string $content): array
    {
        $title = trim($title);
        $content = trim($content);

        if ($title === '' || $content === '') {
            throw new \InvalidArgumentException('Le titre et le contenu sont requis.');
        }

        if (!$this->apiKey) {
            return $this->fallbackEnhancement($title, $content);
        }

        $prompt = <<<PROMPT
Tu es un assistant de rédaction pour un forum santé.
Améliore le texte en français clair, poli et précis.
Réponds en JSON strict avec les clés:
- title
- content
- tags (tableau de 3 à 5 chaînes courtes)
Ne mets aucun texte hors JSON.
PROMPT;

        $userMessage = sprintf(
            "Titre:\n%s\n\nContenu:\n%s",
            $title,
            $content
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
        $contentRaw = $payload['choices'][0]['message']['content'] ?? null;

        if (!is_string($contentRaw) || trim($contentRaw) === '') {
            throw new \RuntimeException('Réponse IA invalide.');
        }

        $decoded = json_decode($contentRaw, true);
        if (!is_array($decoded)) {
            throw new \RuntimeException('Format JSON IA invalide.');
        }

        $enhancedTitle = $this->sanitizeText($decoded['title'] ?? $title);
        $enhancedContent = $this->sanitizeText($decoded['content'] ?? $content, true);
        $tags = $this->sanitizeTags($decoded['tags'] ?? []);

        return [
            'title' => $enhancedTitle ?: $title,
            'content' => $enhancedContent ?: $content,
            'tags' => $tags,
            'source' => 'ai',
        ];
    }

    private function fallbackEnhancement(string $title, string $content): array
    {
        $enhancedTitle = ucfirst($this->collapseSpaces($title));
        $enhancedContent = $this->collapseSpacesWithParagraphs($content);

        if (!str_ends_with($enhancedTitle, '?') && mb_strlen($enhancedTitle) < 120) {
            $enhancedTitle .= ' ?';
        }

        return [
            'title' => $enhancedTitle,
            'content' => $enhancedContent,
            'tags' => $this->extractBasicTags($title . ' ' . $content),
            'source' => 'fallback',
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

    private function sanitizeTags(mixed $tags): array
    {
        if (!is_array($tags)) {
            return [];
        }

        $cleanTags = [];
        foreach ($tags as $tag) {
            if (!is_string($tag)) {
                continue;
            }
            $tag = $this->collapseSpaces($tag);
            if ($tag === '') {
                continue;
            }
            $cleanTags[] = $tag;
        }

        return array_slice(array_values(array_unique($cleanTags)), 0, 5);
    }

    private function collapseSpaces(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/\s+/u', ' ', trim($text)) ?? trim($text);

        return $text;
    }

    private function collapseSpacesWithParagraphs(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", trim($text));
        $paragraphs = array_filter(array_map(static function (string $p): string {
            return preg_replace('/\s+/u', ' ', trim($p)) ?? trim($p);
        }, explode("\n", $text)));

        return implode("\n\n", $paragraphs);
    }

    private function extractBasicTags(string $text): array
    {
        $text = mb_strtolower($text);
        $dictionary = [
            'douleur' => 'Douleur',
            'fièvre' => 'Fievre',
            'fievre' => 'Fievre',
            'nutrition' => 'Nutrition',
            'stress' => 'Stress',
            'sommeil' => 'Sommeil',
            'sport' => 'Sport',
            'medicament' => 'Medicament',
            'médicament' => 'Medicament',
            'fatigue' => 'Fatigue',
        ];

        $tags = [];
        foreach ($dictionary as $keyword => $tag) {
            if (str_contains($text, $keyword)) {
                $tags[] = $tag;
            }
        }

        if (count($tags) === 0) {
            $tags[] = 'Sante';
        }

        return array_slice(array_values(array_unique($tags)), 0, 5);
    }
}

