<?php

namespace App\Twig;

use App\Repository\UserRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AdminVerificationExtension extends AbstractExtension
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly Security $security,
    ) {
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('pending_verification_count', [$this, 'getPendingVerificationCount']),
        ];
    }

    public function getPendingVerificationCount(): int
    {
        $user = $this->security->getUser();

        if (!$user || !$this->security->isGranted('ROLE_ADMIN')) {
            return 0;
        }

        return $this->userRepository->count([
            'role' => 'ROLE_MEDECIN',
            'verificationStatus' => 'pending_review',
        ]);
    }
}

