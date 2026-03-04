<?php

namespace App\Service;

use App\Entity\Annonce;

final class AnnonceManager
{
    /**
     * Valide les règles métier de base pour une annonce.
     *
     * - Le titre est obligatoire et non vide.
     * - L'urgence doit être l'une des valeurs autorisées: faible, moyenne, élevée.
     * - La date de publication ne peut pas être dans le passé.
     *
     * @throws \InvalidArgumentException si une règle métier n'est pas respectée.
     */
    public function validate(Annonce $annonce): bool
    {
        $titre = trim((string) $annonce->getTitreAnnonce());
        if ($titre === '') {
            throw new \InvalidArgumentException('Le titre de l\'annonce est obligatoire');
        }

        $urgence = $annonce->getUrgence();
        $allowedUrgences = ['faible', 'moyenne', 'élevée'];
        if (!in_array($urgence, $allowedUrgences, true)) {
            throw new \InvalidArgumentException('Le niveau d\'urgence est invalide');
        }

        $datePublication = $annonce->getDatePublication();
        if (!$datePublication instanceof \DateTimeInterface) {
            throw new \InvalidArgumentException('La date de publication est obligatoire');
        }

        $today = new \DateTimeImmutable('today');
        $dateOnly = \DateTimeImmutable::createFromFormat('Y-m-d', $datePublication->format('Y-m-d'));

        if ($dateOnly < $today) {
            throw new \InvalidArgumentException('La date de publication ne peut pas être dans le passé');
        }

        return true;
    }
}

