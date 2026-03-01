<?php

namespace App\Controller;

use App\Repository\FicheRepository;
use App\Repository\LigneOrdonnanceRepository;
use App\Repository\MedicamentRepository;
use App\Repository\MissionVolunteerRepository;
use App\Repository\OrdonnanceRepository;
use App\Repository\QuestionRepository;
use App\Repository\AnnonceRepository;
use App\Repository\DonationRepository;
use App\Repository\RdvRepository;
use App\Repository\VolunteerRepository;
use App\Repository\UserRepository;
use App\Service\MedicalAiService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/patient')]
#[IsGranted('ROLE_USER')]
class PatientController extends AbstractController
{
    #[Route('/dashboard', name: 'patient_dashboard')]
    public function dashboard(
        FicheRepository $ficheRepo,
        OrdonnanceRepository $ordRepo,
        RdvRepository $rdvRepository,
        MissionVolunteerRepository $missionRepository,
        VolunteerRepository $volunteerRepository,
        QuestionRepository $questionRepository,
        AnnonceRepository $annonceRepository,
        DonationRepository $donationRepository
    ): Response
    {
        $user = $this->getUser();
        $latestFiche = $ficheRepo->findOneBy(['idU' => $user]);
        $recentOrdonnances = $ordRepo->findByPatient($user, 3);
        $now = new \DateTimeImmutable();

        $nextRdv = $rdvRepository->createQueryBuilder('r')
            ->andWhere('r.patient = :patient')
            ->andWhere('(r.date > :today OR (r.date = :today AND r.hdebut >= :nowTime))')
            ->setParameter('patient', $user)
            ->setParameter('today', $now->format('Y-m-d'))
            ->setParameter('nowTime', $now->format('H:i:s'))
            ->orderBy('r.date', 'ASC')
            ->addOrderBy('r.hdebut', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        $today = (new \DateTimeImmutable('today'))->format('Y-m-d');
        $ongoingVolunteerMissions = $missionRepository->createQueryBuilder('m')
            ->join('m.volunteers', 'v')
            ->andWhere('v.user = :user')
            ->andWhere('v.statut IN (:acceptedStatuses)')
            ->andWhere('m.dateDebut <= :today')
            ->andWhere('m.dateFin >= :today')
            ->setParameter('user', $user)
            ->setParameter('acceptedStatuses', ['Acceptee', 'Acceptée'])
            ->setParameter('today', $today)
            ->orderBy('m.dateDebut', 'ASC')
            ->setMaxResults(4)
            ->getQuery()
            ->getResult();

        $stats = [
            'rdvCount' => $rdvRepository->count(['patient' => $user]),
            'missionsOpen' => $missionRepository->count(['statut' => 'Ouverte']),
            'myCandidatures' => $volunteerRepository->count(['user' => $user]),
            'forumTopics' => $questionRepository->count([]),
            'annonces' => $annonceRepository->count([]),
            'donations' => $donationRepository->count([]),
        ];

        return $this->render('patient/dashboard.html.twig', [
            'latestFiche' => $latestFiche,
            'recentOrdonnances' => $recentOrdonnances,
            'nextRdv' => $nextRdv,
            'ongoingVolunteerMissions' => $ongoingVolunteerMissions,
            'stats' => $stats,
        ]);
    }

    #[Route('/medicaments', name: 'patient_medicaments')]
    public function medicaments(Request $request, MedicamentRepository $medicamentRepository): Response
    {
        $search = $request->query->get('search');
        $medicaments = $medicamentRepository->findBySearchAndSort($search, 'nomMedicament', 'ASC');

        return $this->render('patient/medicaments.html.twig', [
            'medicaments' => $medicaments,
            'search' => $search,
        ]);
    }

    #[Route('/stats', name: 'patient_stats')]
    public function stats(
        FicheRepository $ficheRepository,
        LigneOrdonnanceRepository $ligneRepo,
        OrdonnanceRepository $ordonnanceRepository,
        UserRepository $userRepository
    ): Response {
        $patient = $this->getUser();
        return $this->render('stats/index.html.twig', [
            'patient' => $patient,
            'patients' => [$patient],
            'fiche' => $ficheRepository->findLatestByPatient($patient),
            'momentPrise' => $ligneRepo->countByMomentPrise($patient),
            'topMedicaments' => $ligneRepo->getTopMedicaments($patient, 6),
            'avantRepas' => $ligneRepo->countByAvantRepas($patient),
            'ordonnancesPerMonth' => $ordonnanceRepository->countByMonthForPatient($patient, 6),
            'frequenceParJour' => $ligneRepo->getFrequenceParJourDistribution($patient),
        ]);
    }

    #[Route('/dossier', name: 'patient_dossier')]
    public function dossier(FicheRepository $ficheRepo, OrdonnanceRepository $ordRepo): Response
    {
        $user = $this->getUser();
        $fiche = $ficheRepo->findOneBy(['idU' => $user]);
        $patientOrdonnances = $ordRepo->findByPatient($user);
        $aiOrdonnances = [];
        $docOrdonnances = [];
        foreach ($patientOrdonnances as $ord) {
            if (stripos((string) $ord->getPosologie(), 'IA') !== false || stripos((string) $ord->getPosologie(), 'auto') !== false) {
                $aiOrdonnances[] = $ord;
            } else {
                $docOrdonnances[] = $ord;
            }
        }

        return $this->render('patient/dossier.html.twig', [
            'fiches' => $fiche ? [$fiche] : [],
            'fiche' => $fiche,
            'ordonnances' => $patientOrdonnances,
            'aiOrdonnances' => $aiOrdonnances,
            'docOrdonnances' => $docOrdonnances,
        ]);
    }

    #[Route('/fiche/{id}', name: 'patient_fiche_show')]
    public function showFiche($id, FicheRepository $ficheRepo, OrdonnanceRepository $ordonnanceRepository, MedicalAiService $medicalAiService): Response
    {
        $fiche = $ficheRepo->find($id);

        if (!$fiche || $fiche->getIdU() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $patientOrdonnances = $ordonnanceRepository->findByPatient($fiche->getIdU());
        $aiOrdonnances = [];
        $docOrdonnances = [];
        foreach ($patientOrdonnances as $ord) {
            if (stripos((string) $ord->getPosologie(), 'IA') !== false || stripos((string) $ord->getPosologie(), 'auto') !== false) {
                $aiOrdonnances[] = $ord;
            } else {
                $docOrdonnances[] = $ord;
            }
        }

        $aiSuggestions = $medicalAiService->generateSuggestions($fiche);

        return $this->render('patient/show_fiche.html.twig', [
            'fiche' => $fiche,
            'aiSuggestions' => $aiSuggestions,
            'aiOrdonnances' => $aiOrdonnances,
            'docOrdonnances' => $docOrdonnances,
        ]);
    }

    #[Route('/ordonnance/{id}', name: 'patient_ordonnance_show')]
    public function showOrdonnance($id, OrdonnanceRepository $ordRepo, EntityManagerInterface $em, string $siteBaseUrl): Response
    {
        $ordonnance = $ordRepo->find($id);

        if (!$ordonnance || $ordonnance->getIdRdv()?->getPatient() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($ordonnance->getScanToken() === null) {
            $ordonnance->setScanToken(bin2hex(random_bytes(32)));
            $em->flush();
        }

        $qrDataUri = $this->generateOrdonnanceQrDataUri($ordonnance, $siteBaseUrl);

        return $this->render('patient/show_ordonnance.html.twig', [
            'ordonnance' => $ordonnance,
            'qrDataUri' => $qrDataUri,
        ]);
    }

    private function generateOrdonnanceQrDataUri($ordonnance, string $siteBaseUrl): string
    {
        $scanUrl = rtrim($siteBaseUrl, '/') . $this->generateUrl(
            'app_ordonnance_scan',
            ['token' => $ordonnance->getScanToken()],
            \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_PATH
        );

        $builder = new \Endroid\QrCode\Builder\Builder(
            writer: new \Endroid\QrCode\Writer\SvgWriter(),
            data: $scanUrl,
            size: 200,
            margin: 8
        );
        $result = $builder->build();

        return $result->getDataUri();
    }
}
