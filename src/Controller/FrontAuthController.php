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

class FrontAuthController extends AbstractController
{
    #[Route('/signup', name: 'front_register')]
    public function register(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        // 👇 CORRECTION ICI : Si on est déjà connecté, on va vers l'accueil
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // On crypte le mot de passe
            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                (string) $form->get('plainPassword')->getData() // Petite sécurité supplémentaire ici
            );
            $user->setPassword($hashedPassword);
            
            // On force le rôle USER par défaut
            $user->setRole('ROLE_USER');

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Votre compte a été créé. Vous pouvez vous connecter.');

            return $this->redirectToRoute('front_login');
        }

        return $this->render('security/front_register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/login', name: 'front_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // 👇 CORRECTION ICI : Si on est déjà connecté, on va vers l'accueil
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/front_login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'front_logout')]
    public function logout(): void
    {
        // Intercepted by Symfony Security
        throw new \LogicException('Intercepted by Symfony Security.');
    }
}