<?php

namespace App\Service;

use App\Entity\Fiche;
use App\Repository\MedicamentRepository;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MedicalAiService
{
    private const DEFAULT_OPENAI_MODEL = 'gpt-4o';
    private const DEFAULT_GROK_MODEL = 'grok-2-1212';
    private const GEMINI_MODEL = 'gemini-1.5-flash';
    private const GROK_API_URL = 'https://api.x.ai/v1/chat/completions';
    private const GEMINI_API_URL = 'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent';
    private const CACHE_TTL_SECONDS = 300; // 5 minutes
    private const RETRY_DELAY_SECONDS = 3;
    private const MAX_ATTEMPTS = 2;

    private string $resolvedOpenAiModel;
    private string $resolvedGrokModel;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly MedicamentRepository $medicamentRepository,
        private readonly CacheInterface $cache,
        private readonly ?string $openAiApiKey = null,
        ?string $openAiModel = self::DEFAULT_OPENAI_MODEL,
        private readonly ?string $grokApiKey = null,
        ?string $grokModel = self::DEFAULT_GROK_MODEL,
        private readonly ?string $geminiApiKey = null
    ) {
        $this->resolvedOpenAiModel = $openAiModel ?: self::DEFAULT_OPENAI_MODEL;
        $this->resolvedGrokModel = $grokModel ?: self::DEFAULT_GROK_MODEL;
    }

    /** Suggestions for display: uses Gemini first (per spec), then Grok, then fallback. */
    /** @return array<string, mixed> */
    public function generateSuggestions(Fiche $fiche): array
    {
        $catalog = $this->buildMedicationCatalog();
        $disclaimer = 'Suggestion IA uniquement: validation medicale obligatoire par un medecin avant prescription.';

        if ($catalog === []) {
            return [
                'success' => false,
                'source' => 'none',
                'disclaimer' => $disclaimer,
                'items' => [],
                'error' => 'Aucun medicament disponible dans la base.',
            ];
        }

        $cacheKey = $this->getCacheKey($fiche);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($fiche, $catalog, $disclaimer) {
            $item->expiresAfter(self::CACHE_TTL_SECONDS);
            return $this->generateSuggestionsUncached($fiche, $catalog, $disclaimer);
        });
    }

    /** Suggestions for ordonnance auto-fill: uses OpenAI only. */
    /** @return array<string, mixed> */
    public function generateSuggestionsForOrdonnance(Fiche $fiche): array
    {
        $catalog = $this->buildMedicationCatalog();
        $disclaimer = 'Suggestion IA uniquement: validation medicale obligatoire par un medecin avant prescription.';

        if ($catalog === []) {
            return [
                'success' => false,
                'source' => 'none',
                'disclaimer' => $disclaimer,
                'items' => [],
                'error' => 'Aucun medicament disponible dans la base.',
            ];
        }

        return $this->generateSuggestionsWithOpenAi($fiche, $catalog, $disclaimer);
    }

    private function getCacheKey(Fiche $fiche): string
    {
        $data = $fiche->getId() . '|' . $fiche->getSymptomes() . '|' . $fiche->getTension()
            . '|' . $fiche->getGlycemie() . '|' . $fiche->getPoids() . '|' . $fiche->getLibelleMaladie() . '|' . $fiche->getGravite();

        return 'medical_ai_fiche_' . md5($data);
    }

    /**
     * @param list<array<string, mixed>> $catalog
     * @return array<string, mixed>
     */
    private function generateSuggestionsUncached(Fiche $fiche, array $catalog, string $disclaimer): array
    {
        $lastFallback = null;
        if ((string) $this->geminiApiKey !== '') {
            $result = $this->generateSuggestionsWithGemini($fiche, $catalog, $disclaimer);
            if (($result['source'] ?? '') !== 'fallback') {
                return $result;
            }
            $lastFallback = $result;
        }
        if ((string) $this->grokApiKey !== '') {
            $result = $this->generateSuggestionsWithGrok($fiche, $catalog, $disclaimer);
            if (($result['source'] ?? '') !== 'fallback') {
                return $result;
            }
            $lastFallback = $result;
        }
        return $lastFallback ?? $this->buildFallbackResponse($fiche, $catalog, $disclaimer, 'Aucune cle API IA configuree (Gemini/Grok), fallback local utilise.');
    }

    /**
     * @param list<array<string, mixed>> $catalog
     * @return array<string, mixed>
     */
    private function generateSuggestionsWithGemini(Fiche $fiche, array $catalog, string $disclaimer): array
    {
        try {
            if ((string) $this->geminiApiKey === '') {
                return $this->buildFallbackResponse($fiche, $catalog, $disclaimer, 'Cle Gemini absente.');
            }

            $url = sprintf(self::GEMINI_API_URL, self::GEMINI_MODEL) . '?key=' . urlencode($this->geminiApiKey);
            $prompt = $this->buildGeminiPrompt($fiche, $catalog);

            $response = $this->httpClient->request(
                'POST',
                $url,
                [
                    'headers' => ['Content-Type' => 'application/json'],
                    'json' => [
                        'contents' => [['parts' => [['text' => $prompt]]]],
                        'generationConfig' => ['temperature' => 0.2],
                    ],
                    'timeout' => 25,
                ]
            );

            $payload = $response->toArray();
            $text = $payload['candidates'][0]['content']['parts'][0]['text'] ?? '';
            $parsed = $this->extractSuggestionsFromText($text, $catalog);

            if ($parsed === []) {
                return $this->buildFallbackResponse($fiche, $catalog, $disclaimer, 'Reponse Gemini vide ou invalide.');
            }

            return [
                'success' => true,
                'source' => 'gemini',
                'disclaimer' => $disclaimer,
                'items' => $parsed,
                'error' => null,
            ];
        } catch (\Throwable $e) {
            return $this->buildFallbackResponse($fiche, $catalog, $disclaimer, 'Service IA (Gemini) indisponible: ' . $e->getMessage());
        }
    }

    /** @param list<array<string, mixed>> $catalog */
    private function buildGeminiPrompt(Fiche $fiche, array $catalog): string
    {
        $patientData = [
            'symptomes' => $fiche->getSymptomes(),
            'tension' => $fiche->getTension(),
            'glycemie' => $fiche->getGlycemie(),
            'poids' => $fiche->getPoids(),
            'libelleMaladie' => $fiche->getLibelleMaladie(),
            'gravite' => $fiche->getGravite(),
        ];

        return "You are a Medical Assistant. STRICTLY choose medications ONLY from the provided catalog below. Do not suggest any medication that is not in the list.\n\n"
            . "Patient data:\n" . json_encode($patientData, JSON_PRETTY_PRINT)
            . "\n\nAvailable medications (you MUST use only these IDs):\n" . json_encode($catalog, JSON_PRETTY_PRINT)
            . "\n\nReturn STRICT JSON only, no markdown:\n"
            . '{ "suggestions": [ { "medicamentId": int, "nbJours": int, "frequenceParJour": int, "momentPrise": "Matin"|"Soir"|"Nuit"|"Matin et Soir", "avantRepas": bool, "periode": "Quotidien", "reason": string } ] }';
    }

    /**
     * @param list<array<string, mixed>> $catalog
     * @return array<string, mixed>
     */
    private function generateSuggestionsWithGrok(Fiche $fiche, array $catalog, string $disclaimer): array
    {
        try {
            if ((string) $this->grokApiKey === '') {
                return $this->buildFallbackResponse($fiche, $catalog, $disclaimer, 'Cle Grok absente, fallback local utilise.');
            }

            $response = $this->httpClient->request(
                'POST',
                self::GROK_API_URL,
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->grokApiKey,
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'model' => $this->resolvedGrokModel,
                        'temperature' => 0.2,
                        'messages' => [
                            [
                                'role' => 'system',
                                'content' => 'You are a Medical Assistant. Based on these symptoms and vitals, suggest the most appropriate medications from the provided list. Specify the dosage frequency and duration.',
                            ],
                            [
                                'role' => 'user',
                                'content' => $this->buildUserPrompt($fiche, $catalog),
                            ],
                        ],
                    ],
                    'timeout' => 25,
                ]
            );

            $payload = $response->toArray();
            $text = $this->normalizeAssistantContent($payload['choices'][0]['message']['content'] ?? '');
            $parsed = $this->extractSuggestionsFromText($text, $catalog);

            if ($parsed === []) {
                return $this->buildFallbackResponse($fiche, $catalog, $disclaimer, 'Reponse Grok vide ou invalide, fallback local utilise.');
            }

            return [
                'success' => true,
                'source' => 'grok',
                'disclaimer' => $disclaimer,
                'items' => $parsed,
                'error' => null,
            ];
        } catch (\Throwable $e) {
            return $this->buildFallbackResponse($fiche, $catalog, $disclaimer, 'Service IA (Grok) indisponible: ' . $e->getMessage());
        }
    }

    /**
     * @param list<array<string, mixed>> $catalog
     * @return array<string, mixed>
     */
    private function generateSuggestionsWithOpenAi(Fiche $fiche, array $catalog, string $disclaimer): array
    {
        try {
            if ((string) $this->openAiApiKey === '') {
                return $this->buildFallbackResponse($fiche, $catalog, $disclaimer, 'Cle OpenAI absente, fallback local utilise.');
            }

            $lastException = null;
            for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
                try {
                    $response = $this->httpClient->request(
                        'POST',
                        'https://api.openai.com/v1/chat/completions',
                        [
                            'headers' => [
                                'Authorization' => 'Bearer ' . $this->openAiApiKey,
                                'Content-Type' => 'application/json',
                            ],
                            'json' => [
                                'model' => $this->resolvedOpenAiModel,
                                'temperature' => 0.2,
                                'messages' => [
                                    [
                                        'role' => 'system',
                                        'content' => 'You are a Medical Assistant. Based on these symptoms and vitals, suggest the most appropriate medications from the provided list. Specify the dosage frequency and duration.',
                                    ],
                                    [
                                        'role' => 'user',
                                        'content' => $this->buildUserPrompt($fiche, $catalog),
                                    ],
                                ],
                            ],
                            'timeout' => 20,
                        ]
                    );

                    $payload = $response->toArray();
                    $text = $this->normalizeAssistantContent($payload['choices'][0]['message']['content'] ?? '');
                    $parsed = $this->extractSuggestionsFromText($text, $catalog);

                    if ($parsed === []) {
                        return $this->buildFallbackResponse($fiche, $catalog, $disclaimer, 'Reponse OpenAI vide ou invalide, fallback local utilise.');
                    }

                    return [
                        'success' => true,
                        'source' => 'openai',
                        'disclaimer' => $disclaimer,
                        'items' => $parsed,
                        'error' => null,
                    ];
                } catch (ClientExceptionInterface $e) {
                    $lastException = $e;
                    $statusCode = $e->getResponse()->getStatusCode();
                    if ($statusCode === 429 && $attempt < self::MAX_ATTEMPTS) {
                        sleep(self::RETRY_DELAY_SECONDS);
                        continue;
                    }
                    throw $e;
                }
            }
            throw $lastException;
        } catch (\Throwable $e) {
            return $this->buildFallbackResponse($fiche, $catalog, $disclaimer, 'Service IA (OpenAI) indisponible: ' . $e->getMessage());
        }
    }

    /** @return list<array<string, mixed>> */
    private function buildMedicationCatalog(): array
    {
        $medicaments = $this->medicamentRepository->findAll();
        $catalog = [];

        foreach ($medicaments as $medicament) {
            $catalog[] = [
                'id' => $medicament->getId(),
                'name' => $medicament->getNomMedicament(),
                'dosage' => $medicament->getDosage(),
                'forme' => $medicament->getForme(),
                'categorie' => $medicament->getCategorie(),
            ];
        }

        return $catalog;
    }

    /** @param list<array<string, mixed>> $catalog */
    private function buildUserPrompt(Fiche $fiche, array $catalog): string
    {
        $patientData = [
            'symptomes' => $fiche->getSymptomes(),
            'tension' => $fiche->getTension(),
            'glycemie' => $fiche->getGlycemie(),
            'poids' => $fiche->getPoids(),
            'libelleMaladie' => $fiche->getLibelleMaladie(),
            'gravite' => $fiche->getGravite(),
        ];

        return "You are a Medical Assistant. STRICTLY choose medications ONLY from the provided list. Do not suggest any medication not in the list.\n\n"
            . "Patient data:\n" . json_encode($patientData, JSON_PRETTY_PRINT)
            . "\n\nAvailable medications (use only these IDs):\n" . json_encode($catalog, JSON_PRETTY_PRINT)
            . "\n\nReturn STRICT JSON: { \"suggestions\": [ { \"medicamentId\": int, \"nbJours\": int, \"frequenceParJour\": int, \"momentPrise\": string, \"avantRepas\": bool, \"periode\": string, \"reason\": string } ] }";
    }

    /** @param string|list<array<string, mixed>> $content */
    private function normalizeAssistantContent(string|array $content): string
    {
        if (is_string($content)) {
            return $content;
        }

        $chunks = [];
        foreach ($content as $block) {
            if (isset($block['text']) && is_string($block['text'])) {
                $chunks[] = $block['text'];
            }
        }

        return implode("\n", $chunks);
    }

    /**
     * @param list<array<string, mixed>> $catalog
     * @return list<array<string, mixed>>
     */
    private function extractSuggestionsFromText(string $text, array $catalog): array
    {
        $clean = trim($text);
        $clean = preg_replace('/^```json\s*/', '', $clean) ?? $clean;
        $clean = preg_replace('/^```\s*/', '', $clean) ?? $clean;
        $clean = preg_replace('/\s*```$/', '', $clean) ?? $clean;

        if ($clean === '') {
            return [];
        }

        $decoded = json_decode($clean, true);
        if (!is_array($decoded)) {
            return [];
        }

        $rawSuggestions = $decoded['suggestions'] ?? [];
        if (!is_array($rawSuggestions)) {
            return [];
        }

        $catalogById = [];
        foreach ($catalog as $entry) {
            $catalogById[(int) $entry['id']] = $entry;
        }

        $normalized = [];
        foreach ($rawSuggestions as $item) {
            if (!is_array($item)) {
                continue;
            }
            $medicamentId = (int) ($item['medicamentId'] ?? $item['medicament_id'] ?? 0);
            if ($medicamentId <= 0) {
                continue;
            }
            if (!isset($catalogById[$medicamentId])) {
                continue;
            }

            $med = $catalogById[$medicamentId];
            $normalized[] = [
                'medicamentId' => $medicamentId,
                'medicamentName' => $med['name'],
                'nbJours' => max(1, (int) ($item['nbJours'] ?? 7)),
                'frequenceParJour' => max(1, (int) ($item['frequenceParJour'] ?? 1)),
                'momentPrise' => (string) ($item['momentPrise'] ?? 'Matin'),
                'avantRepas' => (bool) ($item['avantRepas'] ?? false),
                'periode' => (string) ($item['periode'] ?? 'Quotidien'),
                'reason' => (string) ($item['reason'] ?? 'Suggestion IA basee sur les donnees patient.'),
            ];
        }

        return array_slice($normalized, 0, 5);
    }

    /**
     * @param list<array<string, mixed>> $catalog
     * @return array<string, mixed>
     */
    private function buildFallbackResponse(Fiche $fiche, array $catalog, string $disclaimer, string $error): array
    {
        $symptomes = strtolower((string) $fiche->getSymptomes());
        $scored = [];

        foreach ($catalog as $entry) {
            $score = 0;
            $name = strtolower((string) $entry['name']);
            $cat = strtolower((string) $entry['categorie']);

            if (str_contains($symptomes, 'douleur') && (str_contains($cat, 'antalg') || str_contains($name, 'doliprane'))) {
                $score += 3;
            }
            if ((str_contains($symptomes, 'fiev') || str_contains($symptomes, 'gorge')) && (str_contains($cat, 'antibiot') || str_contains($name, 'amoxic'))) {
                $score += 2;
            }
            if (str_contains($symptomes, 'allerg') && (str_contains($cat, 'antihist') || str_contains($name, 'zyrtec'))) {
                $score += 3;
            }
            if (str_contains($symptomes, 'spasm') && (str_contains($cat, 'spasmo') || str_contains($name, 'spasfon'))) {
                $score += 3;
            }

            $score += 1;
            $entry['score'] = $score;
            $scored[] = $entry;
        }

        usort($scored, static fn (array $a, array $b): int => $b['score'] <=> $a['score']);

        $items = [];
        foreach (array_slice($scored, 0, 3) as $entry) {
            $items[] = [
                'medicamentId' => (int) $entry['id'],
                'medicamentName' => (string) $entry['name'],
                'nbJours' => 7,
                'frequenceParJour' => 2,
                'momentPrise' => 'Matin et Soir',
                'avantRepas' => false,
                'periode' => 'Quotidien',
                'reason' => 'Suggestion locale de secours basee sur les symptomes.',
            ];
        }

        return [
            'success' => true,
            'source' => 'fallback',
            'disclaimer' => $disclaimer,
            'items' => $items,
            'error' => $error,
        ];
    }
}
