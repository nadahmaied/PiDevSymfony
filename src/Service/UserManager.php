<?php

namespace App\Service;

use App\Entity\User;

/**
 * Service métier pour la gestion des utilisateurs.
 * Valide les règles métier sur l'entité User.
 */
class UserManager
{
    /**
     * Valide un utilisateur selon les règles métier :
     * 1. Le nom est obligatoire
     * 2. L'email doit être valide
     *
     * @throws \InvalidArgumentException si une règle n'est pas respectée
     */
    public function validate(User $user): bool
    {
        if (empty(trim((string) $user->getNom()))) {
            throw new \InvalidArgumentException('Le nom est obligatoire');
        }

        $email = $user->getEmail();
        if ($email === null || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Email invalide');
        }

        return true;
    }
}
