<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\MedecinRepository;
use App\Repository\DisponibiliteRepository;
use App\Repository\RdvRepository;
use App\Entity\Rdv;
use Doctrine\Persistence\ManagerRegistry;

final class CalendrierController extends AbstractController
{
    private const DYNAMIC_MED_ID = 1;

    // ============================================================
    // SPÉCIALITÉS
    // ============================================================
    #[Route('/api/calendrier/specialites', name: 'api_calendrier_specialites', methods: ['GET'])]
    public function getSpecialites(MedecinRepository $medecinRepo): JsonResponse
    {
        $specialites = [];
        foreach ($medecinRepo->findAll() as $m) {
            $sp = $m->getSpecialite();
            if ($sp && !in_array($sp, $specialites)) $specialites[] = $sp;
        }
        sort($specialites);

        $icons = [
            'Cardiologie' => '❤️', 'Ophtalmologie' => '👁️', 'Neurologie' => '🧠',
            'Dermatologie' => '🩺', 'Pédiatrie' => '👶', 'Gynécologie' => '🌸',
            'Orthopédie' => '🦴', 'Pneumologie' => '🫁', 'Gastroentérologie' => '💊',
            'Dentiste' => '🦷',
        ];

        return $this->json(array_map(fn($sp) => [
            'nom'  => $sp,
            'icon' => $icons[$sp] ?? '🏥',
        ], $specialites));
    }

    // ============================================================
    // MÉDECINS par spécialité
    // ============================================================
    #[Route('/api/calendrier/medecins', name: 'api_calendrier_medecins', methods: ['GET'])]
    public function getMedecins(Request $request, MedecinRepository $medecinRepo): JsonResponse
    {
        $specialite = $request->query->get('specialite', '');
        if (!$specialite) return $this->json(['error' => 'Spécialité requise'], 400);

        return $this->json(array_map(fn($m) => [
            'id'         => $m->getId(),
            'nom'        => $m->getNom(),
            'prenom'     => $m->getPrenom(),
            'specialite' => $m->getSpecialite(),
            'type'       => $m->getType(),
            'disponible' => $m->isDisponible(),
            'photo'      => $m->getPhoto(),
            'initiales'  => strtoupper(substr($m->getPrenom(), 0, 1) . substr($m->getNom(), 0, 1)),
            'dynamique'  => ($m->getId() === self::DYNAMIC_MED_ID),
        ], $medecinRepo->findBy(['specialite' => $specialite])));
    }

    // ============================================================
    // CRÉNEAUX
    // ============================================================
    #[Route('/api/calendrier/creneaux', name: 'api_calendrier_creneaux', methods: ['GET'])]
    public function getCreneaux(
        Request $request,
        DisponibiliteRepository $dispoRepo,
        RdvRepository $rdvRepo,
        MedecinRepository $medecinRepo
    ): JsonResponse {
        $medecinId = (int) $request->query->get('medecin_id');
        $dateStr   = $request->query->get('date', '');

        if (!$medecinId || !$dateStr) return $this->json(['error' => 'Paramètres manquants'], 400);

        try { $date = new \DateTime($dateStr); }
        catch (\Exception $e) { return $this->json(['error' => 'Date invalide'], 400); }

        $jourSemaine = (int) $date->format('N');
        $medecin     = $medecinRepo->find($medecinId);
        $nomMedecin  = $medecin ? 'Dr. ' . $medecin->getPrenom() . ' ' . $medecin->getNom() : null;

        $rdvsExistants   = $rdvRepo->findBy(['date' => $date, 'medecin' => $nomMedecin]);
        $heuresReservees = [];
        foreach ($rdvsExistants as $rdv) {
            if ($rdv->getStatut() !== 'Annulé' && $rdv->getHdebut()) {
                $heuresReservees[] = $rdv->getHdebut()->format('H:i');
            }
        }

        if ($medecinId === self::DYNAMIC_MED_ID) {
            return $this->getCreneauxDynamic($medecinId, $date, $jourSemaine, $dispoRepo, $heuresReservees);
        }

        return $this->getCreneauxStatique($jourSemaine, $heuresReservees);
    }

    // ============================================================
    // CRÉNEAUX DYNAMIQUE — médecin id=1
    // ============================================================
    private function getCreneauxDynamic(
        int $medecinId, \DateTime $date, int $jourSemaine,
        DisponibiliteRepository $dispoRepo, array $heuresReservees
    ): JsonResponse {
        $toutesDispos    = $dispoRepo->findByMedecinAndDate($medecinId, $date);
        $seancesAnnulees = [];
        $disposExtra     = [];

        foreach ($toutesDispos as $dispo) {
            $item = ['hdebut' => $dispo->getHdebut()->format('H:i'), 'hfin' => $dispo->getHFin()->format('H:i')];
            if ($dispo->getStatut() === 'non_disponible') $seancesAnnulees[] = $item;
            else $disposExtra[] = $item;
        }

        $matinAnnule = $this->isSeanceAnnulee($seancesAnnulees, '09:00', '12:00');
        $soirAnnule  = $this->isSeanceAnnulee($seancesAnnulees, '14:00', '17:00');
        $midiActif   = $this->isExtraActif($disposExtra, '12:00', '14:00');

        if ($jourSemaine === 6) return $this->jsonCreneaux($this->genererSlots('09:00', '13:00', $heuresReservees), true, null);

        if ($jourSemaine === 7) {
            $dimExtra = $this->isExtraActif($disposExtra, '10:00', '14:00');
            return $dimExtra
                ? $this->jsonCreneaux($this->genererSlots('10:00', '14:00', $heuresReservees), false, 'Dimanche — créneaux exceptionnels 10h00-14h00')
                : $this->jsonCreneaux([], false, 'Aucun créneau disponible le dimanche');
        }

        $creneaux = [];
        $current  = new \DateTime('09:00');
        $fin      = new \DateTime('17:00');

        while ($current < $fin) {
            $h          = $current->format('H:i');
            $disponible = true;
            if ($h >= '12:00' && $h < '14:00') $disponible = $midiActif;
            if ($matinAnnule && $h >= '09:00' && $h < '12:00') $disponible = false;
            if ($soirAnnule  && $h >= '14:00' && $h < '17:00') $disponible = false;
            if (in_array($h, $heuresReservees)) $disponible = false;
            $creneaux[] = ['heure' => $h, 'disponible' => $disponible];
            $current->modify('+30 minutes');
        }

        return $this->jsonCreneaux($creneaux, false, null);
    }

    // ============================================================
    // CRÉNEAUX STATIQUES — autres médecins
    // ============================================================
    private function getCreneauxStatique(int $jourSemaine, array $heuresReservees): JsonResponse
    {
        if ($jourSemaine === 7) return $this->jsonCreneaux([], false, 'Aucun créneau disponible le dimanche');
        if ($jourSemaine === 6) return $this->jsonCreneaux($this->genererSlots('09:00', '13:00', $heuresReservees), true, null);

        $creneaux = array_merge(
            $this->genererSlots('09:00', '12:00', $heuresReservees),
            $this->genererSlotsBloques('12:00', '14:00'),
            $this->genererSlots('14:00', '17:00', $heuresReservees)
        );

        return $this->jsonCreneaux($creneaux, false, null);
    }

    // ============================================================
    // HELPERS
    // ============================================================

    private function genererSlots(string $debut, string $fin, array $heuresReservees): array
    {
        $slots = []; $current = new \DateTime($debut); $finObj = new \DateTime($fin);
        while ($current < $finObj) {
            $h = $current->format('H:i');
            $slots[] = ['heure' => $h, 'disponible' => !in_array($h, $heuresReservees)];
            $current->modify('+30 minutes');
        }
        return $slots;
    }

    private function genererSlotsBloques(string $debut, string $fin): array
    {
        $slots = []; $current = new \DateTime($debut); $finObj = new \DateTime($fin);
        while ($current < $finObj) {
            $slots[] = ['heure' => $current->format('H:i'), 'disponible' => false, 'pris' => false];
            $current->modify('+30 minutes');
        }
        return $slots;
    }

    private function isSeanceAnnulee(array $seancesAnnulees, string $debut, string $fin): bool
    {
        foreach ($seancesAnnulees as $s) { if ($s['hdebut'] === $debut && $s['hfin'] === $fin) return true; }
        return false;
    }

    private function isExtraActif(array $disposExtra, string $debut, string $fin): bool
    {
        foreach ($disposExtra as $d) { if ($d['hdebut'] === $debut && $d['hfin'] === $fin) return true; }
        return false;
    }

    private function jsonCreneaux(array $creneaux, bool $samedi, ?string $message): JsonResponse
    {
        return new JsonResponse(['creneaux' => $creneaux, 'samedi' => $samedi, 'message' => $message]);
    }

    // ============================================================
    // STRIPE — Créer un PaymentIntent (70 DT affiché = 7000 centimes EUR)
    // ============================================================
    #[Route('/api/calendrier/payment-intent', name: 'api_payment_intent', methods: ['POST'])]
    public function createPaymentIntent(): JsonResponse
    {
        try {
            \Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

            $paymentIntent = \Stripe\PaymentIntent::create([
                'amount'                    => 7000,  // 70 DT affiché (Stripe ne supporte pas TND → on charge en EUR)
                'currency'                  => 'eur',
                'automatic_payment_methods' => ['enabled' => true],
                'description'               => 'Consultation médicale — VitalTech',
            ]);

            return $this->json(['clientSecret' => $paymentIntent->client_secret]);

        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    // ============================================================
    // RÉSERVER un RDV
    // Statut :
    //   - "Confirmé"  si carte Stripe payée avec succès
    //   - "En attente" si sur_place ou assurance
    // ============================================================
    #[Route('/api/calendrier/reserver', name: 'api_calendrier_reserver', methods: ['POST'])]
    public function reserver(
        Request $request,
        ManagerRegistry $mr,
        MedecinRepository $medecinRepo
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $medecinId       = (int) ($data['medecin_id']     ?? 0);
        $dateStr         = $data['date']                  ?? '';
        $heure           = $data['heure']                 ?? '';
        $motif           = $data['motif']                 ?? 'Consultation';
        $message         = $data['message']               ?? '';
        $paiement        = $data['paiement']              ?? 'sur_place';
        $stripePaymentId = $data['stripe_payment_id']     ?? null;

        if (!$medecinId || !$dateStr || !$heure) {
            return $this->json(['success' => false, 'error' => 'Données manquantes'], 400);
        }

        $medecin = $medecinRepo->find($medecinId);
        if (!$medecin) {
            return $this->json(['success' => false, 'error' => 'Médecin introuvable'], 404);
        }

        // ── Vérification Stripe si paiement carte ─────────────
        if ($paiement === 'carte_en_ligne' && $stripePaymentId) {
            try {
                \Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);
                $pi = \Stripe\PaymentIntent::retrieve($stripePaymentId);
                if ($pi->status !== 'succeeded') {
                    return $this->json(['success' => false, 'error' => 'Paiement non confirmé par Stripe'], 400);
                }
            } catch (\Exception $e) {
                return $this->json(['success' => false, 'error' => 'Erreur vérification paiement Stripe'], 500);
            }
        }

        try {
            $date   = new \DateTime($dateStr);
            $hdebut = \DateTime::createFromFormat('H:i', $heure);
            $hfin   = clone $hdebut;
            $hfin->modify('+30 minutes');
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'error' => 'Date/heure invalide'], 400);
        }

        // ── Statut selon mode paiement ─────────────────────────
        $statut = ($paiement === 'carte_en_ligne' && $stripePaymentId) ? 'Confirmé' : 'En attente';

        $rdv = new Rdv();
        $rdv->setDate($date);
        $rdv->setHdebut($hdebut);
        $rdv->setHfin($hfin);
        $rdv->setMedecin('Dr. ' . $medecin->getPrenom() . ' ' . $medecin->getNom());
        $rdv->setMotif($motif);
        $rdv->setMessage($message ?: ('Paiement: ' . $paiement));
        $rdv->setStatut($statut);

        $em = $mr->getManager();
        $em->persist($rdv);
        $em->flush();

        return $this->json([
            'success' => true,
            'rdv_id'  => $rdv->getId(),
            'statut'  => $statut,
            'message' => 'Rendez-vous réservé avec succès !',
        ]);
    }

    // ============================================================
    // REÇU PDF — GET /api/calendrier/recu/{id}?payment_id=pi_xxx
    // ============================================================
    #[Route('/api/calendrier/recu/{id}', name: 'api_calendrier_recu', methods: ['GET'])]
    public function recu(Rdv $rdv, Request $request): Response
    {
        $paymentId = $request->query->get('payment_id', '—');
        $now       = new \DateTime();

        $html = '<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<style>
  * { margin:0; padding:0; box-sizing:border-box; }
  body { font-family: DejaVu Sans, Arial, sans-serif; color:#1e293b; background:white; font-size:12px; padding:40px; }

  .header { background:#1d4ed8; color:white; border-radius:12px; padding:28px 32px; margin-bottom:28px; }
  .header-row { display:table; width:100%; }
  .header-left  { display:table-cell; vertical-align:middle; }
  .header-right { display:table-cell; vertical-align:middle; text-align:right; font-size:10px; opacity:0.8; line-height:1.7; }
  .logo-name { font-size:22px; font-weight:800; color:white; }
  .logo-name span { color:#93c5fd; }
  .logo-sub  { font-size:10px; color:rgba(255,255,255,0.65); margin-top:3px; }
  .header-title  { font-size:16px; font-weight:700; margin-top:18px; }
  .header-accent { height:3px; background:linear-gradient(to right,#60a5fa,#818cf8); border-radius:2px; margin-top:14px; }

  .badge-success { display:inline-block; background:#dcfce7; color:#15803d; border:1px solid #bbf7d0; border-radius:9999px; padding:5px 16px; font-size:11px; font-weight:700; margin-bottom:20px; }

  .section { background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:20px 24px; margin-bottom:18px; }
  .section-title { font-size:10px; font-weight:700; color:#1d4ed8; text-transform:uppercase; letter-spacing:0.07em; margin-bottom:14px; border-bottom:1px solid #dbeafe; padding-bottom:6px; }
  .row { display:table; width:100%; padding:7px 0; border-bottom:1px solid #f1f5f9; }
  .row:last-child { border-bottom:none; }
  .row-label { display:table-cell; color:#64748b; font-size:11px; width:40%; }
  .row-value { display:table-cell; color:#1e293b; font-weight:700; font-size:11px; text-align:right; }

  .amount-box { background:linear-gradient(135deg,#1d4ed8,#4f46e5); color:white; border-radius:10px; padding:18px 24px; text-align:center; margin-bottom:18px; }
  .amount-label { font-size:10px; text-transform:uppercase; letter-spacing:0.08em; opacity:0.8; }
  .amount-value { font-size:36px; font-weight:800; margin:4px 0; }
  .amount-method { font-size:11px; opacity:0.75; }

  .note { background:#f0f9ff; border-left:4px solid #0ea5e9; border-radius:0 8px 8px 0; padding:12px 16px; font-size:10px; color:#0369a1; line-height:1.6; margin-bottom:18px; }

  .footer { border-top:1px solid #e2e8f0; padding-top:14px; text-align:center; font-size:9.5px; color:#94a3b8; }
</style>
</head>
<body>

<div class="header">
  <div class="header-row">
    <div class="header-left">
      <div class="logo-name">Vital<span>Tech</span></div>
      <div class="logo-sub">Plateforme de Gestion Médicale</div>
    </div>
    <div class="header-right">
      Émis le : ' . $now->format('d/m/Y à H:i') . '<br>
      Réf : RECU-' . $rdv->getId() . '-' . $now->format('YmdHi') . '<br>
      Document officiel
    </div>
  </div>
  <div class="header-title">Reçu de Paiement — Consultation Médicale</div>
  <div class="header-accent"></div>
</div>

<div style="text-align:center; margin-bottom:20px;">
  <span class="badge-success">✓ Paiement confirmé avec succès</span>
</div>

<div class="amount-box">
  <div class="amount-label">Montant payé</div>
  <div class="amount-value">70 DT</div>
  <div class="amount-method">Carte bancaire en ligne — Stripe</div>
</div>

<div class="section">
  <div class="section-title">Détails du Rendez-vous</div>
  <div class="row">
    <div class="row-label">Médecin</div>
    <div class="row-value">' . htmlspecialchars($rdv->getMedecin()) . '</div>
  </div>
  <div class="row">
    <div class="row-label">Date</div>
    <div class="row-value">' . $rdv->getDate()->format('d/m/Y') . '</div>
  </div>
  <div class="row">
    <div class="row-label">Horaire</div>
    <div class="row-value">' . $rdv->getHdebut()->format('H:i') . ' — ' . $rdv->getHfin()->format('H:i') . '</div>
  </div>
  <div class="row">
    <div class="row-label">Motif</div>
    <div class="row-value">' . htmlspecialchars($rdv->getMotif() ?? 'Consultation') . '</div>
  </div>
  <div class="row">
    <div class="row-label">Statut RDV</div>
    <div class="row-value" style="color:#15803d;">✓ Confirmé</div>
  </div>
</div>

<div class="section">
  <div class="section-title">Informations de Paiement</div>
  <div class="row">
    <div class="row-label">ID Transaction Stripe</div>
    <div class="row-value" style="font-family:monospace;font-size:10px;">' . htmlspecialchars($paymentId) . '</div>
  </div>
  <div class="row">
    <div class="row-label">Méthode</div>
    <div class="row-value">Carte bancaire (Stripe)</div>
  </div>
  <div class="row">
    <div class="row-label">Montant</div>
    <div class="row-value">70 DT</div>
  </div>
  <div class="row">
    <div class="row-label">Date de paiement</div>
    <div class="row-value">' . $now->format('d/m/Y à H:i') . '</div>
  </div>
</div>

<div class="note">
  Ce reçu confirme le paiement de votre consultation médicale. Conservez ce document comme justificatif.
  Pour toute question, contactez VitalTech à support@vitaltech.tn
</div>

<div class="footer">
  VitalTech — Gestion Médicale &copy; ' . $now->format('Y') . ' &nbsp;|&nbsp;
  Réf : RECU-' . $rdv->getId() . '-' . $now->format('YmdHi') . ' &nbsp;|&nbsp;
  Document généré automatiquement
</div>

</body>
</html>';

        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return new Response($dompdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="recu-paiement-rdv-' . $rdv->getId() . '.pdf"',
        ]);
    }
}