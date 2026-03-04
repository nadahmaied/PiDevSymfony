<?php

namespace App\Controller;

use App\Entity\Question;
use App\Entity\Reponse;
use App\Entity\User;
use App\Form\QuestionType;
use App\Form\ReponseType;
use App\Repository\QuestionRepository;
use App\Service\ForumAiAssistant;
use App\Service\ForumModerationAiService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/forum')]
class ForumController extends AbstractController
{
    #[Route('/ai/ameliorer-sujet', name: 'app_forum_ai_enhance', methods: ['POST'])]
    public function enhanceQuestion(Request $request, ForumAiAssistant $forumAiAssistant): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return $this->json([
                'ok' => false,
                'message' => 'Payload JSON invalide.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $title = (string) ($payload['title'] ?? '');
        $content = (string) ($payload['content'] ?? '');

        try {
            $result = $forumAiAssistant->enhanceQuestion($title, $content);

            return $this->json([
                'ok' => true,
                'title' => $result['title'],
                'content' => $result['content'],
                'tags' => $result['tags'],
                'source' => $result['source'],
            ]);
        } catch (\InvalidArgumentException $e) {
            return $this->json([
                'ok' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Throwable) {
            return $this->json([
                'ok' => false,
                'message' => 'Impossible de generer une suggestion IA pour le moment.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/', name: 'app_forum_index', methods: ['GET'])]
    public function index(QuestionRepository $questionRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $searchTerm = $request->query->get('q');
        $filter = $request->query->get('filter', 'recent');

        $qb = $questionRepository->createQueryBuilder('q')
            ->leftJoin('q.auteur', 'u')
            ->leftJoin('q.reponses', 'r')
            ->addSelect('u', 'r')
            ->andWhere('q.moderationStatus != :blockedStatus')
            ->setParameter('blockedStatus', 'blocked');

        if ($searchTerm) {
            $qb->andWhere('q.titre LIKE :search OR q.contenu LIKE :search')
               ->setParameter('search', '%' . $searchTerm . '%');
        }

        switch ($filter) {
            case 'popular':
                $qb->orderBy('SIZE(q.reponses)', 'DESC');
                break;
            case 'unanswered':
                $qb->andWhere('SIZE(q.reponses) = 0')
                   ->orderBy('q.dateCreation', 'DESC');
                break;
            case 'recent':
            default:
                $qb->orderBy('q.dateCreation', 'DESC');
                break;
        }

        $pagination = $paginator->paginate(
            $qb->getQuery(),
            $request->query->getInt('page', 1),
            6
        );

        return $this->render('forum/index.html.twig', [
            'pagination' => $pagination,
            'searchTerm' => $searchTerm,
            'currentFilter' => $filter,
        ]);
    }

    #[Route('/nouveau', name: 'app_forum_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ForumModerationAiService $moderationAiService): Response
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
            $this->applyModerationToQuestion($question, $moderationAiService->analyze(
                (string) $question->getTitre() . "\n\n" . (string) $question->getContenu()
            ));

            $entityManager->persist($question);
            $entityManager->flush();

            $this->addFlash('success', $this->moderationSuccessMessage($question->getModerationStatus(), 'sujet'));
            return $this->redirectToRoute('app_forum_index');
        }

        return $this->render('forum/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/sujet/{id}', name: 'app_forum_show', methods: ['GET', 'POST'])]
    public function show(
        Request $request,
        Question $question,
        EntityManagerInterface $entityManager,
        ForumModerationAiService $moderationAiService
    ): Response
    {
        if ($question->getModerationStatus() === 'blocked') {
            throw $this->createNotFoundException('Ce sujet est indisponible.');
        }

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
            $this->applyModerationToReponse($reponse, $moderationAiService->analyze((string) $reponse->getContenu()));

            $entityManager->persist($reponse);
            $entityManager->flush();

            $this->addFlash('success', $this->moderationSuccessMessage($reponse->getModerationStatus(), 'reponse'));
            return $this->redirectToRoute('app_forum_show', ['id' => $question->getId()]);
        }

        $visibleReponses = $question->getReponses()->filter(
            static fn (Reponse $item): bool => $item->getModerationStatus() !== 'blocked'
        );

        return $this->render('forum/show.html.twig', [
            'question' => $question,
            'visibleReponses' => $visibleReponses,
            'form' => $form,
        ]);
    }

    #[Route('/sujet/{id}/edit', name: 'app_forum_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Question $question, EntityManagerInterface $entityManager): Response
    {
        if (!$this->canManageQuestion($question)) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas modifier ce sujet.');
        }

        $form = $this->createForm(QuestionType::class, $question);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Sujet modifie avec succes.');
            return $this->redirectToRoute('app_forum_show', ['id' => $question->getId()]);
        }

        return $this->render('forum/edit.html.twig', [
            'question' => $question,
            'form' => $form,
        ]);
    }

    #[Route('/sujet/{id}/delete', name: 'app_forum_delete', methods: ['POST'])]
    public function delete(Request $request, Question $question, EntityManagerInterface $entityManager): Response
    {
        if (!$this->canManageQuestion($question)) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer ce sujet.');
        }

        if ($this->isCsrfTokenValid('delete' . $question->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($question);
            $entityManager->flush();
            $this->addFlash('success', 'Sujet supprime.');
        }

        return $this->redirectToRoute('app_forum_index');
    }

    #[Route('/reponse/{id}/delete', name: 'app_forum_delete_reponse', methods: ['POST'])]
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

        return $this->redirectToRoute('app_forum_show', ['id' => $questionId]);
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

    /** @param array{status:string,toxicity:float,sensitive:float,medicalRisk:float,reasons:list<string>} $analysis */
    private function applyModerationToQuestion(Question $question, array $analysis): void
    {
        $status = $analysis['status'];
        $question->setModerationStatus($status);
        $question->setToxicityScore($analysis['toxicity']);
        $question->setSensitiveScore($analysis['sensitive']);
        $question->setMedicalRiskScore($analysis['medicalRisk']);
        $question->setModerationReason(implode(' | ', $analysis['reasons']));

        if ($status === 'review' || $status === 'blocked') {
            $question->setFlaggedAt(new \DateTimeImmutable());
        } else {
            $question->setFlaggedAt(null);
        }
    }

    /** @param array{status:string,toxicity:float,sensitive:float,medicalRisk:float,reasons:list<string>} $analysis */
    private function applyModerationToReponse(Reponse $reponse, array $analysis): void
    {
        $status = $analysis['status'];
        $reponse->setModerationStatus($status);
        $reponse->setToxicityScore($analysis['toxicity']);
        $reponse->setSensitiveScore($analysis['sensitive']);
        $reponse->setMedicalRiskScore($analysis['medicalRisk']);
        $reponse->setModerationReason(implode(' | ', $analysis['reasons']));

        if ($status === 'review' || $status === 'blocked') {
            $reponse->setFlaggedAt(new \DateTimeImmutable());
        } else {
            $reponse->setFlaggedAt(null);
        }
    }

    private function moderationSuccessMessage(string $status, string $type): string
    {
        return match ($status) {
            'blocked' => sprintf(
                'Votre %s est en attente de moderation (contenu sensible detecte).',
                $type
            ),
            'review' => sprintf(
                'Votre %s a ete publie et signale pour verification admin.',
                $type
            ),
            default => sprintf('Votre %s a ete publie.', $type),
        };
    }
}
