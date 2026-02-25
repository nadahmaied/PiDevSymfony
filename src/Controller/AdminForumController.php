<?php

namespace App\Controller;

use App\Entity\Question;
use App\Entity\Reponse;
use App\Entity\User;
use App\Form\QuestionType;
use App\Form\ReponseType;
use App\Repository\QuestionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/forum')]
class AdminForumController extends AbstractController
{
    #[Route('/', name: 'app_admin_forum_index', methods: ['GET'])]
    public function index(QuestionRepository $questionRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $searchTerm = $request->query->get('q');

        $qb = $questionRepository->createQueryBuilder('q')
            ->leftJoin('q.auteur', 'u')
            ->addSelect('u');

        if ($searchTerm) {
            $qb->andWhere('q.titre LIKE :search OR q.contenu LIKE :search OR u.email LIKE :search')
               ->setParameter('search', '%' . $searchTerm . '%');
        }

        if (!$request->query->get('sort')) {
            $qb->orderBy('q.dateCreation', 'DESC');
        }

        $pagination = $paginator->paginate(
            $qb->getQuery(),
            $request->query->getInt('page', 1),
            5
        );

        return $this->render('admin_forum/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/new', name: 'app_admin_forum_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $question = new Question();
        $form = $this->createForm(QuestionType::class, $question);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getUser();
            if (!$user instanceof User) {
                throw $this->createAccessDeniedException('Vous devez etre connecte pour publier un sujet.');
            }

            $question->setAuteur($user);
            $question->setDateCreation(new \DateTimeImmutable());

            $entityManager->persist($question);
            $entityManager->flush();

            $this->addFlash('success', 'Sujet cree avec succes !');
            return $this->redirectToRoute('app_admin_forum_index');
        }

        return $this->render('admin_forum/new.html.twig', [
            'question' => $question,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_forum_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Question $question, EntityManagerInterface $entityManager): Response
    {
        if (!$this->canManageQuestion($question)) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas modifier ce sujet.');
        }

        $form = $this->createForm(QuestionType::class, $question);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Sujet modifie avec succes !');
            return $this->redirectToRoute('app_admin_forum_index');
        }

        return $this->render('admin_forum/edit.html.twig', [
            'question' => $question,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_forum_show', methods: ['GET', 'POST'])]
    public function show(Request $request, Question $question, EntityManagerInterface $entityManager): Response
    {
        $reponse = new Reponse();
        $form = $this->createForm(ReponseType::class, $reponse);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getUser();
            if (!$user instanceof User) {
                throw $this->createAccessDeniedException('Vous devez etre connecte pour repondre.');
            }

            $reponse->setAuteur($user);
            $reponse->setDateCreation(new \DateTimeImmutable());
            $reponse->setQuestion($question);

            $entityManager->persist($reponse);
            $entityManager->flush();

            $this->addFlash('success', 'Votre reponse a ete publiee !');
            return $this->redirectToRoute('app_admin_forum_show', ['id' => $question->getId()]);
        }

        return $this->render('admin_forum/show.html.twig', [
            'question' => $question,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_forum_delete_question', methods: ['POST'])]
    public function deleteQuestion(Request $request, Question $question, EntityManagerInterface $entityManager): Response
    {
        if (!$this->canManageQuestion($question)) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer ce sujet.');
        }

        if ($this->isCsrfTokenValid('delete' . $question->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($question);
            $entityManager->flush();
            $this->addFlash('success', 'Sujet supprime.');
        }

        return $this->redirectToRoute('app_admin_forum_index');
    }

    #[Route('/reponse/{id}/delete', name: 'app_admin_forum_delete_reponse', methods: ['POST'])]
    public function deleteReponse(Request $request, Reponse $reponse, EntityManagerInterface $entityManager): Response
    {
        $questionId = $reponse->getQuestion()->getId();

        if (!$this->canManageReponse($reponse)) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer cette reponse.');
        }

        if ($this->isCsrfTokenValid('delete' . $reponse->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($reponse);
            $entityManager->flush();
            $this->addFlash('success', 'Reponse supprimee.');
        }

        return $this->redirectToRoute('app_admin_forum_show', ['id' => $questionId]);
    }

    #[Route('/reponse/{id}/edit', name: 'app_admin_forum_edit_reponse', methods: ['GET', 'POST'])]
    public function editReponse(Request $request, Reponse $reponse, EntityManagerInterface $entityManager): Response
    {
        if (!$this->canManageReponse($reponse)) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas modifier cette reponse.');
        }

        $questionId = $reponse->getQuestion()->getId();
        $form = $this->createForm(ReponseType::class, $reponse);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'La reponse a ete modifiee.');
            return $this->redirectToRoute('app_admin_forum_show', ['id' => $questionId]);
        }

        return $this->render('admin_forum/edit_reponse.html.twig', [
            'reponse' => $reponse,
            'form' => $form,
        ]);
    }

    private function canManageQuestion(Question $question): bool
    {
        $user = $this->getUser();
        if (!$user instanceof User || !$question->getAuteur()) {
            return false;
        }

        return $question->getAuteur()->getId() === $user->getId();
    }

    private function canManageReponse(Reponse $reponse): bool
    {
        $user = $this->getUser();
        if (!$user instanceof User || !$reponse->getAuteur()) {
            return false;
        }

        return $reponse->getAuteur()->getId() === $user->getId();
    }
}
