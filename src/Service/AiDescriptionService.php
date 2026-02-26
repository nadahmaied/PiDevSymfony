<?php

namespace App\Service;

class AiDescriptionService
{
    private const OLLAMA_URL = 'http://localhost:11434/api/generate';
    private const GROQ_URL = 'https://api.groq.com/openai/v1/chat/completions';

    public function __construct(
        #[\Symfony\Component\DependencyInjection\Attribute\Autowire(env: 'GROQ_API_KEY')]
        private ?string $groqApiKey = null,
    ) {
    }

    /**
     * Takes a short phrase (e.g. "i need blood for a kid") and returns an enhanced paragraph.
     */
    public function enhanceDescription(string $input): string
    {
        $prompt = $this->buildPrompt($input);

        // Try Ollama first (100% free, runs locally - no API key needed)
        $result = $this->callOllama($prompt);
        if ($result !== null) {
            return $this->cleanResponse($result);
        }

        // Fallback to Groq if API key is configured (free tier: console.groq.com)
        if ($this->groqApiKey && trim($this->groqApiKey) !== '') {
            $result = $this->callGroq($prompt);
            if ($result !== null) {
                return $this->cleanResponse($result);
            }
        }

        return $input; // Return original if both fail
    }

    private function buildPrompt(string $input): string
    {
        return <<<PROMPT
You are helping someone write a clear, professional description for a donation/medical need announcement (e.g. blood, medicine, medical supplies).

The user wrote: "{$input}"

Rewrite this into a single clear, compassionate paragraph (3-5 sentences) that:
- First, DETECT the language of the user's text.
- Is written in EXACTLY THE SAME LANGUAGE as the user's input.
  - If they wrote in French, respond in French.
  - If they wrote in English, respond in English.
  - If they wrote in Arabic, respond in Arabic.
  - Never translate the text into French unless the user text is in French.
- Describes the need clearly and professionally
- Is suitable for a public announcement
- Keeps the same meaning but improves clarity and impact
- Uses a helpful, respectful tone

IMPORTANT:
- Your entire response must be in the same language as the user's text.
- Do NOT translate to another language.
- Do NOT add any explanation, language name, or notes.
Output ONLY the improved paragraph, nothing else. No quotes, no explanations.
PROMPT;
    }

    private function callOllama(string $prompt): ?string
    {
        $payload = json_encode([
            'model' => 'llama3.2',
            'prompt' => $prompt,
            'stream' => false,
        ]);

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n",
                'content' => $payload,
                'timeout' => 30,
            ],
        ]);

        $response = @file_get_contents(self::OLLAMA_URL, false, $context);
        if ($response === false) {
            return null;
        }

        $data = json_decode($response, true);
        return $data['response'] ?? null;
    }

    private function callGroq(string $prompt): ?string
    {
        $payload = json_encode([
            'model' => 'llama-3.1-8b-instant',
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
            'max_tokens' => 300,
        ]);

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\nAuthorization: Bearer {$this->groqApiKey}\r\n",
                'content' => $payload,
                'timeout' => 30,
            ],
        ]);

        $response = @file_get_contents(self::GROQ_URL, false, $context);
        if ($response === false) {
            return null;
        }

        $data = json_decode($response, true);
        return $data['choices'][0]['message']['content'] ?? null;
    }

    private function cleanResponse(string $text): string
    {
        return trim(preg_replace('/^["\']|["\']$/u', '', $text));
    }
}
