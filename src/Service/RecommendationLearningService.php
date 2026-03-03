<?php

namespace App\Service;

use App\Entity\MissionVolunteer;
use App\Entity\RecommendationEvent;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class RecommendationLearningService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /** @param array<string, mixed> $metadata */
    public function track(User $user, MissionVolunteer $mission, string $eventType, float $signal = 1.0, array $metadata = []): void
    {
        $event = (new RecommendationEvent())
            ->setUser($user)
            ->setMission($mission)
            ->setEventType($eventType)
            ->setSignalStrength($signal)
            ->setMetadata($metadata)
            ->setCreatedAt(new \DateTime());

        $this->entityManager->persist($event);
        $this->adaptUserWeights($user, $eventType, $signal);
    }

    private function adaptUserWeights(User $user, string $eventType, float $signal): void
    {
        $weights = $user->getRecommendationWeights();
        if ($weights === []) {
            $weights = [
                'skills' => 0.35,
                'geo' => 0.25,
                'availability' => 0.20,
                'history' => 0.10,
                'social' => 0.10,
            ];
        }

        $delta = min(0.05, max(0.005, abs($signal) * 0.01));
        if ($eventType === 'apply_created') {
            $weights['skills'] += $delta;
            $weights['availability'] += $delta;
            $weights['history'] += ($delta / 2);
        } elseif ($eventType === 'mission_liked') {
            $weights['social'] += $delta;
        } elseif ($eventType === 'mission_rated') {
            $weights['social'] += $delta;
            $weights['skills'] += ($delta / 2);
        } elseif ($eventType === 'mission_ignored') {
            $weights['social'] = max(0.01, $weights['social'] - $delta);
            $weights['history'] = max(0.01, $weights['history'] - ($delta / 2));
        }

        $sum = array_sum($weights);
        foreach ($weights as $key => $value) {
            $weights[$key] = max(0.01, $value / $sum);
        }

        $user->setRecommendationWeights($weights);
        $this->entityManager->persist($user);
    }
}
