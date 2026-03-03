<?php

namespace App\Controller;

use App\Entity\MissionVolunteer;
use App\Entity\User;
use App\Entity\Volunteer;
use App\Form\VolunteerType;
use App\Repository\MissionVolunteerRepository;
use App\Service\MissionRecommendationService;
use App\Service\RecommendationLearningService;
use App\Service\VolunteerAiAssistant;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/missions')]
class MissionController extends AbstractController
{
    #[Route('/', name: 'app_missions_index', methods: ['GET'])]
    public function index(
        MissionVolunteerRepository $missionRepository,
        PaginatorInterface $paginator,
        Request $request,
        MissionRecommendationService $recommendationService
    ): Response {
        $searchTerm = $request->query->get('q');

        $qb = $missionRepository->createQueryBuilder('m')
            ->where('m.statut = :statut')
            ->setParameter('statut', 'Ouverte')
            ->orderBy('m.dateDebut', 'DESC');

        if ($searchTerm) {
            $qb->andWhere('m.titre LIKE :search OR m.description LIKE :search OR m.lieu LIKE :search')
                ->setParameter('search', '%' . $searchTerm . '%');
        }

        $recommendedMissions = [];
        $user = $this->getUser();
        if ($user instanceof User) {
            $candidateMissions = $missionRepository->findBy(['statut' => 'Ouverte']);
            $recommendedMissions = $recommendationService->recommendForUser($user, $candidateMissions, 5);
        }

        $pagination = $paginator->paginate(
            $qb->getQuery(),
            $request->query->getInt('page', 1),
            6
        );

        return $this->render('mission/index.html.twig', [
            'pagination' => $pagination,
            'recommendedMissions' => $recommendedMissions,
        ]);
    }

    #[Route('/{id}/postuler', name: 'app_missions_apply', methods: ['GET', 'POST'])]
    public function apply(
        Request $request,
        MissionVolunteer $mission,
        EntityManagerInterface $entityManager,
        RecommendationLearningService $learningService
    ): Response {
        $volunteer = new Volunteer();
        $user = $this->getUser();

        if (!$user instanceof User) {
            $user = $entityManager->getRepository(User::class)->findOneBy([]);
            if (!$user instanceof User) {
                dd("ERREUR : Il faut creer au moins un utilisateur dans la base de donnees (table 'user') pour tester.");
            }
        }

        $volunteer->setMission($mission);
        $volunteer->setUser($user);
        $volunteer->setStatut('En attente');

        $form = $this->createForm(VolunteerType::class, $volunteer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($volunteer);
            $learningService->track($user, $mission, 'apply_created', 1.0, [
                'disponibilites' => $volunteer->getDisponibilites(),
            ]);
            $entityManager->flush();

            $this->addFlash('success', 'Candidature envoyee !');

            return $this->redirectToRoute('app_missions_index');
        }

        return $this->render('mission/apply.html.twig', [
            'mission' => $mission,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/ai-conseil', name: 'app_mission_ai_advice', methods: ['POST'])]
    public function aiAdvice(MissionVolunteer $mission, VolunteerAiAssistant $aiAssistant): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        try {
            $period = sprintf(
                'Du %s au %s',
                $mission->getDateDebut()?->format('d/m/Y') ?? 'N/A',
                $mission->getDateFin()?->format('d/m/Y') ?? 'N/A'
            );

            $result = $aiAssistant->suggestForMission(
                (string) $mission->getTitre(),
                (string) $mission->getDescription(),
                (string) $mission->getLieu(),
                $period
            );

            return $this->json([
                'ok' => true,
                'advice' => $result['advice'],
                'source' => $result['source'],
            ]);
        } catch (\Throwable) {
            return $this->json([
                'ok' => false,
                'message' => 'Assistance IA indisponible pour le moment.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'app_mission_show', methods: ['GET'])]
    public function show(MissionVolunteer $mission): Response
    {
        return $this->render('mission/show.html.twig', [
            'mission' => $mission,
        ]);
    }
}
