<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\MissionVolunteerRepository;
use App\Service\MissionRecommendationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/missions/recommendations')]
class MissionRecommendationController extends AbstractController
{
    #[Route('', name: 'app_mission_recommendations', methods: ['GET'])]
    public function __invoke(
        MissionVolunteerRepository $missionRepository,
        MissionRecommendationService $recommendationService
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['message' => 'Utilisateur non connecte'], 401);
        }

        $missions = $missionRepository->findBy(['statut' => 'Ouverte']);
        $recommendations = $recommendationService->recommendForUser($user, $missions, 5);

        $payload = array_map(static function (array $item): array {
            return [
                'missionId' => $item['mission']->getId(),
                'title' => $item['mission']->getTitre(),
                'location' => $item['mission']->getLieu(),
                'matchPercent' => $item['matchPercent'],
                'heuristicPercent' => $item['heuristicPercent'] ?? null,
                'mlPercent' => $item['mlPercent'] ?? null,
                'heuristicWeightPercent' => $item['heuristicWeightPercent'] ?? null,
                'mlWeightPercent' => $item['mlWeightPercent'] ?? null,
                'modelTrainRows' => $item['modelTrainRows'] ?? null,
                'breakdown' => $item['breakdown'],
                'reasons' => $item['reasons'],
            ];
        }, $recommendations);

        return $this->json([
            'generatedAt' => (new \DateTime())->format(\DateTimeInterface::ATOM),
            'recommendations' => $payload,
        ]);
    }
}
