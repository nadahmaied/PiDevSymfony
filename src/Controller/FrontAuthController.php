<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\FrontRegistrationFormType;
use App\Repository\UserRepository;
use App\Service\DocumentVerificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class FrontAuthController extends AbstractController
{
    #[Route('/signup', name: 'front_register')]
    public function register(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        DocumentVerificationService $verificationService,
        UserRepository $userRepository,
        MailerInterface $mailer
    ): Response {
        // 👇 CORRECTION ICI : Si on est déjà connecté, on va vers l'accueil
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $user = new User();
        $form = $this->createForm(FrontRegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // On crypte le mot de passe
            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                (string) $form->get('plainPassword')->getData() // Petite sécurité supplémentaire ici
            );
            $user->setPassword($hashedPassword);

            $selectedRole = (string) $form->get('role')->getData();
            $user->setRole($selectedRole);

            // Handle document verification for doctors
            if ($selectedRole === 'ROLE_MEDECIN') {
                $diplomaFile = $form->get('diplomaFile')->getData();

                if (!$diplomaFile) {
                    $this->addFlash('error', 'Les médecins doivent soumettre leur diplôme médical.');
                    return $this->render('security/front_register.html.twig', [
                        'registrationForm' => $form->createView(),
                    ]);
                }

                // Save document
                $diplomaFilename = uniqid() . '.' . $diplomaFile->guessExtension();

                try {
                    $projectDirParam = $this->getParameter('kernel.project_dir');
                    if (!is_string($projectDirParam)) {
                        throw new \RuntimeException('Invalid kernel.project_dir parameter.');
                    }
                    $projectDir = $projectDirParam;
                    $uploadsDir = $projectDir . '/public/uploads/documents';
                    if (!is_dir($uploadsDir)) {
                        mkdir($uploadsDir, 0777, true);
                    }

                    $diplomaFile->move($uploadsDir, $diplomaFilename);
                    $user->setDiplomaDocument($diplomaFilename);

                    // Save diploma for manual admin verification
                    // AI verification is unreliable due to API limitations
                    $user->setIsVerified(false);
                    $user->setVerificationStatus('pending_review');

                    $this->addFlash('info', 'Votre diplôme a été soumis avec succès. Un administrateur vérifiera votre compte sous peu. Vous recevrez une notification une fois approuvé.');
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors du téléchargement du document.');
                    return $this->render('security/front_register.html.twig', [
                        'registrationForm' => $form->createView(),
                    ]);
                }
            } else {
                // Patients are automatically verified
                $user->setIsVerified(true);
                $user->setVerificationStatus('verified');
            }

            $entityManager->persist($user);
            $entityManager->flush();

            if ($selectedRole === 'ROLE_MEDECIN') {
                $admins = $userRepository->createQueryBuilder('u')
                    ->where('u.role IN (:roles)')
                    ->setParameter('roles', ['ROLE_ADMIN', 'ROLE_SUPER_ADMIN'])
                    ->getQuery()
                    ->getResult();

                foreach ($admins as $admin) {
                    if (!$admin instanceof User || !$admin->getEmail()) {
                        continue;
                    }

                    try {
                        $email = (new TemplatedEmail())
                            ->from(new Address('wassimbac12@gmail.com', 'VitalTech'))
                            ->to($admin->getEmail())
                            ->subject('Nouveau médecin en attente de validation')
                            ->htmlTemplate('emails/doctor_signup_pending_admin.html.twig')
                            ->context([
                                'doctor' => $user,
                                'admin' => $admin,
                            ]);

                        $mailer->send($email);
                    } catch (\Exception $e) {
                        error_log('Failed to send doctor signup admin notification: ' . $e->getMessage());
                    }
                }
            }

            return $this->redirectToRoute('front_login');
        }

        return $this->render('security/front_register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/login', name: 'front_login')]
    public function login(Request $request, AuthenticationUtils $authenticationUtils): Response
    {
        // 👇 CORRECTION ICI : Si on est déjà connecté, on va vers l'accueil
        if ($this->getUser()) {
            if ($this->isGranted('ROLE_ADMIN')) {
                return $this->redirectToRoute('admin_user_index');
            }
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
