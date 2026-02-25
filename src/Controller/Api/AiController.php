<?php

namespace App\Controller\Api;

use App\Service\AiDescriptionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/ai')]
final class AiController extends AbstractController
{
    #[Route('/enhance-description', name: 'api_ai_enhance_description', methods: ['POST'])]
    public function enhanceDescription(Request $request, AiDescriptionService $aiService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $text = trim((string) ($data['text'] ?? ''));

        if ($text === '') {
            return new JsonResponse(['error' => 'Text is required'], 400);
        }

        if (strlen($text) > 2000) {
            return new JsonResponse(['error' => 'Text is too long'], 400);
        }

        try {
            $enhanced = $aiService->enhanceDescription($text);
            return new JsonResponse(['description' => $enhanced]);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'error' => 'Could not enhance description. Make sure Ollama is running (ollama run llama3.2) or add GROQ_API_KEY to .env for free cloud AI.',
            ], 502);
        }
    }
}
