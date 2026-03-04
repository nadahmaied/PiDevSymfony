<?php

namespace App\Service;

use App\Entity\Annonce;

final class AiSpamDetectorService
{
    private const OLLAMA_URL = 'http://localhost:11434/api/generate';

    /**
     * Returns true if the annonce looks like spam (e.g. financial incentives, scams).
     */
    public function isSpam(Annonce $annonce): bool
    {
        $title = (string) $annonce->getTitreAnnonce();
        $description = (string) $annonce->getDescription();

        $text = trim($title . "\n\n" . $description);
        if ($text === '') {
            return false;
        }

        // 1) Fast keyword-based heuristic (multi-language) to catch obvious spam
        $lower = mb_strtolower($text);

        // Very strong signals: direct patterns in multiple languages
        $strongPatterns = [
            // English
            'win money', 'win cash', 'become rich', 'get rich', 'win dollars', 'win dollar',
            '100 dollars', '100 dollar', 'prize', 'reward', 'gift card',
            // French
            'gagner de l\'argent', 'gagne de l\'argent', 'gagnez de l\'argent',
            'deviens riche', 'devenir riche',
            '100 euros', '100 euro', 'récompense', 'prix à gagner', 'cadeau offert',
            // Arabic (basic forms)
            'اربح مال', 'اربح مالاً', 'اربح نقود', 'تصبح غني', 'تصبح غنياً', 'جائزة مالية',
        ];

        foreach ($strongPatterns as $pattern) {
            if (str_contains($lower, $pattern)) {
                return true;
            }
        }

        // Weaker but generic patterns: money + winning/earning words in same text
        $moneyWords = [
            'money', 'cash',
            'dollar', 'dollars',
            'euro', 'euros',
            'dinar', 'dinars', 'tnd',
            'argent', 'argen',
            'مال', 'نقود',
        ];
        $winWords = ['win', 'won', 'prize', 'reward', 'rich', 'gagn', 'gagne', 'gagnez', 'ربح', 'اربح'];

        $hasMoney = false;
        foreach ($moneyWords as $w) {
            if (str_contains($lower, $w)) {
                $hasMoney = true;
                break;
            }
        }

        $hasWin = false;
        foreach ($winWords as $w) {
            if (str_contains($lower, $w)) {
                $hasWin = true;
                break;
            }
        }

        if ($hasMoney && $hasWin) {
            return true;
        }

        // Specific pattern: gagn* + number + currency (tnd, dinar, euro, dollar)
        $pattern = '/gagn\\w*[^\\n]*?(\\d+\\s*(tnd|dinars?|dollars?|euros?))/iu';
        if (preg_match($pattern, $text)) {
            return true;
        }

        $prompt = $this->buildPrompt($text);
        $result = $this->callOllama($prompt);

        if ($result === null) {
            return false;
        }

        $answer = strtoupper(trim($result));

        if (str_contains($answer, 'SPAM')) {
            return true;
        }

        return false;
    }

    private function buildPrompt(string $text): string
    {
        return <<<PROMPT
You are moderating donation / medical help announcements in multiple languages
(French, English, Arabic, and others).

Text:
\"\"\"{$text}\"\"\"

Task:
- First, understand the meaning of the text regardless of its language.
- Then classify it as either:
  - SPAM: scams, financial rewards or incentives (e.g. "win 100 dollars",
    "gagner de l'argent", "اربح مالاً"), lotteries, prizes, or anything clearly
    not a serious medical / donation need.
  - OK: a legitimate announcement asking for help, donations, or support,
    without suspicious financial incentives or scam patterns.

Examples of SPAM:
- \"Donate blood and win hundred dollars\"
- \"Donner du sang et gagner 100 euros !\"
- \"تبرع بالدم واحصل على جائزة مالية كبيرة\"

Answer format:
- Output ONE WORD ONLY in UPPERCASE:
  - SPAM
  - OK

No explanations, no other words.
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
                'timeout' => 20,
            ],
        ]);

        $response = @file_get_contents(self::OLLAMA_URL, false, $context);
        if ($response === false) {
            return null;
        }

        $data = json_decode($response, true);
        return $data['response'] ?? null;
    }
}

