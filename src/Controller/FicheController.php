<?php

namespace App\Controller;

use App\Entity\Fiche;
use App\Entity\LigneOrdonnance;
use App\Entity\Ordonnance;
use App\Entity\Rdv;
use App\Entity\User;
use App\Form\FicheType;
use App\Repository\FicheRepository;
use App\Repository\MedicamentRepository;
use App\Repository\OrdonnanceRepository;
use App\Repository\UserRepository;
use App\Service\MedicalAiService;
use App\Service\PrescriptionMailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/fiche')]
class FicheController extends AbstractController
{
    #[Route('/', name: 'app_fiche_index', methods: ['GET'])]
    public function index(Request $request, FicheRepository $ficheRepository): Response
    {
        $search = $request->query->get('search');
        $sortBy = $request->query->get('sortBy');
        $sortOrder = $request->query->get('sortOrder', 'ASC');
        $search = is_string($search) && $search !== '' ? $search : null;
        $sortBy = is_string($sortBy) && $sortBy !== '' ? $sortBy : null;
        $sortOrder = is_string($sortOrder) && $sortOrder !== '' ? $sortOrder : 'ASC';

        return $this->render('fiche/index.html.twig', [
            'fiches' => $ficheRepository->findBySearchAndSort($search, $sortBy, $sortOrder),
            'search' => $search,
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder,
        ]);
    }

    #[Route('/new', name: 'app_fiche_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $fiche = new Fiche();
        $form = $this->createForm(FicheType::class, $fiche);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($fiche);
            $entityManager->flush();
            $this->addFlash('success', 'Fiche médicale créée avec succès !');

            return $this->redirectToRoute('app_fiche_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('fiche/new.html.twig', [
            'fiche' => $fiche,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_fiche_show', methods: ['GET'])]
    public function show(Fiche $fiche, OrdonnanceRepository $ordonnanceRepository): Response
    {
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

        return $this->render('fiche/show.html.twig', [
            'fiche' => $fiche,
            'aiOrdonnances' => $aiOrdonnances,
            'docOrdonnances' => $docOrdonnances,
        ]);
    }

    #[Route('/{id}/fetch-ai-suggestions', name: 'app_fiche_fetch_ai_suggestions', methods: ['GET'])]
    public function fetchAiSuggestions(Fiche $fiche, MedicalAiService $medicalAiService): JsonResponse
    {
        $data = $medicalAiService->generateSuggestions($fiche);

        return $this->json($data);
    }

    #[Route('/{id}/auto-fill-prescription', name: 'app_fiche_auto_fill_prescription', methods: ['POST'])]
    public function autoFillPrescription(
        Request $request,
        Fiche $fiche,
        MedicalAiService $medicalAiService,
        MedicamentRepository $medicamentRepository,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        PrescriptionMailerService $prescriptionMailer
    ): Response {
        $isPatientView = $this->getUser() === $fiche->getIdU();
        $ficheShowRoute = $isPatientView ? 'patient_fiche_show' : 'app_fiche_show';

        if (!$this->isCsrfTokenValid('autofill' . $fiche->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Action invalide.');
            return $this->redirectToRoute($ficheShowRoute, ['id' => $fiche->getId()]);
        }

        $aiSuggestions = $medicalAiService->generateSuggestionsForOrdonnance($fiche);
        $items = $aiSuggestions['items'] ?? [];

        if (!is_array($items) || $items === []) {
            $this->addFlash('warning', 'Aucune suggestion exploitable pour creer une ordonnance.');
            return $this->redirectToRoute($ficheShowRoute, ['id' => $fiche->getId()]);
        }

        $prescriber = $this->resolvePrescriber($userRepository);
        if ($prescriber === null) {
            $this->addFlash('danger', 'Aucun medecin prescripteur disponible.');
            return $this->redirectToRoute($ficheShowRoute, ['id' => $fiche->getId()]);
        }

        $rdv = new Rdv();
        $rdv->setPatient($fiche->getIdU());

        $ordonnance = new Ordonnance();
        $ordonnance->setIdU($prescriber);
        $ordonnance->setIdRdv($rdv);
        $ordonnance->setDateOrdonnance(new \DateTime());
        $ordonnance->setPosologie('Ordonnance auto-remplie via IA - validation medicale requise.');

        $duree = 7;
        $frequenceText = [];
        $validLines = 0;

        foreach ($items as $item) {
            if (!is_array($item) || !isset($item['medicamentId'])) {
                continue;
            }

            $medicament = $medicamentRepository->find((int) $item['medicamentId']);
            if ($medicament === null) {
                continue;
            }

            $nbJours = max(1, (int) ($item['nbJours'] ?? 7));
            $freq = max(1, (int) ($item['frequenceParJour'] ?? 1));
            $moment = (string) ($item['momentPrise'] ?? 'Matin');
            $avantRepas = (bool) ($item['avantRepas'] ?? false);
            $periode = (string) ($item['periode'] ?? 'Quotidien');

            $ligne = new LigneOrdonnance();
            $ligne->setMedicament($medicament);
            $ligne->setNbJours($nbJours);
            $ligne->setFrequenceParJour($freq);
            $ligne->setMomentPrise($moment);
            $ligne->setAvantRepas($avantRepas);
            $ligne->setPeriode($periode);
            $ordonnance->addLignesOrdonnance($ligne);

            $duree = max($duree, $nbJours);
            $frequenceText[] = sprintf('%s x%d/j', $medicament->getNomMedicament(), $freq);
            $validLines++;
        }

        if ($validLines === 0) {
            $this->addFlash('warning', 'Aucun medicament valide trouve dans les suggestions IA.');
            return $this->redirectToRoute($ficheShowRoute, ['id' => $fiche->getId()]);
        }

        $ordonnance->setDureeTraitement($duree);
        $ordonnance->setFrequence(implode(' | ', array_slice($frequenceText, 0, 3)));

        $entityManager->persist($rdv);
        $entityManager->persist($ordonnance);
        $entityManager->flush();

        try {
            if ($prescriptionMailer->sendPrescription($ordonnance)) {
                $this->addFlash('success', 'Ordonnance créée et envoyée par email au patient.');
            } else {
                $this->addFlash('success', 'Ordonnance créée depuis les suggestions IA. Validation médecin requise.');
            }
        } catch (\Throwable $e) {
            $this->addFlash('success', 'Ordonnance créée depuis les suggestions IA. Validation médecin requise.');
            $this->addFlash('warning', 'L\'envoi de l\'email au patient a échoué.');
        }

        $ordonnanceShowRoute = $isPatientView ? 'patient_ordonnance_show' : 'app_ordonnance_show';
        return $this->redirectToRoute($ordonnanceShowRoute, ['id' => $ordonnance->getId()]);
    }

    #[Route('/{id}/edit', name: 'app_fiche_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Fiche $fiche, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(FicheType::class, $fiche);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Fiche médicale mise à jour avec succès !');

            return $this->redirectToRoute('app_fiche_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('fiche/edit.html.twig', [
            'fiche' => $fiche,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_fiche_delete', methods: ['POST'])]
    public function delete(Request $request, Fiche $fiche, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$fiche->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($fiche);
            $entityManager->flush();
            $this->addFlash('success', 'Fiche médicale supprimée avec succès !');
        }

        return $this->redirectToRoute('app_fiche_index', [], Response::HTTP_SEE_OTHER);
    }

    private function resolvePrescriber(UserRepository $userRepository): ?User
    {
        $currentUser = $this->getUser();
        if ($currentUser instanceof User && in_array('ROLE_MEDECIN', $currentUser->getRoles(), true)) {
            return $currentUser;
        }

        $doctor = $userRepository->findOneBy(['role' => 'ROLE_MEDECIN']);
        if ($doctor instanceof User) {
            return $doctor;
        }

        return $userRepository->findOneBy([]);
    }
}
