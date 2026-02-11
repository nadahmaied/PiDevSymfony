<?php

namespace App\Controller;

use App\Entity\Question;
use App\Entity\Reponse;
use App\Entity\User;
use App\Form\QuestionType;
use App\Form\ReponseType;
use App\Repository\QuestionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/forum')]
class ForumController extends AbstractController
{
    // 1. LISTE DES SUJETS
    #[Route('/', name: 'app_forum_index', methods: ['GET'])]
    public function index(QuestionRepository $questionRepository, PaginatorInterface $paginator, Request $request): Response
    {
        // 1. Récupération des paramètres (Recherche & Filtre)
        $searchTerm = $request->query->get('q');
        $filter = $request->query->get('filter', 'recent'); // par défaut 'recent'

        // 2. Construction de la requête de base
        $qb = $questionRepository->createQueryBuilder('q')
            ->leftJoin('q.auteur', 'u')
            ->leftJoin('q.reponses', 'r')
            ->addSelect('u', 'r'); // Optimisation (évite trop de requêtes SQL)

        // 3. Gestion de la Recherche
        if ($searchTerm) {
            $qb->andWhere('q.titre LIKE :search OR q.contenu LIKE :search')
               ->setParameter('search', '%' . $searchTerm . '%');
        }

        // 4. Gestion des Filtres (Onglets)
        switch ($filter) {
            case 'popular':
                // Trie par nombre de réponses (le plus commenté en premier)
                $qb->orderBy('SIZE(q.reponses)', 'DESC');
                break;
            case 'unanswered':
                // Filtre uniquement ceux qui ont 0 réponse
                $qb->andWhere('SIZE(q.reponses) = 0')
                   ->orderBy('q.dateCreation', 'DESC');
                break;
            case 'recent':
            default:
                // Par défaut : du plus récent au plus ancien
                $qb->orderBy('q.dateCreation', 'DESC');
                break;
        }

        // 5. Pagination (6 questions par page pour que ce soit joli)
        $pagination = $paginator->paginate(
            $qb->getQuery(),
            $request->query->getInt('page', 1),
            6 
        );

        return $this->render('forum/index.html.twig', [
            'pagination' => $pagination,
            'searchTerm' => $searchTerm,
            'currentFilter' => $filter
        ]);
    }

    // 2. CRÉER UN NOUVEAU SUJET
    #[Route('/nouveau', name: 'app_forum_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $question = new Question();
        $form = $this->createForm(QuestionType::class, $question);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // --- FIX USER (Simulation) ---
            $user = $entityManager->getRepository(User::class)->findOneBy([]); 
            $question->setAuteur($user); 
            // -----------------------------
            
            $question->setDateCreation(new \DateTimeImmutable());
            
            $entityManager->persist($question);
            $entityManager->flush();

            $this->addFlash('success', 'Votre sujet a été publié !');
            return $this->redirectToRoute('app_forum_index');
        }

        return $this->render('forum/new.html.twig', [
            'form' => $form,
        ]);
    }

    // 3. VOIR LE DÉTAIL + RÉPONDRE
    #[Route('/sujet/{id}', name: 'app_forum_show', methods: ['GET', 'POST'])]
    public function show(Request $request, Question $question, EntityManagerInterface $entityManager): Response
    {
        // Traitement du formulaire de réponse (sur la même page)
        $reponse = new Reponse();
        $form = $this->createForm(ReponseType::class, $reponse);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // --- FIX USER ---
            $user = $entityManager->getRepository(User::class)->findOneBy([]); 
            $reponse->setAuteur($user);
            // ----------------
            
            $reponse->setDateCreation(new \DateTimeImmutable());
            $reponse->setQuestion($question);

            $entityManager->persist($reponse);
            $entityManager->flush();

            $this->addFlash('success', 'Votre réponse a été ajoutée !');
            return $this->redirectToRoute('app_forum_show', ['id' => $question->getId()]);
        }

        return $this->render('forum/show.html.twig', [
            'question' => $question,
            'form' => $form,
        ]);
    }

    // 4. MODIFIER UN SUJET
    #[Route('/sujet/{id}/edit', name: 'app_forum_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Question $question, EntityManagerInterface $entityManager): Response
    {
        // Ici, on devrait vérifier si $this->getUser() == $question->getAuteur()
        
        $form = $this->createForm(QuestionType::class, $question);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Sujet modifié avec succès.');
            return $this->redirectToRoute('app_forum_show', ['id' => $question->getId()]);
        }

        return $this->render('forum/edit.html.twig', [
            'question' => $question,
            'form' => $form,
        ]);
    }

    // 5. SUPPRIMER UN SUJET
    #[Route('/sujet/{id}/delete', name: 'app_forum_delete', methods: ['POST'])]
    public function delete(Request $request, Question $question, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$question->getId(), $request->request->get('_token'))) {
            $entityManager->remove($question);
            $entityManager->flush();
            $this->addFlash('success', 'Sujet supprimé.');
        }
        return $this->redirectToRoute('app_forum_index');
    }

    // 6. SUPPRIMER UNE REPONSE
    #[Route('/reponse/{id}/delete', name: 'app_forum_delete_reponse', methods: ['POST'])]
    public function deleteReponse(Request $request, Reponse $reponse, EntityManagerInterface $entityManager): Response
    {
        $questionId = $reponse->getQuestion()->getId();
        if ($this->isCsrfTokenValid('delete'.$reponse->getId(), $request->request->get('_token'))) {
            $entityManager->remove($reponse);
            $entityManager->flush();
            $this->addFlash('success', 'Réponse supprimée.');
        }
        return $this->redirectToRoute('app_forum_show', ['id' => $questionId]);
    }
}