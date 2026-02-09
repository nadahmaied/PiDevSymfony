<?php

namespace App\Controller;

use App\Entity\MissionVolunteer;
use App\Form\MissionType;
use App\Repository\MissionVolunteerRepository;
use App\Repository\UserRepository;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

#[Route('/espace-association/missions')]
class AssociationMissionController extends AbstractController
{
    // --- FONCTION UTILITAIRE POUR LE MODE TEST ---
    // Permet de récupérer un utilisateur même si le login ne marche pas encore
    private function getTestUser(UserRepository $userRepository): User
    {
        $user = $this->getUser();
        
        if (!$user) {
            $user = $userRepository->findOneBy([]);
            if (!$user) {
                throw $this->createNotFoundException("ERREUR : Aucun utilisateur trouvé dans la base de données (table 'user'). Créez-en un via phpMyAdmin !");
            }
        }
        return $user;
    }

    // 1. TABLEAU DE BORD (Liste des missions de l'association)
    #[Route('/', name: 'app_assoc_mission_index', methods: ['GET'])]
    public function index(MissionVolunteerRepository $missionRepository, UserRepository $userRepository): Response
    {
        $user = $this->getTestUser($userRepository);

        return $this->render('association_mission/index.html.twig', [
            // On filtre pour n'afficher QUE les missions de cet utilisateur
            'missions' => $missionRepository->findBy(['user' => $user]),
        ]);
    }

    // 2. CRÉER UNE NOUVELLE MISSION
    #[Route('/new', name: 'app_assoc_mission_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger, UserRepository $userRepository): Response
    {
        $mission = new MissionVolunteer();
        $user = $this->getTestUser($userRepository);
        
        $mission->setUser($user); // On lie la mission à l'association
        $mission->setStatut('Ouverte'); // Statut par défaut

        $form = $this->createForm(MissionType::class, $mission);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            // --- GESTION UPLOAD IMAGE ---
            $photoFile = $form->get('photo')->getData();
            if ($photoFile) {
                $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$photoFile->guessExtension();

                try {
                    $photoFile->move(
                        $this->getParameter('mission_images_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // Gérer l'erreur si besoin
                }
                $mission->setPhoto($newFilename);
            }
            // -----------------------------

            $entityManager->persist($mission);
            $entityManager->flush();

            $this->addFlash('success', 'Votre mission a été publiée avec succès !');
            return $this->redirectToRoute('app_assoc_mission_index');
        }

        return $this->render('association_mission/new.html.twig', [
            'mission' => $mission,
            'form' => $form,
        ]);
    }

    // 3. MODIFIER UNE MISSION
    #[Route('/{id}/edit', name: 'app_assoc_mission_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, MissionVolunteer $mission, EntityManagerInterface $entityManager, SluggerInterface $slugger, UserRepository $userRepository): Response
    {
        $user = $this->getTestUser($userRepository);

        // SÉCURITÉ : Vérifier que la mission appartient bien à l'utilisateur
        if ($mission->getUser()->getId() !== $user->getId()) {
             throw $this->createAccessDeniedException("Vous ne pouvez pas modifier la mission d'une autre association !");
        }

        $form = $this->createForm(MissionType::class, $mission);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
             
             // --- GESTION UPLOAD IMAGE (Modification) ---
             $photoFile = $form->get('photo')->getData();
             if ($photoFile) {
                 $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                 $safeFilename = $slugger->slug($originalFilename);
                 $newFilename = $safeFilename.'-'.uniqid().'.'.$photoFile->guessExtension();
                 try {
                     $photoFile->move($this->getParameter('mission_images_directory'), $newFilename);
                 } catch (FileException $e) {}
                 
                 $mission->setPhoto($newFilename);
             }
             // -------------------------------------------

            $entityManager->flush();
            $this->addFlash('success', 'Mission mise à jour.');
            return $this->redirectToRoute('app_assoc_mission_index');
        }

        return $this->render('association_mission/edit.html.twig', [
            'mission' => $mission,
            'form' => $form,
        ]);
    }

    // 4. SUPPRIMER UNE MISSION
    #[Route('/{id}', name: 'app_assoc_mission_delete', methods: ['POST'])]
    public function delete(Request $request, MissionVolunteer $mission, EntityManagerInterface $entityManager, UserRepository $userRepository): Response
    {
        $user = $this->getTestUser($userRepository);

        // SÉCURITÉ
        if ($mission->getUser()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException("Interdit !");
        }

        if ($this->isCsrfTokenValid('delete'.$mission->getId(), $request->request->get('_token'))) {
            $entityManager->remove($mission);
            $entityManager->flush();
            $this->addFlash('success', 'Mission supprimée.');
        }

        return $this->redirectToRoute('app_assoc_mission_index');
    }
}