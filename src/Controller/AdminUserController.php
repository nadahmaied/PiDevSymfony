<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Form\UserProfileType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class AdminUserController extends AbstractController
{
    private function canManageUser(User $target): bool
    {
        if ($target->getRole() === 'ROLE_SUPER_ADMIN' && !$this->isGranted('ROLE_SUPER_ADMIN')) {
            return false;
        }

        return true;
    }

    #[Route('/admin/users', name: 'admin_user_index')]
    public function index(Request $request, UserRepository $userRepository, PaginatorInterface $paginator): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $q = $request->query->get('q');
        if (!is_string($q) || $q === '') {
            $q = null;
        }
        $sort = (string) $request->query->get('sort', 'id');
        $direction = (string) $request->query->get('direction', 'ASC');

        $qb = $userRepository->createQueryBuilder('u');

        if ($q) {
            $qb
                ->andWhere('u.email LIKE :q OR u.nom LIKE :q OR u.prenom LIKE :q')
                ->setParameter('q', '%'.$q.'%');
        }

        $allowedSortFields = ['id', 'email', 'nom', 'prenom', 'role'];
        if (!in_array($sort, $allowedSortFields, true)) {
            $sort = 'id';
        }

        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $qb->orderBy('u.'.$sort, $direction);

        $pagination = $paginator->paginate(
            $qb,
            $request->query->getInt('page', 1),
            3
        );

        return $this->render('admin/user_index.html.twig', [
            'users' => $pagination,
            'q' => $q,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    #[Route('/admin/users/create', name: 'admin_user_create')]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                (string) $user->getPlainPassword()
            );
            $user->setPassword($hashedPassword);

            $selectedRole = (string) $form->get('role')->getData();
            $user->setRole($selectedRole);

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Utilisateur créé avec succès.');

            return $this->redirectToRoute('admin_user_index');
        }

        return $this->render('admin/user_create.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/admin/users/{id}/edit', name: 'admin_user_edit')]
    public function edit(User $user, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$this->canManageUser($user)) {
            $this->addFlash('error', 'Action interdite: seul un Super Admin peut modifier ce compte.');
            return $this->redirectToRoute('admin_user_index');
        }

        $form = $this->createForm(UserProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $profilePictureFile = $form->get('profilePictureFile')->getData();

            if ($profilePictureFile) {
                $newFilename = uniqid().'.'.$profilePictureFile->guessExtension();

                try {
                    $projectDirParam = $this->getParameter('kernel.project_dir');
                    if (!is_string($projectDirParam)) {
                        throw new \RuntimeException('Invalid kernel.project_dir parameter.');
                    }
                    $projectDir = $projectDirParam;
                    $profilePictureFile->move(
                        $projectDir . '/public/uploads/profiles',
                        $newFilename
                    );
                    $user->setProfilePicture($newFilename);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors du téléchargement de l\'image.');
                }
            }

            $entityManager->flush();

            $this->addFlash('success', 'Utilisateur mis à jour avec succès.');

            return $this->redirectToRoute('admin_user_index');
        }

        return $this->render('admin/user_edit.html.twig', [
            'user' => $user,
            'profileForm' => $form->createView(),
        ]);
    }

    #[Route('/admin/users/{id}/delete', name: 'admin_user_delete', methods: ['POST'])]
    public function delete(User $user, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$this->canManageUser($user)) {
            $this->addFlash('error', 'Action interdite: seul un Super Admin peut supprimer ce compte.');
            return $this->redirectToRoute('admin_user_index');
        }

        if (!$this->isCsrfTokenValid('delete_user_'.$user->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');

            return $this->redirectToRoute('admin_user_index');
        }

        $entityManager->remove($user);
        $entityManager->flush();

        $this->addFlash('success', 'Utilisateur supprimé avec succès.');

        return $this->redirectToRoute('admin_user_index');
    }
    
    #[Route('/admin/verification/pending', name: 'admin_verification_pending')]
    public function pendingVerifications(UserRepository $userRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $pendingUsers = $userRepository->findBy([
            'role' => 'ROLE_MEDECIN',
            'verificationStatus' => 'pending_review'
        ]);

        return $this->render('admin/verification_pending.html.twig', [
            'pendingUsers' => $pendingUsers,
        ]);
    }

    #[Route('/admin/verification/{id}/approve', name: 'admin_verification_approve', methods: ['POST'])]
    public function approveVerification(
        User $user, 
        Request $request, 
        EntityManagerInterface $entityManager,
        MailerInterface $mailer
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$this->isCsrfTokenValid('approve_verification_'.$user->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('admin_verification_pending');
        }

        $user->setIsVerified(true);
        $user->setVerificationStatus('verified');
        $entityManager->flush();

        // Send approval email
        try {
            $email = (new TemplatedEmail())
                ->from(new Address('wassimbac12@gmail.com', 'VitalTech'))
                ->to($user->getEmail())
                ->subject('Votre compte médecin a été approuvé')
                ->htmlTemplate('emails/account_approved.html.twig')
                ->context([
                    'user' => $user,
                ]);

            $mailer->send($email);
        } catch (\Exception $e) {
            error_log('Failed to send approval email: ' . $e->getMessage());
        }

        $this->addFlash('success', 'Médecin vérifié avec succès. Un email de confirmation a été envoyé.');

        return $this->redirectToRoute('admin_verification_pending');
    }

    #[Route('/admin/verification/{id}/reject', name: 'admin_verification_reject', methods: ['POST'])]
    public function rejectVerification(User $user, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$this->isCsrfTokenValid('reject_verification_'.$user->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('admin_verification_pending');
        }

        $user->setVerificationStatus('rejected');
        $entityManager->flush();

        $this->addFlash('success', 'Vérification rejetée.');

        return $this->redirectToRoute('admin_verification_pending');
    }
}

