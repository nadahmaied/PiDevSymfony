<?php

namespace App\Controller;

use App\Entity\LigneOrdonnance;
use App\Entity\Ordonnance;
use App\Entity\Rdv;
use App\Entity\User;
use App\Form\OrdonnanceType;
use App\Repository\FicheRepository;
use App\Repository\MedicamentRepository;
use App\Repository\OrdonnanceRepository;
use App\Repository\UserRepository;
use App\Service\MedicalAiService;
use App\Service\PrescriptionMailerService;
use Doctrine\ORM\EntityManagerInterface;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\SvgWriter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/ordonnance')]
class OrdonnanceController extends AbstractController
{
    #[Route('/', name: 'app_ordonnance_index', methods: ['GET'])]
    public function index(Request $request, OrdonnanceRepository $ordonnanceRepository): Response
    {
        $search = $request->query->get('search');
        $sortBy = $request->query->get('sortBy');
        $sortOrder = $request->query->get('sortOrder', 'ASC');
        $search = is_string($search) && $search !== '' ? $search : null;
        $sortBy = is_string($sortBy) && $sortBy !== '' ? $sortBy : null;
        $sortOrder = is_string($sortOrder) && $sortOrder !== '' ? $sortOrder : 'ASC';

        return $this->render('ordonnance/index.html.twig', [
            'ordonnances' => $ordonnanceRepository->findBySearchAndSort($search, $sortBy, $sortOrder),
            'search' => $search,
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder,
        ]);
    }

    #[Route('/generate-from-ai/{ficheId}', name: 'app_ordonnance_generate_from_ai', methods: ['POST'])]
    public function generateFromAi(
        int $ficheId,
        Request $request,
        FicheRepository $ficheRepository,
        MedicalAiService $medicalAiService,
        MedicamentRepository $medicamentRepository,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        PrescriptionMailerService $prescriptionMailer
    ): Response {
        $fiche = $ficheRepository->find($ficheId);
        if (!$fiche) {
            $this->addFlash('danger', 'Fiche introuvable.');
            return $this->redirectToRoute('app_fiche_index');
        }

        if (!$this->isCsrfTokenValid('generate-ai-' . $ficheId, (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Action invalide.');
            return $this->redirectToRoute('app_fiche_show', ['id' => $ficheId]);
        }

        $aiSuggestions = $medicalAiService->generateSuggestions($fiche);
        $items = $aiSuggestions['items'] ?? [];

        if (!is_array($items) || $items === []) {
            $this->addFlash('warning', 'Aucune suggestion exploitable pour creer une ordonnance.');
            return $this->redirectToRoute('app_fiche_show', ['id' => $ficheId]);
        }

        $prescriber = $this->resolvePrescriber($userRepository);
        if ($prescriber === null) {
            $this->addFlash('danger', 'Aucun medecin prescripteur disponible.');
            return $this->redirectToRoute('app_fiche_show', ['id' => $ficheId]);
        }

        $rdv = new Rdv();
        $rdv->setPatient($fiche->getIdU());

        $ordonnance = new Ordonnance();
        $ordonnance->setIdU($prescriber);
        $ordonnance->setIdRdv($rdv);
        $ordonnance->setDateOrdonnance(new \DateTime());
        $ordonnance->setPosologie('Ordonnance generee via IA (Gemini) - validation medicale requise.');
        $ordonnance->setFrequence('Auto');
        $ordonnance->setDureeTraitement(7);

        $duree = 7;
        $frequenceText = [];

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
        }

        $ordonnance->setDureeTraitement($duree);
        $ordonnance->setFrequence(implode(' | ', array_slice($frequenceText, 0, 5)));

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

        return $this->redirectToRoute('app_ordonnance_show', ['id' => $ordonnance->getId()]);
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

    #[Route('/new', name: 'app_ordonnance_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, PrescriptionMailerService $prescriptionMailer): Response
    {
        $ordonnance = new Ordonnance();
        $form = $this->createForm(OrdonnanceType::class, $ordonnance);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($ordonnance);
            $entityManager->flush();

            try {
                if ($prescriptionMailer->sendPrescription($ordonnance)) {
                    $this->addFlash('success', 'Ordonnance enregistrée et envoyée par email au patient.');
                } else {
                    $this->addFlash('success', 'Ordonnance enregistrée avec succès !');
                }
            } catch (\Throwable $e) {
                $this->addFlash('success', 'Ordonnance enregistrée avec succès !');
                $this->addFlash('warning', 'L\'envoi de l\'email au patient a échoué.');
            }

            return $this->redirectToRoute('app_ordonnance_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('ordonnance/new.html.twig', [
            'ordonnance' => $ordonnance,
            'form' => $form,
        ]);
    }

    #[Route('/scan/{token}', name: 'app_ordonnance_scan', methods: ['GET'], requirements: ['token' => '[a-f0-9]{64}'])]
    public function scan(string $token, OrdonnanceRepository $ordonnanceRepository): Response
    {
        $ordonnance = $ordonnanceRepository->findOneByScanToken($token);
        if (!$ordonnance) {
            return $this->render('ordonnance/scan_not_found.html.twig', [], new Response('', 404));
        }

        return $this->render('patient/show_ordonnance.html.twig', [
            'ordonnance' => $ordonnance,
        ]);
    }

    #[Route('/{id}', name: 'app_ordonnance_show', methods: ['GET'])]
    public function show(
        Ordonnance $ordonnance,
        EntityManagerInterface $entityManager,
        string $siteBaseUrl
    ): Response {
        if ($ordonnance->getScanToken() === null) {
            $ordonnance->setScanToken(bin2hex(random_bytes(32)));
            $entityManager->flush();
        }

        $qrDataUri = $this->generateQrCodeDataUri($ordonnance, $siteBaseUrl);

        return $this->render('ordonnance/show.html.twig', [
            'ordonnance' => $ordonnance,
            'qrDataUri' => $qrDataUri,
        ]);
    }

    private function generateQrCodeDataUri(Ordonnance $ordonnance, string $siteBaseUrl): string
    {
        $scanUrl = rtrim($siteBaseUrl, '/') . $this->generateUrl(
            'app_ordonnance_scan',
            ['token' => $ordonnance->getScanToken()],
            UrlGeneratorInterface::ABSOLUTE_PATH
        );

        $builder = Builder::create()
            ->writer(new SvgWriter())
            ->data($scanUrl)
            ->size(280)
            ->margin(10);
        $result = $builder->build();

        return $result->getDataUri();
    }

    #[Route('/{id}/edit', name: 'app_ordonnance_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Ordonnance $ordonnance, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(OrdonnanceType::class, $ordonnance);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Ordonnance mise à jour avec succès !');

            return $this->redirectToRoute('app_ordonnance_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('ordonnance/edit.html.twig', [
            'ordonnance' => $ordonnance,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_ordonnance_delete', methods: ['POST'])]
    public function delete(Request $request, Ordonnance $ordonnance, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$ordonnance->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($ordonnance);
            $entityManager->flush();
            $this->addFlash('success', 'Ordonnance supprimée avec succès !');
        }

        return $this->redirectToRoute('app_ordonnance_index', [], Response::HTTP_SEE_OTHER);
    }
}
