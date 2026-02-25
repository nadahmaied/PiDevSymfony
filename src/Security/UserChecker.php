<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        // Check if doctor account is verified
        if ($user->getRole() === 'ROLE_MEDECIN' && !$user->isVerified()) {
            throw new CustomUserMessageAccountStatusException(
                'Votre compte médecin est en attente de vérification par un administrateur. Vous recevrez un email une fois votre compte approuvé.'
            );
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        // Nothing to check after authentication
    }
}
