<?php

namespace App\Service;

final class TextToSpeechService
{
    private string $apiKey;
    private string $region;
    private string $voice;

    public function __construct(
        string $apiKey,
        string $region,
        string $voice = 'fr-FR-DeniseNeural',
    ) {
        $this->apiKey = $apiKey;
        $this->region = $region;
        $this->voice = $voice;
    }

    /**
     * Convertit du texte en audio MP3 en utilisant Azure Cognitive Services.
     *
     * @return string binaire MP3
     */
    public function synthesize(string $text): string
    {
        $ssml = sprintf(
            "<speak version='1.0' xml:lang='fr-FR'><voice xml:lang='fr-FR' xml:gender='Female' name='%s'>%s</voice></speak>",
            htmlspecialchars($this->voice, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars($text, ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8'),
        );

        return $this->callAzureTts($ssml);
    }

    private function callAzureTts(string $ssml): string
    {
        $ttsUrl = sprintf(
            'https://%s.tts.speech.microsoft.com/cognitiveservices/v1',
            $this->region,
        );

        $responseHeaders = [];

        $ch = curl_init($ttsUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Ocp-Apim-Subscription-Key: ' . $this->apiKey,
                'Content-Type: application/ssml+xml; charset=utf-8',
                'X-Microsoft-OutputFormat: audio-16khz-32kbitrate-mono-mp3',
                'User-Agent: HealthTrack-TTS/1.0',
                'Accept: audio/mpeg',
            ],
            CURLOPT_POSTFIELDS => $ssml,
            CURLOPT_HEADERFUNCTION => static function ($ch, $headerLine) use (&$responseHeaders) {
                $len = strlen($headerLine);
                $headerLine = trim($headerLine);
                if ($headerLine === '' || str_starts_with($headerLine, 'HTTP/')) {
                    return $len;
                }
                $parts = explode(':', $headerLine, 2);
                if (count($parts) === 2) {
                    $name = strtolower(trim($parts[0]));
                    $value = trim($parts[1]);
                    $responseHeaders[$name] = $value;
                }
                return $len;
            },
        ]);

        $body = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $contentType = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($body === false) {
            throw new \RuntimeException('Erreur lors de la requete TTS : ' . $error);
        }
        if (!is_string($body)) {
            throw new \RuntimeException('Reponse TTS invalide.');
        }

        if ($status < 200 || $status >= 300) {
            $snippet = trim(mb_substr($body, 0, 1200));
            $reqId = $responseHeaders['x-requestid'] ?? ($responseHeaders['x-ms-requestid'] ?? '');
            throw new \RuntimeException(sprintf(
                "Le service TTS a renvoyé le statut HTTP %d (Content-Type: %s, BodyLen: %d, RequestId: %s). Réponse: %s",
                $status,
                $contentType !== '' ? $contentType : 'n/a',
                strlen($body),
                $reqId !== '' ? $reqId : 'n/a',
                $snippet !== '' ? $snippet : '[vide]',
            ));
        }

        if ($contentType !== '' && !str_contains(strtolower($contentType), 'audio')) {
            $snippet = trim(mb_substr($body, 0, 1200));
            throw new \RuntimeException(sprintf(
                'Réponse inattendue du service TTS (Content-Type: %s). Réponse: %s',
                $contentType,
                $snippet !== '' ? $snippet : '[vide]',
            ));
        }

        return $body;
    }
}
