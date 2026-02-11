<?php

namespace App\Controller;

use App\Repository\AnnonceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(AnnonceRepository $annonceRepository): Response
    {
        $latestAnnonces = $annonceRepository->findBy(
            [],
            ['datePublication' => 'DESC'],
            6
        );

        return $this->render('home/index.html.twig', [
            'annonces' => $latestAnnonces,
        ]);
    }
}

