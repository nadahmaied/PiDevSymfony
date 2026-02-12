<?php

namespace App\Controller;

use App\Entity\MissionLike;
use App\Entity\MissionRating;
use App\Entity\MissionVolunteer;
use App\Repository\MissionLikeRepository;
use App\Repository\MissionRatingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request; // Import correct
use Symfony\Component\Routing\Annotation\Route;

#[Route('/reaction/mission')]
class MissionReactionController extends AbstractController
{
    // --- GESTION DU LIKE ---
    #[Route('/like/{id}', name: 'app_mission_like_action')]
    public function like(MissionVolunteer $mission, EntityManagerInterface $em, MissionLikeRepository $likeRepo): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) return $this->json(['code' => 403, 'message' => 'Non connecté'], 403);

        // Est-ce que l'utilisateur a déjà liké cette mission ?
        $existingLike = $likeRepo->findOneBy(['mission' => $mission, 'user' => $user]);

        if ($existingLike) {
            // Si oui, on ENLÈVE le like (Dislike)
            $em->remove($existingLike);
            $em->flush();
            
            return $this->json([
                'code' => 200, 
                'message' => 'Like supprimé', 
                'likesCount' => $likeRepo->count(['mission' => $mission]),
                'isLiked' => false
            ]);
        } 
        
        // Sinon, on AJOUTE le like
        $like = new MissionLike();
        $like->setMission($mission);
        $like->setUser($user);
        
        $em->persist($like);
        $em->flush();

        return $this->json([
            'code' => 200, 
            'message' => 'Like ajouté', 
            'likesCount' => $likeRepo->count(['mission' => $mission]),
            'isLiked' => true
        ]);
    }

    // --- GESTION DES ÉTOILES (RATING) ---
    #[Route('/rate/{id}/{note}', name: 'app_mission_rate_action', methods: ['POST'])] // Ajout de methods POST pour sécurité
    public function rate(MissionVolunteer $mission, int $note, EntityManagerInterface $em, MissionRatingRepository $ratingRepo): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) return $this->json(['code' => 403, 'message' => 'Non connecté'], 403);

        if ($note < 1 || $note > 5) return $this->json(['code' => 400, 'message' => 'Note invalide'], 400);

        // On cherche si l'utilisateur a déjà noté
        $rating = $ratingRepo->findOneBy(['mission' => $mission, 'user' => $user]);

        if (!$rating) {
            $rating = new MissionRating();
            $rating->setMission($mission);
            $rating->setUser($user);
        }

        $rating->setNote($note); // On met à jour ou on crée la note
        $em->persist($rating);
        $em->flush();

        // Calcul de la nouvelle moyenne
        $allRatings = $ratingRepo->findBy(['mission' => $mission]);
        $total = 0;
        foreach ($allRatings as $r) {
            $total += $r->getNote();
        }
        $average = count($allRatings) > 0 ? round($total / count($allRatings), 1) : 0;

        return $this->json([
            'code' => 200,
            'message' => 'Note enregistrée',
            'average' => $average,
            'count' => count($allRatings)
        ]);
    }
}