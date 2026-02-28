<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Volunteer;
use App\Form\VolunteerType;
use App\Repository\VolunteerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/mon-espace/candidatures')]
class FrontVolunteerController extends AbstractController
{
    #[Route('/', name: 'app_front_volunteer_index', methods: ['GET'])]
    public function index(VolunteerRepository $volunteerRepository): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_front_login');
        }

        return $this->render('front_volunteer/index.html.twig', [
            'volunteers' => $volunteerRepository->findBy(['user' => $user]),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_front_volunteer_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Volunteer $volunteer, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_front_login');
        }

        if ($volunteer->getUser()?->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException("Vous ne pouvez pas modifier la candidature d'un autre utilisateur !");
        }

        if (mb_strtolower((string) $volunteer->getStatut()) !== 'en attente') {
            $this->addFlash('warning', 'Cette candidature ne peut plus etre modifiee.');
            return $this->redirectToRoute('app_front_volunteer_index');
        }

        $form = $this->createForm(VolunteerType::class, $volunteer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Candidature modifiee avec succes !');

            return $this->redirectToRoute('app_front_volunteer_index');
        }

        return $this->render('front_volunteer/edit.html.twig', [
            'volunteer' => $volunteer,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_front_volunteer_delete', methods: ['POST'])]
    public function delete(Request $request, Volunteer $volunteer, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_front_login');
        }

        if ($volunteer->getUser()?->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('Interdit !');
        }

        if (mb_strtolower((string) $volunteer->getStatut()) !== 'en attente') {
            $this->addFlash('warning', 'Cette candidature ne peut plus etre annulee.');
            return $this->redirectToRoute('app_front_volunteer_index');
        }

        if ($this->isCsrfTokenValid('delete' . $volunteer->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($volunteer);
            $entityManager->flush();
            $this->addFlash('success', 'Candidature annulee.');
        }

        return $this->redirectToRoute('app_front_volunteer_index');
    }
}
