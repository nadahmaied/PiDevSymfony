<?php

namespace App\Tests;

use App\Entity\Question;
use App\Entity\User;
use App\Service\QuestionBusinessService;
use PHPUnit\Framework\TestCase;

class QuestionBusinessServiceTest extends TestCase
{
    public function testQuestionIsPublishableWhenBusinessRulesAreSatisfied(): void
    {
        $service = new QuestionBusinessService();
        $question = $this->buildQuestion('Comment gerer une migraine ?', 'Je veux des conseils pour reduire les crises de migraine.');

        $auteur = new User();
        $auteur->setId(10);
        $question->setAuteur($auteur);

        $this->assertTrue($service->isPublishable($question));
    }

    public function testQuestionIsNotPublishableWithoutAuthor(): void
    {
        $service = new QuestionBusinessService();
        $question = $this->buildQuestion('Titre valide', 'Contenu suffisamment long pour etre valide.');

        $this->assertFalse($service->isPublishable($question));
    }

    public function testPrepareForCreationSetsCreationDateAndTrimsValues(): void
    {
        $service = new QuestionBusinessService();
        $question = new Question();
        $question->setTitre('  Mon titre  ');
        $question->setContenu('  Mon contenu detaille et assez long pour passer la regle.  ');

        $prepared = $service->prepareForCreation($question, new \DateTimeImmutable('2026-03-03 12:00:00'));

        $this->assertSame('Mon titre', $prepared->getTitre());
        $this->assertSame('Mon contenu detaille et assez long pour passer la regle.', $prepared->getContenu());
        $this->assertSame('2026-03-03 12:00:00', $prepared->getDateCreation()?->format('Y-m-d H:i:s'));
    }

    public function testCanEditOnlyByAuthorWithinTimeLimit(): void
    {
        $service = new QuestionBusinessService();
        $question = $this->buildQuestion('Titre edition', 'Contenu edition suffisamment long.');

        $author = new User();
        $author->setId(5);
        $question->setAuteur($author);
        $question->setDateCreation(new \DateTimeImmutable('2026-03-03 10:00:00'));

        $otherUser = new User();
        $otherUser->setId(7);

        $this->assertTrue($service->canEdit($question, $author, new \DateTimeImmutable('2026-03-03 10:10:00')));
        $this->assertFalse($service->canEdit($question, $author, new \DateTimeImmutable('2026-03-03 10:20:01')));
        $this->assertFalse($service->canEdit($question, $otherUser, new \DateTimeImmutable('2026-03-03 10:10:00')));
    }

    private function buildQuestion(string $titre, string $contenu): Question
    {
        $question = new Question();
        $question->setTitre($titre);
        $question->setContenu($contenu);

        return $question;
    }
}
