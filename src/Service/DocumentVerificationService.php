<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class DocumentVerificationService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $apiKey
    ) {
    }

    public function extractTextFromImage(string $imagePath): ?string
    {
        try {
            error_log('Attempting vision analysis on: ' . $imagePath);
            error_log('Gemini API Key present: ' . (!empty($this->apiKey) ? 'YES' : 'NO'));
            
            // Check if file exists
            if (!file_exists($imagePath)) {
                error_log('File does not exist: ' . $imagePath);
                return null;
            }
            
            // Read image and convert to base64
            $imageData = file_get_contents($imagePath);
            if ($imageData === false) {
                error_log('Failed to read image file');
                return null;
            }
            
            $base64Image = base64_encode($imageData);
            $mimeType = mime_content_type($imagePath);
            error_log('Image mime type: ' . $mimeType);
            error_log('Image size: ' . strlen($imageData) . ' bytes');
            
            // Using Google Gemini Vision API
            $response = $this->httpClient->request('POST', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent?key=' . $this->apiKey, [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'contents' => [
                        [
                            'parts' => [
                                [
                                    'text' => 'Extract ALL text from this image. Return only the extracted text, nothing else. Include all names, titles, and words you can see in the document.'
                                ],
                                [
                                    'inline_data' => [
                                        'mime_type' => $mimeType,
                                        'data' => $base64Image
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.1,
                        'maxOutputTokens' => 1024,
                    ]
                ],
                'timeout' => 30,
            ]);

            $statusCode = $response->getStatusCode();
            error_log('Gemini API Status Code: ' . $statusCode);
            
            $data = $response->toArray(false);
            error_log('Gemini Response: ' . json_encode($data));

            if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                $text = $data['candidates'][0]['content']['parts'][0]['text'];
                error_log('Extracted text: ' . $text);
                return $text;
            }

            if (isset($data['error'])) {
                error_log('Gemini API Error: ' . json_encode($data['error']));
            }

            error_log('No text found in Gemini response');
            return null;
        } catch (\Exception $e) {
            error_log('Vision API Exception: ' . $e->getMessage());
            error_log('Exception trace: ' . $e->getTraceAsString());
            return null;
        }
    }

    public function verifyDiploma(string $diplomaPath, string $userFirstName, string $userLastName): array
    {
        error_log("Verifying diploma for: $userFirstName $userLastName");
        
        $diplomaText = $this->extractTextFromImage($diplomaPath);

        if (!$diplomaText || trim($diplomaText) === '') {
            error_log('Failed to extract text from diploma');
            return [
                'verified' => false,
                'message' => 'Impossible de lire le diplôme. Veuillez soumettre une image plus claire.',
            ];
        }

        // Normalize texts for comparison
        $diplomaText = $this->normalizeText($diplomaText);
        $fullName = $this->normalizeText($userFirstName . ' ' . $userLastName);
        $reverseName = $this->normalizeText($userLastName . ' ' . $userFirstName);

        error_log("Normalized diploma text: $diplomaText");
        error_log("Looking for name: $fullName or $reverseName");

        // Check if name appears in diploma
        $nameInDiploma = $this->containsName($diplomaText, $fullName, $reverseName);
        error_log("Name found in diploma: " . ($nameInDiploma ? 'YES' : 'NO'));

        // Check for medical keywords in diploma
        $medicalKeywords = ['medecin', 'docteur', 'diplome', 'faculte', 'medicine', 'medical'];
        $hasMedicalKeywords = false;
        foreach ($medicalKeywords as $keyword) {
            if (str_contains($diplomaText, $keyword)) {
                $hasMedicalKeywords = true;
                error_log("Found medical keyword: $keyword");
                break;
            }
        }
        error_log("Has medical keywords: " . ($hasMedicalKeywords ? 'YES' : 'NO'));

        if ($nameInDiploma && $hasMedicalKeywords) {
            return [
                'verified' => true,
                'message' => 'Diplôme vérifié avec succès.',
            ];
        }

        $reasons = [];
        if (!$nameInDiploma) {
            $reasons[] = 'Le nom sur le diplôme ne correspond pas';
        }
        if (!$hasMedicalKeywords) {
            $reasons[] = 'Le diplôme ne semble pas être un diplôme médical';
        }

        return [
            'verified' => false,
            'message' => 'Vérification échouée: ' . implode(', ', $reasons),
        ];
    }

    public function verifyDocuments(string $diplomaPath, string $idCardPath, string $userFirstName, string $userLastName): array
    {
        $diplomaText = $this->extractTextFromImage($diplomaPath);
        $idCardText = $this->extractTextFromImage($idCardPath);

        if (!$diplomaText || !$idCardText) {
            return [
                'verified' => false,
                'message' => 'Impossible de lire les documents. Veuillez soumettre des images plus claires.',
            ];
        }

        // Normalize texts for comparison
        $diplomaText = $this->normalizeText($diplomaText);
        $idCardText = $this->normalizeText($idCardText);
        $fullName = $this->normalizeText($userFirstName . ' ' . $userLastName);
        $reverseName = $this->normalizeText($userLastName . ' ' . $userFirstName);

        // Check if name appears in both documents
        $nameInDiploma = $this->containsName($diplomaText, $fullName, $reverseName);
        $nameInIdCard = $this->containsName($idCardText, $fullName, $reverseName);

        // Check for medical keywords in diploma
        $medicalKeywords = ['medecin', 'docteur', 'diplome', 'faculte', 'medicine', 'medical'];
        $hasMedicalKeywords = false;
        foreach ($medicalKeywords as $keyword) {
            if (str_contains($diplomaText, $keyword)) {
                $hasMedicalKeywords = true;
                break;
            }
        }

        if ($nameInDiploma && $nameInIdCard && $hasMedicalKeywords) {
            return [
                'verified' => true,
                'message' => 'Documents vérifiés avec succès.',
            ];
        }

        $reasons = [];
        if (!$nameInDiploma) {
            $reasons[] = 'Le nom sur le diplôme ne correspond pas';
        }
        if (!$nameInIdCard) {
            $reasons[] = 'Le nom sur la carte d\'identité ne correspond pas';
        }
        if (!$hasMedicalKeywords) {
            $reasons[] = 'Le diplôme ne semble pas être un diplôme médical';
        }

        return [
            'verified' => false,
            'message' => 'Vérification échouée: ' . implode(', ', $reasons),
        ];
    }

    private function normalizeText(string $text): string
    {
        // Remove accents and convert to lowercase
        $text = mb_strtolower($text, 'UTF-8');
        $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        // Remove special characters and extra spaces
        $text = preg_replace('/[^a-z0-9\s]/', '', $text);
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }

    private function containsName(string $text, string $fullName, string $reverseName): bool
    {
        // Check if full name or reverse name appears in text
        if (str_contains($text, $fullName) || str_contains($text, $reverseName)) {
            return true;
        }

        // Check if first and last name appear separately
        $parts = explode(' ', $fullName);
        if (count($parts) >= 2) {
            $firstName = $parts[0];
            $lastName = $parts[count($parts) - 1];
            
            if (str_contains($text, $firstName) && str_contains($text, $lastName)) {
                return true;
            }
        }

        return false;
    }
}
