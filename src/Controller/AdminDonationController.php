<?php

namespace App\Controller;

use App\Entity\Donation;
use App\Entity\Annonce;
use App\Form\DonationType;
use App\Repository\DonationRepository;
use App\Repository\AnnonceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/donation')]
final class AdminDonationController extends AbstractController
{
    #[Route(name: 'admin_donation_index', methods: ['GET'])]
    public function index(DonationRepository $donationRepository, AnnonceRepository $annonceRepository): Response
    {
        return $this->render('admin/donation/index.html.twig', [
            'donations' => $donationRepository->findAll(),
            'annonces' => $annonceRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'admin_donation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $donation = new Donation();
        $form = $this->createForm(DonationType::class, $donation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($donation);
            $entityManager->flush();

            return $this->redirectToRoute('admin_donation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/donation/new.html.twig', [
            'donation' => $donation,
            'form' => $form,
        ]);
    }

    #[Route('/new/annonce/{annonce}', name: 'admin_donation_new_for_annonce', methods: ['GET', 'POST'])]
    public function newForAnnonce(Request $request, EntityManagerInterface $entityManager, Annonce $annonce): Response
    {
        $donation = new Donation();
        $donation->setAnnonce($annonce);
        $form = $this->createForm(DonationType::class, $donation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($donation);
            $entityManager->flush();

            return $this->redirectToRoute('admin_donation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/donation/new.html.twig', [
            'donation' => $donation,
            'form' => $form,
            'annonce' => $annonce,
        ]);
    }

    #[Route('/{id}', name: 'admin_donation_show', methods: ['GET'])]
    public function show(Donation $donation): Response
    {
        return $this->render('admin/donation/show.html.twig', [
            'donation' => $donation,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_donation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Donation $donation, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(DonationType::class, $donation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('admin_donation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/donation/edit.html.twig', [
            'donation' => $donation,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_donation_delete', methods: ['POST'])]
    public function delete(Request $request, Donation $donation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$donation->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($donation);
            $entityManager->flush();
        }

        return $this->redirectToRoute('admin_donation_index', [], Response::HTTP_SEE_OTHER);
    }
}

