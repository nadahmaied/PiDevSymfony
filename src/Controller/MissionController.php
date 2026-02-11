<?php

namespace App\Controller;

use App\Entity\MissionVolunteer;
use App\Entity\Volunteer;
use App\Form\VolunteerType;
use App\Repository\MissionVolunteerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\User;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/missions')]
class MissionController extends AbstractController
{
    /**
     * 1. AFFICHER LA LISTE DES MISSIONS (Page avec les cartes)
     */
    #[Route('/', name: 'app_missions_index', methods: ['GET'])]
    public function index(MissionVolunteerRepository $missionRepository, PaginatorInterface $paginator, Request $request): Response
    {
        // 1. On récupère le terme de recherche s'il existe (depuis la barre de recherche)
        $searchTerm = $request->query->get('q');

        // 2. On prépare la requête (QueryBuilder)
        $qb = $missionRepository->createQueryBuilder('m')
            ->where('m.statut = :statut')
            ->setParameter('statut', 'Ouverte')
            ->orderBy('m.dateDebut', 'DESC');

        // 3. Si une recherche est faite, on filtre les résultats
        if ($searchTerm) {
            $qb->andWhere('m.titre LIKE :search OR m.description LIKE :search OR m.lieu LIKE :search')
               ->setParameter('search', '%' . $searchTerm . '%');
        }

        // 4. On active la pagination (6 missions par page)
        $pagination = $paginator->paginate(
            $qb->getQuery(), /* La requête */
            $request->query->getInt('page', 1), /* Numéro de page actuel */
            6 /* Limite par page */
        );

        // 5. On envoie "pagination" à la vue (C'est ce que votre Twig attend !)
        return $this->render('mission/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    /**
     * 2. GÉRER LE FORMULAIRE DE CANDIDATURE (Page Orange)
     */
    #[Route('/{id}/postuler', name: 'app_missions_apply', methods: ['GET', 'POST'])]
    public function apply(Request $request, MissionVolunteer $mission, EntityManagerInterface $entityManager): Response
    {
        $volunteer = new Volunteer();
        
        // 2. RÉCUPÉRATION INTELLIGENTE DE L'UTILISATEUR
        $user = $this->getUser(); // Essaie de récupérer l'utilisateur connecté

        // Si personne n'est connecté (votre cas actuel), on prend le premier user de la base "au hasard"
        if (!$user) {
            // On cherche le premier utilisateur dans la table 'user'
            $user = $entityManager->getRepository(User::class)->findOneBy([]);
            
            // Si la table user est vide, on arrête tout car ça plantera la base de données
            if (!$user) {
                dd("ERREUR : Il faut créer au moins un utilisateur dans la base de données (table 'user') pour tester, même manuellement via phpMyAdmin.");
            }
        }

        $volunteer->setMission($mission);
        $volunteer->setUser($user); // On utilise l'utilisateur (réel ou simulé)
        $volunteer->setStatut('En attente');

        // ... Le reste du code ne change pas ...
        $form = $this->createForm(VolunteerType::class, $volunteer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($volunteer);
            $entityManager->flush();
            $this->addFlash('success', 'Candidature envoyée (Mode Test) !');
            return $this->redirectToRoute('app_missions_index');
        }

        return $this->render('mission/apply.html.twig', [
            'mission' => $mission,
            'form' => $form,
        ]);
    }
    #[Route('/{id}', name: 'app_mission_show', methods: ['GET'])]
    public function show(MissionVolunteer $mission): Response
    {
        return $this->render('mission/show.html.twig', [
            'mission' => $mission,
        ]);
    }
}