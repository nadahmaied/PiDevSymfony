<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/super-admin')]
class SuperAdminController extends AbstractController
{
    #[Route('/users', name: 'app_super_admin_users', methods: ['GET'])]
    public function users(Request $request, UserRepository $userRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        $q = trim((string) $request->query->get('q', ''));
        $qb = $userRepository->createQueryBuilder('u')->orderBy('u.id', 'DESC');

        if ($q !== '') {
            $qb->andWhere('u.email LIKE :q OR u.nom LIKE :q OR u.prenom LIKE :q')
                ->setParameter('q', '%' . $q . '%');
        }

        return $this->render('super_admin/users.html.twig', [
            'users' => $qb->getQuery()->getResult(),
            'q' => $q,
        ]);
    }

    #[Route('/users/{id}/set-role', name: 'app_super_admin_set_role', methods: ['POST'])]
    public function setRole(User $target, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        if (!$this->isCsrfTokenValid('super_admin_role_' . $target->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        $requestedRole = (string) $request->request->get('role', '');
        $allowedRoles = ['ROLE_USER', 'ROLE_ADMIN'];
        if (!in_array($requestedRole, $allowedRoles, true)) {
            $this->addFlash('warning', 'Role non autorise.');
            return $this->redirectToRoute('app_super_admin_users');
        }

        $currentUser = $this->getUser();
        if ($currentUser instanceof User && $currentUser->getId() === $target->getId()) {
            $this->addFlash('warning', 'Vous ne pouvez pas changer votre propre role.');
            return $this->redirectToRoute('app_super_admin_users');
        }

        if ($target->getRole() === 'ROLE_SUPER_ADMIN') {
            $this->addFlash('warning', 'Modification bloquee: ce compte est Super Admin.');
            return $this->redirectToRoute('app_super_admin_users');
        }

        $target->setRole($requestedRole);
        $entityManager->flush();

        $this->addFlash('success', sprintf('Role mis a jour pour %s: %s', $target->getEmail(), $requestedRole));
        return $this->redirectToRoute('app_super_admin_users');
    }
}

