<?php

namespace App\Controller;

use App\Entity\User; // Import indispensable
use App\Entity\Volunteer;
use App\Form\VolunteerType;
use App\Repository\UserRepository; // Import indispensable
use App\Repository\VolunteerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/mon-espace/candidatures')]
class FrontVolunteerController extends AbstractController
{
    // Fonction utilitaire pour récupérer le premier user de la base (MODE TEST)
    private function getTestUser(UserRepository $userRepository): ?User
    {
        $user = $userRepository->findOneBy([]);
        if (!$user) {
            // Si la table user est vide, on arrête tout
            throw $this->createNotFoundException('Aucun utilisateur trouvé dans la base de données. Créez-en un d\'abord !');
        }
        return $user;
    }

    #[Route('/', name: 'app_front_volunteer_index', methods: ['GET'])]
    public function index(VolunteerRepository $volunteerRepository, UserRepository $userRepository): Response
    {
        // 1. On récupère l'utilisateur de test au lieu de $this->getUser()
        $user = $this->getTestUser($userRepository);

        return $this->render('front_volunteer/index.html.twig', [
            // 2. On cherche les candidatures de cet utilisateur précis
            'volunteers' => $volunteerRepository->findBy(['user' => $user]),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_front_volunteer_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Volunteer $volunteer, EntityManagerInterface $entityManager, UserRepository $userRepository): Response
    {
        // 1. On récupère l'utilisateur de test
        $user = $this->getTestUser($userRepository);

        // 2. Vérification : Est-ce que cette candidature appartient bien à notre utilisateur de test ?
        // On compare les ID pour être sûr
        if ($volunteer->getUser()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException("Vous ne pouvez pas modifier la candidature d'un autre utilisateur !");
        }

        $form = $this->createForm(VolunteerType::class, $volunteer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Candidature modifiée avec succès !');
            return $this->redirectToRoute('app_front_volunteer_index');
        }

        return $this->render('front_volunteer/edit.html.twig', [
            'volunteer' => $volunteer,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_front_volunteer_delete', methods: ['POST'])]
    public function delete(Request $request, Volunteer $volunteer, EntityManagerInterface $entityManager, UserRepository $userRepository): Response
    {
        // 1. On récupère l'utilisateur de test
        $user = $this->getTestUser($userRepository);

        // 2. Vérification de sécurité (avec l'user de test)
        if ($volunteer->getUser()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException("Interdit !");
        }

        if ($this->isCsrfTokenValid('delete'.$volunteer->getId(), $request->request->get('_token'))) {
            $entityManager->remove($volunteer);
            $entityManager->flush();
            $this->addFlash('success', 'Candidature annulée.');
        }

        return $this->redirectToRoute('app_front_volunteer_index');
    }
}