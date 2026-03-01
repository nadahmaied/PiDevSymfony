<?php

namespace App\Controller;

use App\Entity\MissionVolunteer;
use App\Entity\Volunteer;
use App\Form\MissionType;
use App\Repository\MissionVolunteerRepository;
use App\Service\ApplicationsSummaryAiService;
use App\Service\MissionRecommendationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/admin/missions')]
class AdminMissionController extends AbstractController
{
    // 1. AFFICHER LA LISTE
    #[Route('/', name: 'app_admin_missions_index', methods: ['GET'])]
    public function index(
    MissionVolunteerRepository $missionRepository, 
    PaginatorInterface $paginator, 
    Request $request
): Response
{
    // 1. RÃ©cupÃ©rer le terme de recherche depuis l'URL (ex: ?q=medecin)
    $searchTerm = $request->query->get('q');
    $applicationsFilter = (string) $request->query->get('candidatures', 'all');
    if (!\in_array($applicationsFilter, ['all', 'with', 'without'], true)) {
        $applicationsFilter = 'all';
    }

    // 2. CrÃ©er la requÃªte via notre mÃ©thode du Repository
    $query = $missionRepository->findBySearchQuery($searchTerm, null, $applicationsFilter);

    // 3. Paginer les rÃ©sultats (10 par page)
    $pagination = $paginator->paginate(
        $query,
        $request->query->getInt('page', 1), // NumÃ©ro de page
        4 // Limite par page
    );

    return $this->render('admin_mission/index.html.twig', [
        'pagination' => $pagination, // On passe "pagination" au lieu de "missions"
        'searchTerm' => $searchTerm,  // Pour garder le mot dans la barre de recherche
        'applicationsFilter' => $applicationsFilter,
    ]);
}

    // 2. CRÃ‰ER (NOUVEAU)
    // 2. CRÃ‰ER (NOUVEAU) - VERSION DEBUG
    #[Route('/new', name: 'app_admin_missions_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $mission = new MissionVolunteer();
        
        // --- NOUVEAU : On relie la mission Ã  l'admin connectÃ© ---
        $mission->setUser($this->getUser());
        // --------------------------------------------------------

        $form = $this->createForm(MissionType::class, $mission);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            // GESTION DE L'UPLOAD PHOTO
            $photoFile = $form->get('photo')->getData();

            if ($photoFile) {
                $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$photoFile->guessExtension();

                try {
                    $photoFile->move(
                        $this->getParameter('mission_images_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // GÃ©rer l'erreur si besoin
                }

                // On enregistre le nom du fichier dans l'entitÃ©
                $mission->setPhoto($newFilename);
            }

            $entityManager->persist($mission);
            $entityManager->flush();

            // Petit message de succÃ¨s pour confirmer (optionnel mais sympa)
            $this->addFlash('success', 'Nouvelle mission crÃ©Ã©e avec succÃ¨s !');

            return $this->redirectToRoute('app_admin_missions_index');
        }

        return $this->render('admin_mission/new.html.twig', [
            'mission' => $mission,
            'form' => $form,
        ]);
    }
    

    // 3. MODIFIER
    #[Route('/{id}/edit', name: 'app_admin_missions_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, MissionVolunteer $mission, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(MissionType::class, $mission);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            // --- GESTION DE L'IMAGE (MÃªme logique que pour "new") ---
            $photoFile = $form->get('photo')->getData();

            // On ne traite l'image que si un NOUVEAU fichier a Ã©tÃ© envoyÃ©
            if ($photoFile) {
                $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$photoFile->guessExtension();

                try {
                    $photoFile->move(
                        $this->getParameter('mission_images_directory'),
                        $newFilename
                    );
                    
                    // On met Ã  jour le nom de l'image dans la base de donnÃ©es
                    $mission->setPhoto($newFilename);

                } catch (FileException $e) {
                    // Vous pouvez ajouter un message d'erreur ici si l'upload Ã©choue
                }
            }
            // --------------------------------------------------------

            $entityManager->flush();

            return $this->redirectToRoute('app_admin_missions_index');
        }

        return $this->render('admin_mission/edit.html.twig', [
            'mission' => $mission,
            'form' => $form,
        ]);
    }

    // 4. SUPPRIMER
    #[Route('/{id}', name: 'app_admin_missions_delete', methods: ['POST'])]
    public function delete(Request $request, MissionVolunteer $mission, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$mission->getId(), $request->request->get('_token'))) {
            $entityManager->remove($mission);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_admin_missions_index');
    }

    #[Route('/ai-dashboard', name: 'app_admin_missions_ai_dashboard', methods: ['GET'])]
    public function aiDashboard(
        MissionVolunteerRepository $missionRepository,
        MissionRecommendationService $recommendationService
    ): Response {
        $missions = $missionRepository->findBy([], ['dateDebut' => 'DESC']);
        $insights = $recommendationService->adminInsights($missions);

        $atRiskMissions = [];
        foreach ($missions as $mission) {
            if ($mission->getStatut() !== 'Ouverte') {
                continue;
            }

            $difficulty = $mission->getDifficultyLevel() ?? 3;
            $urgency = $mission->getUrgencyLevel() ?? 3;
            $applications = count($mission->getVolunteers());
            $riskScore = ($difficulty * 0.4) + ($urgency * 0.4) + (max(0, 5 - min(5, $applications)) * 0.2);

            if ($riskScore >= 3.2) {
                $atRiskMissions[] = [
                    'mission' => $mission,
                    'riskScore' => round($riskScore, 1),
                    'applications' => $applications,
                ];
            }
        }

        usort(
            $atRiskMissions,
            static fn (array $a, array $b): int => $b['riskScore'] <=> $a['riskScore']
        );

        return $this->render('admin_mission/ai_dashboard.html.twig', [
            'insights' => $insights,
            'atRiskMissions' => array_slice($atRiskMissions, 0, 8),
        ]);
    }

    #[Route('/{id}/ai-candidatures-summary', name: 'app_admin_missions_ai_candidatures_summary', methods: ['POST'])]
    public function aiApplicationsSummary(
        MissionVolunteer $mission,
        ApplicationsSummaryAiService $applicationsSummaryAiService
    ): JsonResponse {
        try {
            $result = $applicationsSummaryAiService->summarize($mission);

            return $this->json([
                'ok' => true,
                'missionId' => $mission->getId(),
                'missionTitle' => $mission->getTitre(),
                'applicationsCount' => count($mission->getVolunteers()),
                'source' => $result['source'],
                'summary' => $result['summary'],
                'topCandidates' => $result['topCandidates'],
                'risks' => $result['risks'],
                'recommendation' => $result['recommendation'],
                'localRanking' => $result['localRanking'],
            ]);
        } catch (\Throwable) {
            return $this->json([
                'ok' => false,
                'message' => 'Impossible de generer le resume IA des candidatures.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{missionId}/candidatures/{id}/status', name: 'app_admin_missions_application_status', methods: ['POST'])]
    public function updateApplicationStatus(
        int $missionId,
        Volunteer $volunteer,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $mission = $volunteer->getMission();
        if ($mission === null || $mission->getId() !== $missionId) {
            throw $this->createNotFoundException('Candidature introuvable pour cette mission.');
        }

        $token = (string) $request->request->get('_token');
        if (!$this->isCsrfTokenValid('application_status_' . $volunteer->getId(), $token)) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        $status = mb_strtolower(trim((string) $request->request->get('status')));
        $mappedStatus = match ($status) {
            'acceptee', 'accepteee', 'accepte', 'validée', 'validee' => 'Acceptee',
            'refusee', 'refuse', 'rejetee', 'rejeteee' => 'Refusee',
            default => 'En attente',
        };

        $volunteer->setStatut($mappedStatus);
        $entityManager->flush();

        $this->addFlash('success', sprintf('Statut candidature #%d mis a jour: %s', $volunteer->getId(), $mappedStatus));

        return $this->redirectToRoute('app_admin_missions_edit', ['id' => $missionId]);
    }
}
