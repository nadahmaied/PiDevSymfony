<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class AdminAuthController extends AbstractController
{
    #[Route('/admin/signup', name: 'admin_register')]
    public function register(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                (string) $user->getPlainPassword()
            );
            $user->setPassword($hashedPassword);
            $user->setRole('ROLE_ADMIN');

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Compte administrateur créé. Vous pouvez vous connecter.');

            return $this->redirectToRoute('admin_login');
        }

        return $this->render('security/admin_register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/admin/login', name: 'admin_login')]
    public function login(): Response
    {
        // Use the unified login page for both clients and admins
        return $this->redirectToRoute('front_login');
    }

    #[Route('/admin/logout', name: 'admin_logout')]
    public function logout(): void
    {
        // Intercepted by Symfony Security
        throw new \LogicException('Intercepted by Symfony Security.');
    }
}

