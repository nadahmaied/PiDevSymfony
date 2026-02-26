<?php

namespace App\Controller;

use App\Repository\FicheRepository;
use App\Repository\LigneOrdonnanceRepository;
use App\Repository\OrdonnanceRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class StatsController extends AbstractController
{
    #[Route('/stats', name: 'app_admin_stats', methods: ['GET'])]
    public function index(
        Request $request,
        UserRepository $userRepository,
        FicheRepository $ficheRepository,
        LigneOrdonnanceRepository $ligneRepo,
        OrdonnanceRepository $ordonnanceRepository
    ): Response {
        $patientId = $request->query->getInt('patient');
        $patient = $patientId > 0 ? $userRepository->find($patientId) : null;
        $patients = $userRepository->findPatientsWithFiches();

        if ($patient === null) {
            return $this->render('stats/index.html.twig', [
                'patient' => null, 'patients' => $patients,
                'fiche' => null,
                'momentPrise' => [], 'topMedicaments' => [],
                'avantRepas' => ['avant' => 0, 'apres' => 0],
                'ordonnancesPerMonth' => [], 'frequenceParJour' => [],
            ]);
        }

        return $this->render('stats/index.html.twig', [
            'patient' => $patient,
            'patients' => $patients,
            'fiche' => $ficheRepository->findLatestByPatient($patient),
            'momentPrise' => $ligneRepo->countByMomentPrise($patient),
            'topMedicaments' => $ligneRepo->getTopMedicaments($patient, 6),
            'avantRepas' => $ligneRepo->countByAvantRepas($patient),
            'ordonnancesPerMonth' => $ordonnanceRepository->countByMonthForPatient($patient, 6),
            'frequenceParJour' => $ligneRepo->getFrequenceParJourDistribution($patient),
        ]);
    }
}
