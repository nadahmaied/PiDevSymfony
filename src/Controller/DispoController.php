<?php

namespace App\Controller;

use App\Entity\Disponibilite;
use App\Repository\DisponibiliteRepository;
use App\Repository\RdvRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class DispoController extends AbstractController
{
    // ============================================================
    // ID DU MÉDECIN COURANT
    // Hardcodé pour l'instant — remplacer par $this->getUser() quand login ajouté
    // 1 = Dr. Sarah Amrani
    // 2 = Dr. Mohamed Kallel
    // 3 = Dr. Ali Zouhaier
    // ============================================================
    private const MED_ID = 1;

    private const HORAIRES_DEFAULT = [
        'lundi'    => [['debut' => '09:00', 'fin' => '12:00'], ['debut' => '14:00', 'fin' => '17:00']],
        'mardi'    => [['debut' => '09:00', 'fin' => '12:00'], ['debut' => '14:00', 'fin' => '17:00']],
        'mercredi' => [['debut' => '09:00', 'fin' => '12:00'], ['debut' => '14:00', 'fin' => '17:00']],
        'jeudi'    => [['debut' => '09:00', 'fin' => '12:00'], ['debut' => '14:00', 'fin' => '17:00']],
        'vendredi' => [['debut' => '09:00', 'fin' => '12:00'], ['debut' => '14:00', 'fin' => '17:00']],
        'samedi'   => [['debut' => '09:00', 'fin' => '13:00']],
        'dimanche' => [],
    ];

    private const EXTRA_AUTORISE = [
        'lundi'    => ['debut' => '12:00', 'fin' => '14:00'],
        'mardi'    => ['debut' => '12:00', 'fin' => '14:00'],
        'mercredi' => ['debut' => '12:00', 'fin' => '14:00'],
        'jeudi'    => ['debut' => '12:00', 'fin' => '14:00'],
        'vendredi' => ['debut' => '12:00', 'fin' => '14:00'],
        'samedi'   => null,
        'dimanche' => ['debut' => '10:00', 'fin' => '14:00'],
    ];

    private const SEANCE_MATIN = ['debut' => '09:00', 'fin' => '12:00'];
    private const SEANCE_SOIR  = ['debut' => '14:00', 'fin' => '17:00'];

    // ============================================================
    #[Route('/back/disponibilites/{medecinId}', name: 'showAlldispoBackDispo', defaults: ['medecinId' => null])]
    public function showAllDispoBack(
        DisponibiliteRepository $dispoRepo,
        RdvRepository $rdvRepo,
        int $medecinId = null
    ): Response {
        $medId          = $medecinId ?? self::MED_ID;
        $disponibilites = $dispoRepo->findBy(['MedId' => $medId], ['dateDispo' => 'ASC']);
        $rdvs           = $rdvRepo->findAll();
        $dispoData      = [];

        foreach ($disponibilites as $dispo) {
            $date = $dispo->getDateDispo()->format('Y-m-d');
            $dispoData[$date]['dispos'][] = [
                'id'     => $dispo->getId(),
                'hdebut' => $dispo->getHdebut()->format('H:i'),
                'hfin'   => $dispo->getHFin()->format('H:i'),
                'statut' => $dispo->getStatut(),
                'nbrH'   => $dispo->getNbrH(),
            ];
        }

        foreach ($rdvs as $rdv) {
            $date = $rdv->getDate()->format('Y-m-d');
            if (!isset($dispoData[$date])) {
                $dispoData[$date] = ['dispos' => [], 'rdvs' => []];
            }
            $dispoData[$date]['rdvs'][] = [
                'id'      => $rdv->getId(),
                'hdebut'  => $rdv->getHdebut()->format('H:i'),
                'hfin'    => $rdv->getHfin() ? $rdv->getHfin()->format('H:i') : '',
                'motif'   => $rdv->getMotif(),
                'statut'  => $rdv->getStatut(),
                'medecin' => $rdv->getMedecin(),
            ];
        }

        // Stats pour les cartes
        $today = new \DateTime('today');
        $allRdvs = $rdvRepo->findAll();
        $countAujourdhui = count(array_filter($allRdvs, fn($r) => $r->getDate()->format('Y-m-d') === $today->format('Y-m-d')));
        $countEnAttente  = count(array_filter($allRdvs, fn($r) => $r->getStatut() === 'En attente'));
        $countTermines   = count(array_filter($allRdvs, fn($r) => $r->getDate() < $today && $r->getStatut() === 'Confirmé'));

        return $this->render('rdv/back/showAlldispoBack.html.twig', [
            'dispoData'       => $dispoData,
            'horairesDefault' => self::HORAIRES_DEFAULT,
            'medecinId'       => $medId,
            'countAujourdhui' => $countAujourdhui,
            'countEnAttente'  => $countEnAttente,
            'countTermines'   => $countTermines,
        ]);
    }

    // ============================================================
    // GET données d'un jour (AJAX — consommé aussi par le front)
    // URL : /back/disponibilite/get/{date}?medecin_id=1
    // Le paramètre medecin_id est optionnel (défaut = MED_ID)
    // ============================================================
    #[Route('/back/disponibilite/get/{date}', name: 'getDispoByDate', methods: ['GET'])]
    public function getDispoByDate(
        string $date,
        Request $request,
        DisponibiliteRepository $dispoRepo,
        RdvRepository $rdvRepo
    ): JsonResponse {
        // Le front peut passer ?medecin_id=X pour récupérer les dispos d'un médecin spécifique
        $medId = (int) $request->query->get('medecin_id', self::MED_ID);

        try {
            $dateObj = new \DateTime($date);
            $jourFr  = $this->getJourFrancais($dateObj);

            $toutesDispos         = $dispoRepo->findByMedecinAndDate($medId, $dateObj);
            $disposPersonnalisees = [];
            $seancesAnnulees      = [];

            foreach ($toutesDispos as $dispo) {
                $item = [
                    'id'     => $dispo->getId(),
                    'hdebut' => $dispo->getHdebut()->format('H:i'),
                    'hfin'   => $dispo->getHFin()->format('H:i'),
                    'statut' => $dispo->getStatut(),
                    'nbrH'   => $dispo->getNbrH(),
                ];
                if ($dispo->getStatut() === 'non_disponible') {
                    $seancesAnnulees[] = $item;
                } else {
                    $disposPersonnalisees[] = $item;
                }
            }

            $rdvsEntities = $rdvRepo->findBy(['date' => $dateObj]);
            $rdvs = [];
            foreach ($rdvsEntities as $rdv) {
                $rdvs[] = [
                    'id'      => $rdv->getId(),
                    'hdebut'  => $rdv->getHdebut()->format('H:i'),
                    'hfin'    => $rdv->getHfin() ? $rdv->getHfin()->format('H:i') : '',
                    'motif'   => $rdv->getMotif(),
                    'statut'  => $rdv->getStatut(),
                    'medecin' => $rdv->getMedecin(),
                    'message' => $rdv->getMessage(),
                ];
            }
            usort($rdvs, fn($a, $b) => strcmp($a['hdebut'], $b['hdebut']));

            $extraAutorise      = self::EXTRA_AUTORISE[$jourFr] ?? null;
            $peutAnnulerSeances = !in_array($jourFr, ['samedi', 'dimanche']);

            $extraActif = false;
            if ($extraAutorise) {
                foreach ($disposPersonnalisees as $d) {
                    if ($d['hdebut'] === $extraAutorise['debut'] && $d['hfin'] === $extraAutorise['fin']) {
                        $extraActif = true;
                        break;
                    }
                }
            }

            return new JsonResponse([
                'success'            => true,
                'date'               => $date,
                'jour'               => $jourFr,
                'horairesDefault'    => self::HORAIRES_DEFAULT[$jourFr] ?? [],
                'disponibilites'     => $disposPersonnalisees,
                'seancesAnnulees'    => $seancesAnnulees,
                'rdvs'               => $rdvs,
                'extraAutorise'      => $extraAutorise,
                'extraActif'         => $extraActif,
                'peutAjouter'        => $extraAutorise !== null,
                'peutAnnulerSeances' => $peutAnnulerSeances,
            ]);

        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'Erreur : ' . $e->getMessage()], 400);
        }
    }

    // ============================================================
    #[Route('/back/disponibilite/toggle-extra', name: 'toggleExtraDispo', methods: ['POST'])]
    public function toggleExtraDispo(
        Request $request,
        EntityManagerInterface $em,
        DisponibiliteRepository $dispoRepo
    ): JsonResponse {
        $medId = self::MED_ID;

        try {
            $data    = json_decode($request->getContent(), true);
            $dateObj = new \DateTime($data['date']);
            $jourFr  = $this->getJourFrancais($dateObj);

            if ($jourFr === 'samedi') {
                return new JsonResponse(['success' => false, 'message' => 'Aucun ajout autorisé le samedi.'], 400);
            }

            $extra = self::EXTRA_AUTORISE[$jourFr] ?? null;
            if (!$extra) {
                return new JsonResponse(['success' => false, 'message' => 'Aucun créneau extra défini pour ce jour.'], 400);
            }

            $existing = $dispoRepo->findExtraExistant($medId, $dateObj, $extra['debut'], $extra['fin']);

            if ($existing) {
                $em->remove($existing);
                $em->flush();
                return new JsonResponse(['success' => true, 'action' => 'supprime', 'message' => 'Créneau supprimé.']);
            }

            $hdebut = \DateTime::createFromFormat('H:i', $extra['debut']);
            $hfin   = \DateTime::createFromFormat('H:i', $extra['fin']);
            if (!$hdebut instanceof \DateTime || !$hfin instanceof \DateTime) {
                return new JsonResponse(['success' => false, 'message' => 'Heure invalide.'], 400);
            }

            $dispo = new Disponibilite();
            $dispo->setMedId($medId);
            $dispo->setDateDispo($dateObj);
            $dispo->setHdebut($hdebut);
            $dispo->setHFin($hfin);
            $dispo->setNbrH($hdebut->diff($hfin)->h);
            $dispo->setStatut('disponible');

            $em->persist($dispo);
            $em->flush();

            return new JsonResponse(['success' => true, 'action' => 'ajoute', 'message' => 'Créneau ajouté.']);

        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'Erreur : ' . $e->getMessage()], 400);
        }
    }

    // ============================================================
    #[Route('/back/disponibilite/cancel-session', name: 'cancelDefaultSession', methods: ['POST'])]
    public function cancelDefaultSession(
        Request $request,
        EntityManagerInterface $em,
        DisponibiliteRepository $dispoRepo
    ): JsonResponse {
        $medId = self::MED_ID;

        try {
            $data    = json_decode($request->getContent(), true);
            $dateObj = new \DateTime($data['date']);
            $session = $data['session'];
            $jourFr  = $this->getJourFrancais($dateObj);

            if (in_array($jourFr, ['samedi', 'dimanche'])) {
                return new JsonResponse(['success' => false, 'message' => 'Annulation non disponible ce jour.'], 400);
            }
            if (!in_array($session, ['matin', 'soir'])) {
                return new JsonResponse(['success' => false, 'message' => 'Session invalide'], 400);
            }

            $seance   = ($session === 'matin') ? self::SEANCE_MATIN : self::SEANCE_SOIR;
            $existing = $dispoRepo->findSeanceAnnulee($medId, $dateObj, $seance['debut'], $seance['fin']);

            if ($existing) {
                $em->remove($existing);
                $em->flush();
                return new JsonResponse(['success' => true, 'action' => 'restauree', 'message' => 'Séance restaurée.']);
            }

            $hdebut = \DateTime::createFromFormat('H:i', $seance['debut']);
            $hfin   = \DateTime::createFromFormat('H:i', $seance['fin']);
            if (!$hdebut instanceof \DateTime || !$hfin instanceof \DateTime) {
                return new JsonResponse(['success' => false, 'message' => 'Heure invalide.'], 400);
            }

            $dispo = new Disponibilite();
            $dispo->setMedId($medId);
            $dispo->setDateDispo($dateObj);
            $dispo->setHdebut($hdebut);
            $dispo->setHFin($hfin);
            $dispo->setNbrH($hdebut->diff($hfin)->h);
            $dispo->setStatut('non_disponible');

            $em->persist($dispo);
            $em->flush();

            return new JsonResponse(['success' => true, 'action' => 'annulee', 'message' => 'Séance annulée.']);

        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'Erreur : ' . $e->getMessage()], 400);
        }
    }

    // ============================================================
    #[Route('/back/disponibilite/edit/{id}', name: 'editDispoBack', methods: ['POST'])]
    public function editDispo(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        DisponibiliteRepository $dispoRepo
    ): JsonResponse {
        $dispo = $dispoRepo->find($id);
        if (!$dispo) {
            return new JsonResponse(['success' => false, 'message' => 'Disponibilité introuvable'], 404);
        }
        if ($dispo->getStatut() === 'non_disponible') {
            return new JsonResponse(['success' => false, 'message' => 'Impossible de modifier une séance annulée.'], 400);
        }

        try {
            $data = json_decode($request->getContent(), true);
            if (isset($data['hdebut'])) {
                $hdebut = \DateTime::createFromFormat('H:i', (string) $data['hdebut']);
                if (!$hdebut instanceof \DateTime) {
                    return new JsonResponse(['success' => false, 'message' => 'Heure de debut invalide.'], 400);
                }
                $dispo->setHdebut($hdebut);
            }
            if (isset($data['hfin'])) {
                $hfin = \DateTime::createFromFormat('H:i', (string) $data['hfin']);
                if (!$hfin instanceof \DateTime) {
                    return new JsonResponse(['success' => false, 'message' => 'Heure de fin invalide.'], 400);
                }
                $dispo->setHFin($hfin);
            }
            $diff = $dispo->getHdebut()->diff($dispo->getHFin());
            $dispo->setNbrH((int) round($diff->h + ($diff->i / 60)));
            $em->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Modifiée avec succès',
                'dispo'   => [
                    'id'     => $dispo->getId(),
                    'hdebut' => $dispo->getHdebut()->format('H:i'),
                    'hfin'   => $dispo->getHFin()->format('H:i'),
                    'statut' => $dispo->getStatut(),
                    'nbrH'   => $dispo->getNbrH(),
                ],
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'Erreur : ' . $e->getMessage()], 400);
        }
    }

    // ============================================================
    #[Route('/back/disponibilite/delete/{id}', name: 'deleteDispo', methods: ['POST'])]
    public function deleteDispo(
        int $id,
        EntityManagerInterface $em,
        DisponibiliteRepository $dispoRepo
    ): JsonResponse {
        $dispo = $dispoRepo->find($id);
        if (!$dispo) {
            return new JsonResponse(['success' => false, 'message' => 'Disponibilité introuvable'], 404);
        }
        $em->remove($dispo);
        $em->flush();
        return new JsonResponse(['success' => true, 'message' => 'Supprimée avec succès']);
    }

    // ============================================================
    private function getJourFrancais(\DateTime $date): string
    {
        return [
            'Monday'    => 'lundi',
            'Tuesday'   => 'mardi',
            'Wednesday' => 'mercredi',
            'Thursday'  => 'jeudi',
            'Friday'    => 'vendredi',
            'Saturday'  => 'samedi',
            'Sunday'    => 'dimanche',
        ][$date->format('l')] ?? 'lundi';
    }
}
