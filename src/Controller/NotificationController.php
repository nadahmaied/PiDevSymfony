<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\RdvRepository;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class NotificationController extends AbstractController
{
    #[Route('/api/notifications', name: 'api_notifications')]
    public function index(RdvRepository $repo, SessionInterface $session): JsonResponse
    {
        // Récupérer tous les RDVs
        $rdvs = $repo->findAll();

        // IDs déjà lus depuis la session
        $lues = $session->get('notifs_lues', []);

        $notifications = [];

        foreach ($rdvs as $rdv) {
            $id     = $rdv->getId();
            $medecin = $rdv->getMedecin();
            $date   = $rdv->getDate()->format('d/m/Y');

            // Générer le message selon le statut
            if ($rdv->getStatut() === 'Confirmé') {
                $notifications[] = [
                    'id'      => $id,
                    'message' => "✅ RDV avec {$medecin} le {$date} est confirmé",
                    'type'    => 'success',
                    'heure'   => $rdv->getHdebut()->format('H:i'),
                ];
            } elseif ($rdv->getStatut() === 'Annulé') {
                $notifications[] = [
                    'id'      => $id,
                    'message' => "❌ RDV avec {$medecin} le {$date} a été annulé",
                    'type'    => 'danger',
                    'heure'   => $rdv->getHdebut()->format('H:i'),
                ];
            } elseif ($rdv->getStatut() === 'En attente') {
                $notifications[] = [
                    'id'      => $id,
                    'message' => "⏳ RDV avec {$medecin} le {$date} est en attente",
                    'type'    => 'warning',
                    'heure'   => $rdv->getHdebut()->format('H:i'),
                ];
            }
        }

        // Filtrer les notifications déjà lues
        $nonLues = array_values(array_filter(
            $notifications,
            fn($n) => !in_array($n['id'], $lues)
        ));

        return new JsonResponse([
            'count'         => count($nonLues),
            'notifications' => $nonLues,
        ]);
    }

    #[Route('/api/notifications/lu/{id}', name: 'api_notification_lu', methods: ['POST'])]
    public function marquerLu(int $id, SessionInterface $session): JsonResponse
    {
        $lues = $session->get('notifs_lues', []);

        if (!in_array($id, $lues)) {
            $lues[] = $id;
            $session->set('notifs_lues', $lues);
        }

        return new JsonResponse(['success' => true]);
    }
}