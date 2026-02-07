<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/baseFront', name: 'baseFont')]
    public function baseFont(): Response
    {
        return $this->render('/baseFront.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }
      #[Route('/baseBack', name: 'baseBack')]
    public function baseBack(): Response
    {
        return $this->render('/baseBack.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }
}
