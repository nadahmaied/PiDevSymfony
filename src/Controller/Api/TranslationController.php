<?php

namespace App\Controller\Api;

use App\Service\TranslationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/translate')]
final class TranslationController extends AbstractController
{
    #[Route('', name: 'api_translate', methods: ['POST'])]
    public function translate(Request $request, TranslationService $translationService): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $target = trim((string) ($data['to'] ?? $data['target'] ?? ''));
        $source = trim((string) ($data['from'] ?? $data['source'] ?? '')) ?: null;

        if ($target === '') {
            return new JsonResponse(['error' => 'Target language (to) is required'], 400);
        }

        // Single text
        $text = $data['text'] ?? null;
        if ($text !== null) {
            $translated = $translationService->translate((string) $text, $target, $source);
            return new JsonResponse(['translated' => $translated]);
        }

        // Multiple fields (e.g. for annonce: titre, description)
        $texts = $data['texts'] ?? null;
        if (is_array($texts)) {
            $translated = $translationService->translateMany($texts, $target, $source);
            return new JsonResponse(['translated' => $translated]);
        }

        return new JsonResponse(['error' => 'Either "text" or "texts" (object) is required'], 400);
    }

    #[Route('/languages', name: 'api_translate_languages', methods: ['GET'])]
    public function languages(): JsonResponse
    {
        return new JsonResponse(TranslationService::getSupportedLanguages());
    }
}
