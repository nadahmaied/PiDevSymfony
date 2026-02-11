
*^=$pgyta78



/
uoètsdfg9*/<?php

namespace App\Controller;

use App\Entity\Question;
use App\Entity\Reponse;
use App\Form\QuestionType;
use App\Repository\QuestionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\User;
use App\Form\ReponseType;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/admin/forum')]
class AdminForumController extends AbstractController
{
    // 1. LISTE (INDEX)
    #[Route('/', name: 'app_admin_forum_index', methods: ['GET'])]
    public function index(QuestionRepository $questionRepository, PaginatorInterface $paginator, Request $request): Response
    {
        // 1. Récupérer le terme de recherche
        $searchTerm = $request->query->get('q');

        // 2. Construire la requête (QueryBuilder)
        $qb = $questionRepository->createQueryBuilder('q')
            ->leftJoin('q.auteur', 'u') // On joint l'utilisateur pour chercher par email
            ->addSelect('u');

        // 3. Recherche (Titre, Contenu ou Email auteur)
        if ($searchTerm) {
            $qb->andWhere('q.titre LIKE :search OR q.contenu LIKE :search OR u.email LIKE :search')
               ->setParameter('search', '%' . $searchTerm . '%');
        }

        // 4. Tri par défaut (Si on ne clique pas sur les colonnes, on trie par date récente)
        if (!$request->query->get('sort')) {
            $qb->orderBy('q.dateCreation', 'DESC');
        }

        // 5. Pagination (10 questions par page)
        $pagination = $paginator->paginate(
            $qb->getQuery(),
            $request->query->getInt('page', 1),
            5 
        );

        // On envoie 'pagination' à la vue au lieu de 'questions'
        return $this->render('admin_forum/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    // 2. AJOUTER UN SUJET (NEW)
    #[Route('/new', name: 'app_admin_forum_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $question = new Question();
        $form = $this->createForm(QuestionType::class, $question);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            // --- CORRECTION PROVISOIRE ---
            // Au lieu de $this->getUser(), on récupère le premier utilisateur de la base
            $user = $entityManager->getRepository(User::class)->findOneBy([]);
            
            if (!$user) {
                throw new \Exception('Erreur : Aucun utilisateur trouvé dans la table "user". Ajoutez-en un via phpMyAdmin !');
            }
            
            $question->setAuteur($user);
            // -----------------------------

            $question->setDateCreation(new \DateTimeImmutable());

            $entityManager->persist($question);
            $entityManager->flush();

            $this->addFlash('success', 'Sujet créé avec succès !');
            return $this->redirectToRoute('app_admin_forum_index');
        }

        return $this->render('admin_forum/new.html.twig', [
            'question' => $question,
            'form' => $form,
        ]);
    }

    // 3. MODIFIER UN SUJET (EDIT)
    #[Route('/{id}/edit', name: 'app_admin_forum_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Question $question, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(QuestionType::class, $question);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Sujet modifié avec succès !');
            return $this->redirectToRoute('app_admin_forum_index');
        }

        return $this->render('admin_forum/edit.html.twig', [
            'question' => $question,
            'form' => $form,
        ]);
    }

    // 4. VOIR DÉTAIL (SHOW)
    #[Route('/{id}', name: 'app_admin_forum_show', methods: ['GET', 'POST'])]
    public function show(Request $request, Question $question, EntityManagerInterface $entityManager): Response
    {
        // Création d'une nouvelle réponse vide
        $reponse = new Reponse();
        $form = $this->createForm(ReponseType::class, $reponse);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // --- FIX AUTEUR (comme tout à l'heure) ---
            $user = $entityManager->getRepository(User::class)->findOneBy([]);
            $reponse->setAuteur($user); // On force le 1er user trouvé
            // -----------------------------------------

            $reponse->setDateCreation(new \DateTimeImmutable());
            $reponse->setQuestion($question); // On lie la réponse à la question actuelle

            $entityManager->persist($reponse);
            $entityManager->flush();

            $this->addFlash('success', 'Votre réponse a été publiée !');
            
            // On recharge la page pour voir la réponse
            return $this->redirectToRoute('app_admin_forum_show', ['id' => $question->getId()]);
        }

        return $this->render('admin_forum/show.html.twig', [
            'question' => $question,
            'form' => $form, // On envoie le formulaire à la vue
        ]);
    }

    // 5. SUPPRIMER SUJET (DELETE QUESTION)
    #[Route('/{id}', name: 'app_admin_forum_delete_question', methods: ['POST'])]
    public function deleteQuestion(Request $request, Question $question, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$question->getId(), $request->request->get('_token'))) {
            $entityManager->remove($question);
            $entityManager->flush();
            $this->addFlash('success', 'Sujet supprimé.');
        }

        return $this->redirectToRoute('app_admin_forum_index');
    }

    // 6. SUPPRIMER RÉPONSE (DELETE REPONSE)
    #[Route('/reponse/{id}/delete', name: 'app_admin_forum_delete_reponse', methods: ['POST'])]
    public function deleteReponse(Request $request, Reponse $reponse, EntityManagerInterface $entityManager): Response
    {
        $questionId = $reponse->getQuestion()->getId();

        if ($this->isCsrfTokenValid('delete'.$reponse->getId(), $request->request->get('_token'))) {
            $entityManager->remove($reponse);
            $entityManager->flush();
            $this->addFlash('success', 'Réponse supprimée.');
        }

        return $this->redirectToRoute('app_admin_forum_show', ['id' => $questionId]);
    }

    // 7. MODIFIER UNE RÉPONSE (NOUVEAU)
    #[Route('/reponse/{id}/edit', name: 'app_admin_forum_edit_reponse', methods: ['GET', 'POST'])]
    public function editReponse(Request $request, Reponse $reponse, EntityManagerInterface $entityManager): Response
    {
        $questionId = $reponse->getQuestion()->getId(); // Pour le retour
        $form = $this->createForm(ReponseType::class, $reponse);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'La réponse a été modifiée.');
            return $this->redirectToRoute('app_admin_forum_show', ['id' => $questionId]);
        }

        return $this->render('admin_forum/edit_reponse.html.twig', [
            'reponse' => $reponse,
            'form' => $form,
        ]);
    }
}