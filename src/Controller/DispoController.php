<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DispoController extends AbstractController
{
    #[Route('/dispo', name: 'app_dispo')]
    public function index(): Response
    {
        return $this->render('dispo/index.html.twig', [
            'controller_name' => 'DispoController',
        ]);
    }
}
