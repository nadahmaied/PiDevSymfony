<?php

namespace App\Controller;

use App\Entity\MissionVolunteer;
use App\Form\MissionType;
use App\Repository\MissionVolunteerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/admin/missions')]
class AdminMissionController extends AbstractController
{
    // 1. AFFICHER LA LISTE
    #[Route('/', name: 'app_admin_missions_index', methods: ['GET'])]
    public function index(
    MissionVolunteerRepository $missionRepository, 
    PaginatorInterface $paginator, 
    Request $request
): Response
{
    // 1. Récupérer le terme de recherche depuis l'URL (ex: ?q=medecin)
    $searchTerm = $request->query->get('q');

    // 2. Créer la requête via notre méthode du Repository
    $query = $missionRepository->findBySearchQuery($searchTerm);

    // 3. Paginer les résultats (10 par page)
    $pagination = $paginator->paginate(
        $query,
        $request->query->getInt('page', 1), // Numéro de page
        4 // Limite par page
    );

    return $this->render('admin_mission/index.html.twig', [
        'pagination' => $pagination, // On passe "pagination" au lieu de "missions"
        'searchTerm' => $searchTerm  // Pour garder le mot dans la barre de recherche
    ]);
}

    // 2. CRÉER (NOUVEAU)
    // 2. CRÉER (NOUVEAU) - VERSION DEBUG
    #[Route('/new', name: 'app_admin_missions_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $mission = new MissionVolunteer();
        
        // --- NOUVEAU : On relie la mission à l'admin connecté ---
        $mission->setUser($this->getUser());
        // --------------------------------------------------------

        $form = $this->createForm(MissionType::class, $mission);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            // GESTION DE L'UPLOAD PHOTO
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

                // On enregistre le nom du fichier dans l'entité
                $mission->setPhoto($newFilename);
            }

            $entityManager->persist($mission);
            $entityManager->flush();

            // Petit message de succès pour confirmer (optionnel mais sympa)
            $this->addFlash('success', 'Nouvelle mission créée avec succès !');

            return $this->redirectToRoute('app_admin_missions_index');
        }

        return $this->render('admin_mission/new.html.twig', [
            'mission' => $mission,
            'form' => $form,
        ]);
    }
    

    // 3. MODIFIER
    #[Route('/{id}/edit', name: 'app_admin_missions_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, MissionVolunteer $mission, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(MissionType::class, $mission);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            // --- GESTION DE L'IMAGE (Même logique que pour "new") ---
            $photoFile = $form->get('photo')->getData();

            // On ne traite l'image que si un NOUVEAU fichier a été envoyé
            if ($photoFile) {
                $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$photoFile->guessExtension();

                try {
                    $photoFile->move(
                        $this->getParameter('mission_images_directory'),
                        $newFilename
                    );
                    
                    // On met à jour le nom de l'image dans la base de données
                    $mission->setPhoto($newFilename);

                } catch (FileException $e) {
                    // Vous pouvez ajouter un message d'erreur ici si l'upload échoue
                }
            }
            // --------------------------------------------------------

            $entityManager->flush();

            return $this->redirectToRoute('app_admin_missions_index');
        }

        return $this->render('admin_mission/edit.html.twig', [
            'mission' => $mission,
            'form' => $form,
        ]);
    }

    // 4. SUPPRIMER
    #[Route('/{id}', name: 'app_admin_missions_delete', methods: ['POST'])]
    public function delete(Request $request, MissionVolunteer $mission, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$mission->getId(), $request->request->get('_token'))) {
            $entityManager->remove($mission);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_admin_missions_index');
    }
}
