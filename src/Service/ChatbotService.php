<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\FicheRepository;
use App\Repository\OrdonnanceRepository;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ChatbotService
{
    private const OPENAI_API_URL = 'https://api.openai.com/v1/chat/completions';
    private const GROQ_API_URL = 'https://api.groq.com/openai/v1/chat/completions';
    private const GROK_API_URL = 'https://api.x.ai/v1/chat/completions';
    private const GEMINI_API_URL = 'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent';
    private const GEMINI_MODEL = 'gemini-1.5-flash';
    private const GROQ_MODEL = 'llama-3.1-8b-instant';
    private const GROK_MODEL = 'grok-2-1212';
    private const OLLAMA_DEFAULT_URL = 'http://localhost:11434';
    private const SYSTEM_PROMPT_TEMPLATE = 'You are DEV, a friendly medical assistant for this patient\'s dossier. You have access ONLY to the patient data below.

RULES:
1) GREETINGS (hi, hey, bonjour, salut, etc.): Respond warmly and briefly, then invite them to ask about their medical record. Example: "Bonjour ! Comment puis-je vous aider avec votre dossier médical aujourd\'hui ?"
2) MEDICAL QUESTIONS (prescriptions, symptoms, medications, allergies, chronic conditions, etc.): Answer STRICTLY from the data below. Go straight to the answer—do NOT repeat greetings like "Bonjour ! Comment puis-je vous aider...". Do NOT invent or assume. If the data does not contain the answer, say "Cette information n\'est pas disponible dans le dossier."
3) UNRELATED QUESTIONS (weather, news, general knowledge, jokes, etc.): Do NOT answer. Politely decline: "Je suis uniquement un assistant pour votre dossier médical. Je ne peux répondre qu\'aux questions sur vos prescriptions, symptômes ou médicaments. Que souhaitez-vous savoir ?"

Patient data: %s';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly FicheRepository $ficheRepository,
        private readonly OrdonnanceRepository $ordonnanceRepository,
        private readonly ?LoggerInterface $logger = null,
        private readonly ?string $openAiApiKey = null,
        private readonly string $openAiModel = 'gpt-4o',
        private readonly ?string $groqApiKey = null,
        private readonly ?string $grokApiKey = null,
        private readonly ?string $geminiApiKey = null,
        private readonly ?string $ollamaUrl = null,
        private readonly string $ollamaModel = 'llama3.2'
    ) {
    }

    /**
     * Sends a query to the chatbot and returns the AI response.
     * Fallback chain: OpenAI → Grok → Gemini
     *
     * @return array{success: bool, message: string, error?: string}
     */
    public function ask(User $patient, string $query): array
    {
        $context = $this->buildContext($patient);
        if ($context === '') {
            return [
                'success' => false,
                'message' => '',
                'error' => 'Aucune donnée médicale trouvée pour ce patient.',
            ];
        }

        $systemPrompt = sprintf(self::SYSTEM_PROMPT_TEMPLATE, $context);
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $query],
        ];

        $lastError = 'Aucune clé API IA configurée (Groq, OpenAI, Grok, Gemini).';

        if ((string) $this->groqApiKey !== '') {
            $result = $this->callGroq($messages);
            if ($result !== null) {
                return $result;
            }
            $lastError = 'Groq indisponible.';
        }

        if ((string) $this->openAiApiKey !== '') {
            $result = $this->callOpenAi($messages);
            if ($result !== null) {
                return $result;
            }
            $lastError = 'OpenAI indisponible (429 ou erreur).';
        }

        if ((string) $this->grokApiKey !== '') {
            $result = $this->callGrok($messages);
            if ($result !== null) {
                return $result;
            }
            $lastError = 'Grok indisponible.';
        }

        if ((string) $this->geminiApiKey !== '') {
            $result = $this->callGemini($systemPrompt, $query);
            if ($result !== null) {
                return $result;
            }
            $lastError = 'Gemini indisponible.';
        }

        $ollamaBase = ($this->ollamaUrl !== null && $this->ollamaUrl !== '') ? $this->ollamaUrl : self::OLLAMA_DEFAULT_URL;
        $result = $this->callOllama($ollamaBase, $messages);
        if ($result !== null) {
            return $result;
        }

        return [
            'success' => false,
            'message' => '',
            'error' => 'Tous les services IA sont indisponibles. ' . $lastError . ' Lancez Ollama en local: ollama run ' . $this->ollamaModel,
        ];
    }

    private function callOpenAi(array $messages): ?array
    {
        try {
            $response = $this->httpClient->request(
                'POST',
                self::OPENAI_API_URL,
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->openAiApiKey,
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'model' => $this->openAiModel,
                        'temperature' => 0.2,
                        'messages' => $messages,
                    ],
                    'timeout' => 30,
                ]
            );
            $payload = $response->toArray();
            $text = $payload['choices'][0]['message']['content'] ?? '';
            return $this->successOrNull($text);
        } catch (\Throwable $e) {
            $this->logger?->debug('Chatbot OpenAI failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function callGroq(array $messages): ?array
    {
        try {
            $response = $this->httpClient->request(
                'POST',
                self::GROQ_API_URL,
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->groqApiKey,
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'model' => self::GROQ_MODEL,
                        'temperature' => 0.2,
                        'messages' => $messages,
                    ],
                    'timeout' => 30,
                ]
            );
            $payload = $response->toArray();
            $text = $payload['choices'][0]['message']['content'] ?? '';
            return $this->successOrNull($text);
        } catch (\Throwable $e) {
            $this->logger?->debug('Chatbot Groq failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function callGrok(array $messages): ?array
    {
        try {
            $response = $this->httpClient->request(
                'POST',
                self::GROK_API_URL,
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->grokApiKey,
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'model' => self::GROK_MODEL,
                        'temperature' => 0.2,
                        'messages' => $messages,
                    ],
                    'timeout' => 30,
                ]
            );
            $payload = $response->toArray();
            $text = $payload['choices'][0]['message']['content'] ?? '';
            return $this->successOrNull($text);
        } catch (\Throwable $e) {
            $this->logger?->debug('Chatbot Grok failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function callGemini(string $systemPrompt, string $query): ?array
    {
        try {
            $url = sprintf(self::GEMINI_API_URL, self::GEMINI_MODEL) . '?key=' . urlencode($this->geminiApiKey);
            $fullPrompt = $systemPrompt . "\n\nUser question: " . $query;

            $response = $this->httpClient->request(
                'POST',
                $url,
                [
                    'headers' => ['Content-Type' => 'application/json'],
                    'json' => [
                        'contents' => [
                            ['role' => 'user', 'parts' => [['text' => $fullPrompt]]],
                        ],
                        'generationConfig' => ['temperature' => 0.2, 'maxOutputTokens' => 1024],
                    ],
                    'timeout' => 30,
                ]
            );
            $payload = $response->toArray();
            $text = $payload['candidates'][0]['content']['parts'][0]['text'] ?? '';
            return $this->successOrNull($text);
        } catch (\Throwable $e) {
            $this->logger?->debug('Chatbot Gemini failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function callOllama(string $baseUrl, array $messages): ?array
    {
        try {
            $url = rtrim($baseUrl, '/') . '/api/chat';
            $ollamaMessages = [];
            foreach ($messages as $m) {
                $ollamaMessages[] = ['role' => $m['role'], 'content' => $m['content']];
            }

            $response = $this->httpClient->request(
                'POST',
                $url,
                [
                    'headers' => ['Content-Type' => 'application/json'],
                    'json' => [
                        'model' => $this->ollamaModel,
                        'messages' => $ollamaMessages,
                        'stream' => false,
                    ],
                    'timeout' => 60,
                ]
            );
            $payload = $response->toArray();
            $text = $payload['message']['content'] ?? '';
            return $this->successOrNull($text);
        } catch (\Throwable $e) {
            $this->logger?->debug('Chatbot Ollama failed', ['error' => $e->getMessage(), 'url' => $baseUrl]);
            return null;
        }
    }

    private function successOrNull(string $text): ?array
    {
        $trimmed = trim($text);
        if ($trimmed === '') {
            return null;
        }
        return ['success' => true, 'message' => $trimmed];
    }

    private function buildContext(User $patient): string
    {
        $fiche = $this->ficheRepository->findLatestByPatient($patient);
        $ordonnances = $this->ordonnanceRepository->findByPatient($patient, 20);

        $parts = [];

        if ($fiche) {
            $ficheData = [
                'fiche' => [
                    'date' => $fiche->getDate()?->format('Y-m-d'),
                    'poids' => $fiche->getPoids(),
                    'taille' => $fiche->getTaille(),
                    'grpSanguin' => $fiche->getGrpSanguin(),
                    'tension' => $fiche->getTension(),
                    'glycemie' => $fiche->getGlycemie(),
                    'allergie' => $fiche->getAllergie(),
                    'maladieChronique' => $fiche->getMaladieChronique(),
                    'libelleMaladie' => $fiche->getLibelleMaladie(),
                    'gravite' => $fiche->getGravite(),
                    'symptomes' => $fiche->getSymptomes(),
                    'recommandation' => $fiche->getRecommandation(),
                ],
            ];
            $parts[] = json_encode($ficheData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }

        if ($ordonnances !== []) {
            $ordData = [];
            foreach ($ordonnances as $ord) {
                $lignes = [];
                foreach ($ord->getLignesOrdonnance() as $ligne) {
                    $med = $ligne->getMedicament();
                    $lignes[] = [
                        'medicament' => $med ? $med->getNomMedicament() : null,
                        'dosage' => $med ? $med->getDosage() : null,
                        'forme' => $med ? $med->getForme() : null,
                        'nbJours' => $ligne->getNbJours(),
                        'frequenceParJour' => $ligne->getFrequenceParJour(),
                        'momentPrise' => $ligne->getMomentPrise(),
                        'periode' => $ligne->getPeriode(),
                        'avantRepas' => $ligne->isAvantRepas(),
                    ];
                }
                $ordData[] = [
                    'date' => $ord->getDateOrdonnance()?->format('Y-m-d'),
                    'posologie' => $ord->getPosologie(),
                    'frequence' => $ord->getFrequence(),
                    'dureeTraitement' => $ord->getDureeTraitement(),
                    'lignes' => $lignes,
                ];
            }
            $parts[] = 'Ordonnances: ' . json_encode($ordData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }

        if ($parts === []) {
            return '';
        }

        return implode("\n\n", $parts);
    }
}
