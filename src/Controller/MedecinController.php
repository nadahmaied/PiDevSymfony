<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\MedecinRepository;

class MedecinController extends AbstractController
{
    #[Route('/specialistes', name: 'specialistes')]
    public function index(Request $request, MedecinRepository $repo): Response
    {
        $specialite = $request->query->get('specialite');
        $nom        = $request->query->get('nom');
        $type       = $request->query->get('type');

        $medecins = [];
        $searched = false;

        if ($specialite || $nom || $type) {
            $medecins = $repo->search($specialite, $nom, $type);
            $searched = true;
        }

        return $this->render('rdv/front/show.html.twig', [
            'rdvs'      => [],
            'medecins'  => $medecins,
            'searched'  => $searched,
            'specialite'=> $specialite,
            'nom'       => $nom,
            'type'      => $type,
        ]);
    }
}