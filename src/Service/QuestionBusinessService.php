<?php

namespace App\Service;

use App\Entity\Question;
use App\Entity\User;

class QuestionBusinessService
{
    public function prepareForCreation(Question $question, ?\DateTimeImmutable $now = null): Question
    {
        $now = $now ?? new \DateTimeImmutable();

        $question->setTitre(trim((string) $question->getTitre()));
        $question->setContenu(trim((string) $question->getContenu()));

        if ($question->getDateCreation() === null) {
            $question->setDateCreation($now);
        }

        return $question;
    }

    public function isPublishable(Question $question): bool
    {
        $titre = trim((string) $question->getTitre());
        $contenu = trim((string) $question->getContenu());

        if ($question->getAuteur() === null) {
            return false;
        }

        if (mb_strlen($titre) < 5 || mb_strlen($titre) > 150) {
            return false;
        }

        if (mb_strlen($contenu) < 20) {
            return false;
        }

        return true;
    }

    public function canEdit(Question $question, User $actor, ?\DateTimeImmutable $now = null): bool
    {
        $author = $question->getAuteur();
        $createdAt = $question->getDateCreation();
        if ($author === null || $createdAt === null) {
            return false;
        }

        if ($author->getId() === null || $actor->getId() === null || $author->getId() !== $actor->getId()) {
            return false;
        }

        $now = $now ?? new \DateTimeImmutable();
        $limit = $createdAt->modify('+15 minutes');

        return $now <= $limit;
    }
}
