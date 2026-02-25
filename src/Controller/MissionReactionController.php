<?php

namespace App\Controller;

use App\Entity\MissionLike;
use App\Entity\MissionRating;
use App\Entity\MissionVolunteer;
use App\Entity\User;
use App\Repository\MissionLikeRepository;
use App\Repository\MissionRatingRepository;
use App\Service\RecommendationLearningService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/reaction/mission')]
class MissionReactionController extends AbstractController
{
    #[Route('/like/{id}', name: 'app_mission_like_action')]
    public function like(
        MissionVolunteer $mission,
        EntityManagerInterface $em,
        MissionLikeRepository $likeRepo,
        RecommendationLearningService $learningService
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['code' => 403, 'message' => 'Non connecte'], 403);
        }

        $existingLike = $likeRepo->findOneBy(['mission' => $mission, 'user' => $user]);

        if ($existingLike) {
            $em->remove($existingLike);
            $learningService->track($user, $mission, 'mission_ignored', 0.6);
            $em->flush();

            return $this->json([
                'code' => 200,
                'message' => 'Like supprime',
                'likesCount' => $likeRepo->count(['mission' => $mission]),
                'isLiked' => false,
            ]);
        }

        $like = new MissionLike();
        $like->setMission($mission);
        $like->setUser($user);

        $em->persist($like);
        $learningService->track($user, $mission, 'mission_liked', 1.0);
        $em->flush();

        return $this->json([
            'code' => 200,
            'message' => 'Like ajoute',
            'likesCount' => $likeRepo->count(['mission' => $mission]),
            'isLiked' => true,
        ]);
    }

    #[Route('/rate/{id}/{note}', name: 'app_mission_rate_action', methods: ['POST'])]
    public function rate(
        MissionVolunteer $mission,
        int $note,
        EntityManagerInterface $em,
        MissionRatingRepository $ratingRepo,
        RecommendationLearningService $learningService
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['code' => 403, 'message' => 'Non connecte'], 403);
        }

        if ($note < 1 || $note > 5) {
            return $this->json(['code' => 400, 'message' => 'Note invalide'], 400);
        }

        $rating = $ratingRepo->findOneBy(['mission' => $mission, 'user' => $user]);

        if (!$rating) {
            $rating = new MissionRating();
            $rating->setMission($mission);
            $rating->setUser($user);
        }

        $rating->setNote($note);
        $em->persist($rating);
        $learningService->track($user, $mission, 'mission_rated', $note / 5, ['note' => $note]);
        $em->flush();

        $allRatings = $ratingRepo->findBy(['mission' => $mission]);
        $total = 0;
        foreach ($allRatings as $r) {
            $total += $r->getNote() ?? 0;
        }
        $average = count($allRatings) > 0 ? round($total / count($allRatings), 1) : 0;

        return $this->json([
            'code' => 200,
            'message' => 'Note enregistree',
            'average' => $average,
            'count' => count($allRatings),
        ]);
    }
}
