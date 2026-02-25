<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Rdv;
use App\Form\RdvType;
use App\Repository\RdvRepository;
use App\Repository\MedecinRepository;
use App\Repository\UserRepository;
use Doctrine\Persistence\ManagerRegistry;
use Dompdf\Dompdf;
use Dompdf\Options;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use App\Service\SmsService;

final class RdvController extends AbstractController
{
    // ============================================================
    // HELPER — nom complet du médecin courant (hardcodé pour l'instant)
    // Changer $medId pour switcher de médecin
    // Remplacer par $this->getUser() quand le login sera ajouté
    // ============================================================
    private function getNomMedecinCourant(MedecinRepository $medecinRepo): string
    {
        $medId   = 1; // ← changer ici pour tester : 1=Amrani, 2=Kallel, 3=Zouhaier
        $medecin = $medecinRepo->find($medId);

        if (!$medecin) {
            throw new \Exception('Médecin introuvable.');
        }

        return 'Dr. ' . $medecin->getPrenom() . ' ' . $medecin->getNom();
    }

    #[Route('/showAllRdvfront/{userId}', name: 'showAllRdvfront', defaults: ['userId' => null])]
    public function showAllRdvfront(
        RdvRepository $rdvRepository,
        Request $request,
        MedecinRepository $medecinRepo,
        UserRepository $userRepo,
        int $userId = null
    ): Response {
        // Si userId passé → RDVs de ce patient uniquement, sinon tous
        if ($userId !== null) {
            $patient = $userRepo->find($userId);
            if (!$patient) {
                throw $this->createNotFoundException("Patient #$userId introuvable.");
            }
            $rdvs = $rdvRepository->findBy(
                ['patient' => $patient],
                ['date' => 'ASC', 'hdebut' => 'ASC']
            );
        } else {
            $rdvs = $rdvRepository->findBy([], ['date' => 'ASC', 'hdebut' => 'ASC']);
        }

        $now         = new \DateTime();
        $prochainRdv = null;
        foreach ($rdvs as $rdv) {
            if ($rdv->getStatut() === 'Annulé') continue;
            $rdvDateTime = new \DateTime(
                $rdv->getDate()->format('Y-m-d') . ' ' . $rdv->getHdebut()->format('H:i')
            );
            if ($rdvDateTime > $now) {
                $prochainRdv = $rdv;
                break;
            }
        }

        $specialite = $request->query->get('specialite');
        $nom        = $request->query->get('nom');
        $type       = $request->query->get('type');
        $medecins   = [];
        $searched   = false;

        if ($specialite || $nom || $type) {
            $medecins = $medecinRepo->search($specialite, $nom, $type);
            $searched = true;
        }
         $patient = $userRepo->find(2);
    if ($patient) {
        $rdv->setPatient($patient);
    }

        return $this->render('rdv/front/show.html.twig', [
            'rdvs'        => $rdvs,
            'prochainRdv' => $prochainRdv,
            'medecins'    => $medecins,
            'searched'    => $searched,
            'specialite'  => $specialite,
            'nom'         => $nom,
            'type'        => $type,
            'userId'      => $userId,
        ]);
    }

    // ============================================================
    // Mapping nom médecin → User ID (table user)
    // ============================================================
    private const MEDECIN_USER_MAP = [
        'Dr. Sarah Amrani'      => 1,
        'Dr. Mohamed Kallel'    => 2,
        'Dr. Ali Zouhaier'      => 3,
        'Dr. Karim Ben Youssef' => 4,
    ];

    // ID du patient courant (hardcodé — remplacer par $this->getUser() quand login ajouté)
    private const PATIENT_ID = 1;

    // ID du médecin par défaut pour le back (remplacer par $this->getUser() quand login ajouté)
    private const DYNAMIC_MED_ID = 1;

    // ============================================================
    #[Route('/AjouterRdv', name: 'AjouterRdv')]
    public function AjouterRdv(
        Request $request,
        ManagerRegistry $mr,
        UserRepository $userRepo
    ): Response {
        $rdv  = new Rdv();
        $form = $this->createForm(RdvType::class, $rdv);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $rdv->setStatut('En attente');

            // ── hfin auto +30 min ──
            if ($rdv->getHdebut()) {
                $hfin = clone $rdv->getHdebut();
                $hfin->modify('+30 minutes');
                $rdv->setHfin($hfin);
            }

            // ── patient_id ──
 $patient = $userRepo->find(2);
    if ($patient) {
        $rdv->setPatient($patient);
    }
            // ── medecin_user_id ── selon le nom choisi dans le formulaire
            $nomMedecin = $rdv->getMedecin();
            $medecinUserId = self::MEDECIN_USER_MAP[$nomMedecin] ?? null;
            if ($medecinUserId) {
                $medecinUser = $userRepo->find($medecinUserId);
                if ($medecinUser) {
                    $rdv->setMedecinUser($medecinUser);
                }
            }

            $em = $mr->getManager();
            $em->persist($rdv);
            $em->flush();

            if ($request->isXmlHttpRequest()) {
                return $this->json(['success' => true]);
            }
            return $this->redirectToRoute('showAllRdvfront');
        }

        if ($request->isXmlHttpRequest()) {
            return $this->render('rdv/front/_addRdvFormContent.html.twig', [
                'form' => $form->createView(),
            ]);
        }

        return $this->render('rdv/front/AjouterRdv.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // ============================================================
    #[Route('/editForm/{id}', name: 'editForm')]
    public function editForm(Request $request, Rdv $rdv, ManagerRegistry $mr): Response
    {
        $form = $this->createForm(RdvType::class, $rdv, ['rdv' => $rdv]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($rdv->getHdebut()) {
                $hfin = clone $rdv->getHdebut();
                $hfin->modify('+30 minutes');
                $rdv->setHfin($hfin);
            }
            $mr->getManager()->flush();

            if ($request->isXmlHttpRequest()) {
                return $this->json(['success' => true]);
            }
            return $this->redirectToRoute('showAllRdvfront');
        }

        if ($request->isXmlHttpRequest()) {
            return $this->render('rdv/front/ModifierRdv.html.twig', [
                'form' => $form->createView(),
                'rdv'  => $rdv,
            ]);
        }

        return $this->render('rdv/front/ModifierRdv.html.twig', [
            'form' => $form->createView(),
            'rdv'  => $rdv,
        ]);
    }

    // ============================================================
    #[Route('/showAlldispoBack', name: 'showAlldispoBackLegacy')]
    public function showAlldispoBack(RdvRepository $repo): Response
    {
        return $this->redirectToRoute('showAlldispoBackDispo');
    }

    // ============================================================
    // BACK — Affiche uniquement les RDV du médecin courant
    // ============================================================
    #[Route('/showAllRdvBack/{medecinId}', name: 'showAllRdvBack', defaults: ['medecinId' => null])]
    public function showAllRdvBack(
        RdvRepository $repo,
        MedecinRepository $medecinRepo,
        int $medecinId = null
    ): Response {
        // Si un ID est passé dans l'URL → on l'utilise, sinon hardcodé = 1
        $id      = $medecinId ?? self::DYNAMIC_MED_ID;
        $medecin = $medecinRepo->find($id);

        if (!$medecin) {
            throw $this->createNotFoundException("Médecin #$id introuvable.");
        }

        $nomMedecin = 'Dr. ' . $medecin->getPrenom() . ' ' . $medecin->getNom();

        $rdvs = $repo->findBy(
            ['medecin' => $nomMedecin],
            ['date' => 'DESC']
        );

        $today = new \DateTime('today');

        $countAujourdhui = count(array_filter($rdvs, fn($r) =>
            $r->getDate()->format('Y-m-d') === $today->format('Y-m-d')
        ));
        $countEnAttente = count(array_filter($rdvs, fn($r) =>
            $r->getStatut() === 'En attente'
        ));
        $countTermines = count(array_filter($rdvs, fn($r) =>
            $r->getDate() < $today && $r->getStatut() === 'Confirmé'
        ));

        return $this->render('rdv/back/showRdv.html.twig', [
            'rdvs'            => $rdvs,
            'nomMedecin'      => $nomMedecin,
            'medecinId'       => $id,
            'countAujourdhui' => $countAujourdhui,
            'countEnAttente'  => $countEnAttente,
            'countTermines'   => $countTermines,
        ]);
    }

    // ============================================================
    #[Route('/showOne/{id}', name: 'showOne')]
    public function showOne(Rdv $rdv): Response
    {
        return $this->render('rdv/front/showOne.html.twig', [
            'showOne' => $rdv,
        ]);
    }

    // ============================================================
    #[Route('/delete/{id}', name: 'deleteRdv', methods: ['POST'])]
    public function delete(Request $request, Rdv $rdv, ManagerRegistry $mr): Response
    {
        if ($this->isCsrfTokenValid('delete' . $rdv->getId(), $request->request->get('_token'))) {
            $em = $mr->getManager();
            $em->remove($rdv);
            $em->flush();
        }
        return $this->redirectToRoute('showAllRdvfront');
    }

    // ============================================================
    #[Route('/editBack/{id}', name: 'editRdvBack', methods: ['POST'])]
    public function editRdvBack(Request $request, Rdv $rdv, ManagerRegistry $mr): Response
    {
        $date = $request->request->get('date');
        $time = $request->request->get('hdebut');

        if ($date && $time) {
            $newDate = new \DateTime($date);
            $newTime = new \DateTime($time);
            $rdv->setDate($newDate);
            $rdv->setHdebut($newTime);
            $hfin = clone $newTime;
            $hfin->modify('+30 minutes');
            $rdv->setHfin($hfin);
            $mr->getManager()->flush();
        }

        return $this->redirectToRoute('showAllRdvBack');
    }

    // ============================================================
    #[Route('/cancel/{id}', name: 'cancelRdv', methods: ['POST'])]
    public function cancelRdv(Rdv $rdv, ManagerRegistry $mr): Response
    {
        $rdv->setStatut('Annulé');
        $mr->getManager()->flush();
        return $this->redirectToRoute('showAllRdvBack');
    }

    // ============================================================
    #[Route('/confirm/{id}', name: 'confirmRdv', methods: ['POST'])]
    public function confirmRdv(Rdv $rdv, ManagerRegistry $mr): Response
    {
        $rdv->setStatut('Confirmé');
        $mr->getManager()->flush();
        return $this->redirectToRoute('showAllRdvBack');
    }

    // ============================================================
    #[Route('/api/rdv/statuts', name: 'api_rdv_statuts')]
    public function statuts(RdvRepository $repo): \Symfony\Component\HttpFoundation\JsonResponse
    {
        $rdvs = $repo->findAll();
        $data = [];
        foreach ($rdvs as $rdv) {
            $data[] = ['id' => $rdv->getId(), 'statut' => $rdv->getStatut()];
        }
        return $this->json($data);
    }

    // ============================================================
    #[Route('/api/rappels', name: 'api_rappels')]
    public function rappels(RdvRepository $repo): \Symfony\Component\HttpFoundation\JsonResponse
    {
        $now      = new \DateTime();
        $today    = new \DateTime('today');    // aujourd'hui à 00:00:00
        $tomorrow = new \DateTime('tomorrow'); // demain à 00:00:00
        $rdvs     = $repo->findAll();
        $rappels  = [];

        foreach ($rdvs as $rdv) {
            if ($rdv->getStatut() === 'Annulé') continue;

            $rdvDateTime = new \DateTime(
                $rdv->getDate()->format('Y-m-d') . ' ' . $rdv->getHdebut()->format('H:i')
            );

            // RDV déjà passé → ignorer
            if ($rdvDateTime <= $now) continue;

            $diff    = $now->diff($rdvDateTime);
            $minutes = ($diff->days * 24 * 60) + ($diff->h * 60) + $diff->i;

            // Comparaison sur la DATE uniquement (pas les minutes)
            $rdvDate = new \DateTime($rdv->getDate()->format('Y-m-d'));

            if ($minutes <= 60) {
                // Moins d'1h → urgent (peu importe le jour)
                $niveau = 'urgent';
                $texte  = "🚨 URGENT ! RDV avec {$rdv->getMedecin()} dans {$minutes} minutes !";
            } elseif ($rdvDate == $today) {
                // Même date qu'aujourd'hui (mais > 60 min)
                $niveau = 'today';
                $texte  = "🔔 Rappel : RDV avec {$rdv->getMedecin()} aujourd'hui à {$rdv->getHdebut()->format('H:i')}";
            } elseif ($rdvDate == $tomorrow) {
                // Exactement demain
                $niveau = 'tomorrow';
                $texte  = "📅 Rappel : RDV avec {$rdv->getMedecin()} demain à {$rdv->getHdebut()->format('H:i')}";
            } else {
                continue; // Après-demain ou plus → pas de bannière
            }

            $rappels[] = [
                'id'      => $rdv->getId(),
                'message' => $texte,
                'niveau'  => $niveau,
                'minutes' => $minutes,
            ];
        }

        usort($rappels, fn($a, $b) => $a['minutes'] - $b['minutes']);

        return $this->json(['count' => count($rappels), 'rappels' => $rappels]);
    }

    // ============================================================
    #[Route('/rdv/search', name: 'rdv_search', methods: ['GET'])]
    public function search(Request $request, RdvRepository $rdvRepository): \Symfony\Component\HttpFoundation\JsonResponse
    {
        $query = trim($request->query->get('q', ''));

        $rdvs = strlen($query) < 2
            ? $rdvRepository->findBy([], ['date' => 'ASC'])
            : $rdvRepository->searchGlobal($query);

        $data = array_map(fn($rdv) => [
            'id'      => $rdv->getId(),
            'medecin' => $rdv->getMedecin(),
            'date'    => $rdv->getDate()->format('M'),
            'jour'    => $rdv->getDate()->format('d'),
            'heure'   => $rdv->getHdebut()->format('H:i') . ' - ' . $rdv->getHfin()->format('H:i'),
            'statut'  => $rdv->getStatut(),
            'motif'   => $rdv->getMotif() ?? '',
            'message' => $rdv->getMessage() ?? '',
        ], $rdvs);

        return $this->json($data);
    }

    // ============================================================
    #[Route('/historique', name: 'historique')]
    public function historique(RdvRepository $repo): Response
    {
        $rdvs      = $repo->findPasses();
        $total     = count($rdvs);
        $confirmes = count(array_filter($rdvs, fn($r) => $r->getStatut() === 'Confirmé'));
        $annules   = count(array_filter($rdvs, fn($r) => $r->getStatut() === 'Annulé'));
        $attente   = $total - $confirmes - $annules;

        return $this->render('rdv/front/historique.html.twig', [
            'rdvs'      => $rdvs,
            'total'     => $total,
            'confirmes' => $confirmes,
            'annules'   => $annules,
            'attente'   => $attente,
        ]);
    }

    // ============================================================
    #[Route('/historique/pdf', name: 'historique_pdf')]
    public function historiquePdf(RdvRepository $repo): Response
    {
        $rdvs = $repo->findPasses();
        $now  = new \DateTime();
        $html = $this->renderView('rdv/front/historique_pdf.html.twig', [
            'rdvs' => $rdvs,
            'date' => $now->format('d/m/Y H:i'),
        ]);

        $dompdf = new \Dompdf\Dompdf();
        $dompdf->getOptions()->setChroot(dirname(__DIR__, 2) . '/public');
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return new Response($dompdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="bilan-medical-' . $now->format('Y-m-d') . '.pdf"',
        ]);
    }

    // ============================================================
    #[Route('/qrcode/rdv/{id}', name: 'rdv_qrcode')]
    public function qrcode(Rdv $rdv): Response
    {
        $text = implode("\n", [
            '=== RDV MEDICAL ===',
            'Medecin : ' . $rdv->getMedecin(),
            'Date    : ' . $rdv->getDate()->format('d/m/Y'),
            'Heure   : ' . $rdv->getHdebut()->format('H:i') . ' - ' . $rdv->getHfin()->format('H:i'),
            'Motif   : ' . ($rdv->getMotif() ?? 'Consultation'),
            'Statut  : ' . $rdv->getStatut(),
            '==================',
        ]);

        $qrCode = new QrCode(
            data                : $text,
            encoding            : new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size                : 300,
            margin              : 10,
            roundBlockSizeMode  : RoundBlockSizeMode::Margin,
            foregroundColor     : new Color(29, 78, 216),
            backgroundColor     : new Color(255, 255, 255),
        );

        $result = (new PngWriter())->write($qrCode);

        return new Response($result->getString(), 200, ['Content-Type' => 'image/png']);
    }

    // ============================================================
    #[Route('/rdv/sms/{id}', name: 'rdv_sms')]
    public function sendSms(Rdv $rdv, SmsService $sms): Response
    {
        $ok = $sms->sendRappel(
            '+21629254485',
            $rdv->getMedecin(),
            $rdv->getDate()->format('d/m/Y'),
            $rdv->getHdebut()->format('H:i')
        );

        $this->addFlash($ok ? 'success' : 'error', $ok ? '✅ SMS de rappel envoyé !' : '❌ Erreur lors de l\'envoi du SMS.');

        return $this->redirectToRoute('showOne', ['id' => $rdv->getId()]);
    }

    // ============================================================
    #[Route('/test-sms', name: 'test_sms')]
    public function testSms(SmsService $sms): Response
    {
        $ok = $sms->sendRappel('+21629254485', 'Dr. Mohamed Kallel', '24/02/2026', '11:00');
        return new Response($ok ? '✅ SMS envoyé !' : '❌ Erreur');
    }
}