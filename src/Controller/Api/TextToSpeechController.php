<?php

namespace App\Controller\Api;

use App\Repository\AnnonceRepository;
use App\Service\TextToSpeechService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/tts')]
final class TextToSpeechController extends AbstractController
{
    #[Route('/annonces/{id}/audio', name: 'api_annonce_tts', methods: ['GET'])]
    public function annonceAudio(
        int $id,
        AnnonceRepository $annonceRepository,
        TextToSpeechService $ttsService,
    ): Response {
        $annonce = $annonceRepository->find($id);

        if (!$annonce) {
            return new Response('Annonce non trouvée', Response::HTTP_NOT_FOUND);
        }

        $text = trim(($annonce->getTitreAnnonce() ?? '') . '. ' . ($annonce->getDescription() ?? ''));

        if ($text === '') {
            return new Response('Aucun texte disponible pour cette annonce', Response::HTTP_BAD_REQUEST);
        }

        try {
            $audio = $ttsService->synthesize($text);
        } catch (\Throwable $e) {
            return new Response(
                'Erreur lors de la génération audio de l\'annonce : ' . $e->getMessage(),
                Response::HTTP_BAD_GATEWAY,
            );
        }

        return new Response(
            $audio,
            Response::HTTP_OK,
            [
                'Content-Type' => 'audio/mpeg',
                'Content-Disposition' => 'inline; filename="annonce-' . $id . '.mp3"',
                'Cache-Control' => 'no-store',
            ],
        );
    }
}

