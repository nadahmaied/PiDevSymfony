<?php

namespace App\Controller;

use App\Repository\AnnonceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StatistiquesController extends AbstractController
{
    #[Route('/statistiques', name: 'app_statistiques')]
    public function index(AnnonceRepository $annonceRepository): Response
    {
        $annoncesParUrgence = $annonceRepository->countByUrgence();

        $labels = array_column($annoncesParUrgence, 'urgence');
        $data = array_map('intval', array_column($annoncesParUrgence, 'total'));
        $total = array_sum($data);

        return $this->render('statistiques/dashboard.html.twig', [
            'annoncesParUrgence' => $annoncesParUrgence,
            'annoncesParUrgenceLabels' => $labels,
            'annoncesParUrgenceData' => $data,
            'annoncesTotal' => $total,
        ]);
    }
}

