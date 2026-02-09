<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Rdv;
use App\Form\RdvType;

final class RdvController extends AbstractController
{
     #[Route('/showAllRdvfront', name: 'showAllRdvfront')]
public function showAllRdvfront(): Response
{
    return $this->render('rdv/front/show.html.twig');
}
    #[Route('/AjouterRdv', name: 'AjouterRdv')]
public function AjouterRdv(): Response
{
    $form = $this->createForm(RdvType::class);

    return $this->render('rdv/front/AjouterRdv.html.twig', [
        'form' => $form->createView(),
    ]);
}

#[Route('/editForm/{id}', name: 'editForm')]
public function editForm(Rdv $rdv): Response
{
    $form = $this->createForm(RdvType::class, $rdv);

    return $this->render('rdv/front/ModifierRdv.html.twig', [
        'form' => $form->createView(),
    ]);
}


    #[Route('/showAllRdvBack', name: 'showAllRdvBack')]
    public function showAllRdvBack(): Response
    {
        return $this->render('rdv/back/show.html.twig', [
            'controller_name' => 'RdvController',
        ]);
    }

#[Route('/showOne/{id}', name: 'showOne')]
public function showOne(Rdv $rdv): Response
{
    return $this->render('rdv/front/showOne.html.twig', [
        'showOne' => $rdv,
    ]);
}



}
