<?php

namespace App\Service;

use Stichoza\GoogleTranslate\GoogleTranslate;

final class TranslationService
{
    private const MAX_TEXT_LENGTH = 5000;

    /**
     * Translate text to a target language.
     * Source language is auto-detected when null.
     */
    public function translate(string $text, string $targetLanguage, ?string $sourceLanguage = null): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        if (mb_strlen($text) > self::MAX_TEXT_LENGTH) {
            $text = mb_substr($text, 0, self::MAX_TEXT_LENGTH);
        }

        try {
            $tr = new GoogleTranslate($targetLanguage, $sourceLanguage ?? null);
            return $tr->translate($text) ?: $text;
        } catch (\Throwable $e) {
            return $text;
        }
    }

    /**
     * Translate multiple text fields (e.g. titre + description) in one go.
     * Returns array with same keys, translated values.
     *
     * @param array<string, string> $texts
     * @return array<string, string>
     */
    public function translateMany(array $texts, string $targetLanguage, ?string $sourceLanguage = null): array
    {
        $result = [];
        foreach ($texts as $key => $value) {
            $result[$key] = is_string($value) ? $this->translate($value, $targetLanguage, $sourceLanguage) : (string) $value;
        }
        return $result;
    }

    /**
     * Supported target language codes for the UI.
     * @return array<string, string> code => label
     */
    public static function getSupportedLanguages(): array
    {
        return [
            'fr' => 'Français',
            'en' => 'English',
            'ar' => 'العربية',
            'es' => 'Español',
            'de' => 'Deutsch',
        ];
    }
}
