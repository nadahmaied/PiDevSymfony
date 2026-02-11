<?php

namespace App\Controller;

use App\Entity\Donation;
use App\Entity\Annonce;
use App\Repository\AnnonceRepository;
use App\Form\DonationType;
use App\Repository\DonationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/donation')]
final class DonationController extends AbstractController
{
    #[Route(name: 'app_donation_index', methods: ['GET'])]
    public function index(DonationRepository $donationRepository, AnnonceRepository $annonceRepository): Response
    {
        return $this->render('donation/index.html.twig', [
            'donations' => $donationRepository->findAll(),
            'annonces' => $annonceRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_donation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $donation = new Donation();
        $form = $this->createForm(DonationType::class, $donation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($donation);
            $entityManager->flush();

            return $this->redirectToRoute('app_donation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('donation/new.html.twig', [
            'donation' => $donation,
            'form' => $form,
        ]);
    }

    #[Route('/new/annonce/{annonce}', name: 'app_donation_new_for_annonce', methods: ['GET', 'POST'])]
    public function newForAnnonce(Request $request, EntityManagerInterface $entityManager, Annonce $annonce): Response
    {
        $donation = new Donation();
        $donation->setAnnonce($annonce);
        $form = $this->createForm(DonationType::class, $donation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($donation);
            $entityManager->flush();

            return $this->redirectToRoute('app_donation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('donation/new.html.twig', [
            'donation' => $donation,
            'form' => $form,
            'annonce' => $annonce,
        ]);
    }

    #[Route('/{id}', name: 'app_donation_show', methods: ['GET'])]
    public function show(Donation $donation): Response
    {
        return $this->render('donation/show.html.twig', [
            'donation' => $donation,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_donation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Donation $donation, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(DonationType::class, $donation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_donation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('donation/edit.html.twig', [
            'donation' => $donation,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_donation_delete', methods: ['POST'])]
    public function delete(Request $request, Donation $donation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$donation->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($donation);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_donation_index', [], Response::HTTP_SEE_OTHER);
    }
}
