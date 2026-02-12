<?php

namespace App\Controller;

use App\Repository\FicheRepository;
use App\Repository\MedicamentRepository;
use App\Repository\OrdonnanceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/patient')]
#[IsGranted('ROLE_USER')]
class PatientController extends AbstractController
{
    #[Route('/dashboard', name: 'patient_dashboard')]
    public function dashboard(FicheRepository $ficheRepo, OrdonnanceRepository $ordRepo): Response
    {
        $user = $this->getUser();
        $latestFiche = $ficheRepo->findOneBy(['idU' => $user], ['date' => 'DESC']);
        $recentOrdonnances = $ordRepo->findBy(['idU' => $user], ['id' => 'DESC'], 3);

        return $this->render('patient/dashboard.html.twig', [
            'latestFiche' => $latestFiche,
            'recentOrdonnances' => $recentOrdonnances,
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

    #[Route('/dossier', name: 'patient_dossier')]
    public function dossier(FicheRepository $ficheRepo, OrdonnanceRepository $ordRepo): Response
    {
        $user = $this->getUser();
        $fiches = $ficheRepo->findBy(['idU' => $user], ['date' => 'DESC']);
        $ordonnances = $ordRepo->findBy(['idU' => $user], ['id' => 'DESC']);

        return $this->render('patient/dossier.html.twig', [
            'fiches' => $fiches,
            'ordonnances' => $ordonnances,
        ]);
    }

    #[Route('/fiche/{id}', name: 'patient_fiche_show')]
    public function showFiche($id, FicheRepository $ficheRepo): Response
    {
        $fiche = $ficheRepo->find($id);
        
        if (!$fiche || $fiche->getIdU() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('patient/show_fiche.html.twig', [
            'fiche' => $fiche,
        ]);
    }

    #[Route('/ordonnance/{id}', name: 'patient_ordonnance_show')]
    public function showOrdonnance($id, OrdonnanceRepository $ordRepo): Response
    {
        $ordonnance = $ordRepo->find($id);
        
        if (!$ordonnance || $ordonnance->getIdU() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('patient/show_ordonnance.html.twig', [
            'ordonnance' => $ordonnance,
        ]);
    }
}
