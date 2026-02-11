<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserProfileType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class AccountController extends AbstractController
{
    #[Route('/account', name: 'account_show')]
    public function show(): Response
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('account/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/account/edit', name: 'account_edit')]
    public function edit(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(UserProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Vos informations ont été mises à jour.');

            return $this->redirectToRoute('account_show');
        }

        return $this->render('account/edit.html.twig', [
            'profileForm' => $form->createView(),
        ]);
    }

    #[Route('/account/delete', name: 'account_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        EntityManagerInterface $entityManager,
        TokenStorageInterface $tokenStorage
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        if (!$this->isCsrfTokenValid('delete_account', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');

            return $this->redirectToRoute('account_show');
        }

        $entityManager->remove($user);
        $entityManager->flush();

        $tokenStorage->setToken(null);
        $request->getSession()->invalidate();

        $this->addFlash('success', 'Votre compte a été supprimé.');

        return $this->redirectToRoute('app_login');
    }
}

