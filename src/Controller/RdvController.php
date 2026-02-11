<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Rdv;
use App\Form\RdvType;
use App\Repository\RdvRepository;
use Doctrine\Persistence\ManagerRegistry;


final class RdvController extends AbstractController
{
     #[Route('/showAllRdvfront', name: 'showAllRdvfront')]
public function showAllRdvfront(RdvRepository $rdvRepository): Response
{
    $rdvs = $rdvRepository->findBy([], ['date' => 'ASC', 'hdebut' => 'ASC']);

    return $this->render('rdv/front/show.html.twig', [
        'rdvs' => $rdvs,
    ]);
}
   /* #[Route('/AjouterRdv', name: 'AjouterRdv')]
public function AjouterRdv(Request $request): Response
{
    $form = $this->createForm(RdvType::class);

    if ($request->isXmlHttpRequest()) {
        return $this->render('rdv/front/_addRdvFormContent.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    return $this->render('rdv/front/AjouterRdv.html.twig', [
        'form' => $form->createView(),
    ]);
}*/

#[Route('/AjouterRdv', name: 'AjouterRdv')]
public function AjouterRdv(Request $request, ManagerRegistry $mr): Response
{
    // 1️⃣ Instance
    $rdv = new Rdv();

    // 2️⃣ Création formulaire lié à l'entité
    $form = $this->createForm(RdvType::class, $rdv);

    // 3️⃣ Analyse request
    $form->handleRequest($request);

    // 4️⃣ Si soumis et valide
   if ($form->isSubmitted() && $form->isValid()) {

    $rdv->setStatut('En attente');

    if ($rdv->getHdebut()) {
        $hfin = clone $rdv->getHdebut();
        $hfin->modify('+30 minutes');
        $rdv->setHfin($hfin);
    }

    $em = $mr->getManager();
    $em->persist($rdv);
    $em->flush();

    if ($request->isXmlHttpRequest()) {
        return $this->json([
            'success' => true
        ]);
    }

    return $this->redirectToRoute('showAllRdvfront');
}


    // Si popup AJAX
    if ($request->isXmlHttpRequest()) {
        return $this->render('rdv/front/_addRdvFormContent.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    return $this->render('rdv/front/AjouterRdv.html.twig', [
        'form' => $form->createView(),
    ]);
}


#[Route('/editForm/{id}', name: 'editForm')]
public function editForm(Request $request, Rdv $rdv, ManagerRegistry $mr): Response
{
    $form = $this->createForm(RdvType::class, $rdv);

    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {

        // recalcul hfin si heure modifiée
        if ($rdv->getHdebut()) {
            $hfin = clone $rdv->getHdebut();
            $hfin->modify('+30 minutes');
            $rdv->setHfin($hfin);
        }

        $em = $mr->getManager();
        $em->flush(); // PAS besoin de persist car déjà existant

        return $this->redirectToRoute('showAllRdvfront');
    }

    return $this->render('rdv/front/ModifierRdv.html.twig', [
        'form' => $form->createView(),
    ]);
}


    #[Route('/showAlldispoBack', name: 'showAlldispoBack')]
public function showAlldispoBack(RdvRepository $repo): Response
{
    $rdvs = $repo->findAll();

    $dates = [];

    foreach ($rdvs as $rdv) {
        if ($rdv->getDate()) {
            $dates[] = $rdv->getDate()->format('Y-m-d');
        }
    }

    // enlever les doublons
    $dates = array_unique($dates);

    return $this->render('rdv/back/show.html.twig', [
        'rdvDates' => $dates
    ]);
}


#[Route('/showAllRdvBack', name: 'showAllRdvBack')]
public function showAllRdvBack(RdvRepository $repo): Response
{
    $rdvs = $repo->findBy([], ['date' => 'DESC']);

    return $this->render('rdv/back/showRdv.html.twig', [
        'rdvs' => $rdvs
    ]);
}



#[Route('/showOne/{id}', name: 'showOne')]
public function showOne(Rdv $rdv): Response
{
    return $this->render('rdv/front/showOne.html.twig', [
        'showOne' => $rdv,
    ]);
}
#[Route('/delete/{id}', name: 'deleteRdv', methods: ['POST'])]
public function delete(Request $request, Rdv $rdv, ManagerRegistry $mr): Response
{
    if ($this->isCsrfTokenValid('delete'.$rdv->getId(), $request->request->get('_token'))) {

        $em = $mr->getManager();
        $em->remove($rdv);
        $em->flush();
    }

    return $this->redirectToRoute('showAllRdvfront');
}
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


#[Route('/cancel/{id}', name: 'cancelRdv', methods: ['POST'])]
public function cancelRdv(Rdv $rdv, ManagerRegistry $mr): Response
{
    $rdv->setStatut('Annulé');

    $mr->getManager()->flush();

    return $this->redirectToRoute('showAllRdvBack');
}

#[Route('/confirm/{id}', name: 'confirmRdv', methods: ['POST'])]
public function confirmRdv(Rdv $rdv, ManagerRegistry $mr): Response
{
    $rdv->setStatut('Confirmé');
    $mr->getManager()->flush();

    return $this->redirectToRoute('showAllRdvBack');
}



}
