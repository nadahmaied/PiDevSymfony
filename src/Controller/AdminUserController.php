<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class AdminUserController extends AbstractController
{
    #[Route('/admin/users', name: 'admin_user_index')]
    public function index(Request $request, UserRepository $userRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $q = $request->query->get('q');
        $sort = $request->query->get('sort', 'id');
        $direction = $request->query->get('direction', 'ASC');

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

        $users = $qb->getQuery()->getResult();

        return $this->render('admin/user_index.html.twig', [
            'users' => $users,
            'q' => $q,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }
}

