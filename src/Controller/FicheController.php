<?php

namespace App\Controller;

use App\Entity\Fiche;
use App\Form\FicheType;
use App\Repository\FicheRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/fiche')]
class FicheController extends AbstractController
{
    #[Route('/', name: 'app_fiche_index', methods: ['GET'])]
    public function index(Request $request, FicheRepository $ficheRepository): Response
    {
        $search = $request->query->get('search');
        $sortBy = $request->query->get('sortBy');
        $sortOrder = $request->query->get('sortOrder', 'ASC');

        return $this->render('fiche/index.html.twig', [
            'fiches' => $ficheRepository->findBySearchAndSort($search, $sortBy, $sortOrder),
            'search' => $search,
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder,
        ]);
    }

    #[Route('/new', name: 'app_fiche_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $fiche = new Fiche();
        $form = $this->createForm(FicheType::class, $fiche);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($fiche);
            $entityManager->flush();
            $this->addFlash('success', 'Fiche médicale créée avec succès !');

            return $this->redirectToRoute('app_fiche_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('fiche/new.html.twig', [
            'fiche' => $fiche,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_fiche_show', methods: ['GET'])]
    public function show(Fiche $fiche): Response
    {
        return $this->render('fiche/show.html.twig', [
            'fiche' => $fiche,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_fiche_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Fiche $fiche, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(FicheType::class, $fiche);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Fiche médicale mise à jour avec succès !');

            return $this->redirectToRoute('app_fiche_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('fiche/edit.html.twig', [
            'fiche' => $fiche,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_fiche_delete', methods: ['POST'])]
    public function delete(Request $request, Fiche $fiche, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$fiche->getId(), $request->request->get('_token'))) {
            $entityManager->remove($fiche);
            $entityManager->flush();
            $this->addFlash('success', 'Fiche médicale supprimée avec succès !');
        }

        return $this->redirectToRoute('app_fiche_index', [], Response::HTTP_SEE_OTHER);
    }
}
