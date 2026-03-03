<?php

namespace App\Service;

use App\Entity\MissionVolunteer;
use App\Entity\User;
use App\Entity\Volunteer;
use App\Repository\MissionLikeRepository;
use App\Repository\MissionRatingRepository;
use App\Repository\VolunteerRepository;

class MissionRecommendationService
{
    private const DEFAULT_WEIGHTS = [
        'skills' => 0.35,
        'geo' => 0.25,
        'availability' => 0.20,
        'history' => 0.10,
        'social' => 0.10,
    ];

    public function __construct(
        private readonly VolunteerRepository $volunteerRepository,
        private readonly MissionLikeRepository $likeRepository,
        private readonly MissionRatingRepository $ratingRepository,
        private readonly MlModelScoringService $mlModelScoringService,
    ) {
    }

    /**
     * @param list<MissionVolunteer> $missions
     * @return list<array<string, mixed>>
     */
    public function recommendForUser(User $user, array $missions, int $limit = 5): array
    {
        $recommendations = [];
        foreach ($missions as $mission) {
            $recommendations[] = $this->scoreMission($user, $mission);
        }

        usort(
            $recommendations,
            static fn (array $a, array $b): int => $b['matchPercent'] <=> $a['matchPercent']
        );

        return array_slice($recommendations, 0, $limit);
    }

    /** @return array<string, mixed> */
    public function scoreMission(User $user, MissionVolunteer $mission): array
    {
        $weights = $this->normalizeWeights($user->getRecommendationWeights());

        $skills = $this->skillsScore($user, $mission);
        $geo = $this->geoScore($user, $mission);
        $availability = $this->availabilityScore($user, $mission);
        $history = $this->historyScore($user, $mission);
        $social = $this->socialScore($user, $mission);

        $heuristicScore = ($skills * $weights['skills'])
            + ($geo * $weights['geo'])
            + ($availability * $weights['availability'])
            + ($history * $weights['history'])
            + ($social * $weights['social']);

        $features = $this->buildFeatureVector($mission, $skills, $geo, $availability, $history, $social);
        $mlScore = $this->mlModelScoringService->predictProbability($features);
        $mlWeight = $this->getMlBlendWeight();
        $heuristicWeight = 1 - $mlWeight;

        $finalScore = $heuristicScore;
        if ($mlScore !== null) {
            // Adaptive blend: low ML weight on small datasets, higher as dataset grows.
            $finalScore = ($heuristicScore * $heuristicWeight) + ($mlScore * $mlWeight);
        }

        $reasons = $this->buildReasons($mission, $skills, $geo, $availability, $history, $social);

        return [
            'mission' => $mission,
            'matchPercent' => (int) round(max(0.0, min(1.0, $finalScore)) * 100),
            'heuristicPercent' => (int) round(max(0.0, min(1.0, $heuristicScore)) * 100),
            'mlPercent' => $mlScore !== null ? (int) round(max(0.0, min(1.0, $mlScore)) * 100) : null,
            'mlWeightPercent' => (int) round($mlWeight * 100),
            'heuristicWeightPercent' => (int) round($heuristicWeight * 100),
            'modelTrainRows' => $this->mlModelScoringService->getTrainingRows(),
            'breakdown' => [
                'skills' => (int) round($skills * 100),
                'geo' => (int) round($geo * 100),
                'availability' => (int) round($availability * 100),
                'history' => (int) round($history * 100),
                'social' => (int) round($social * 100),
            ],
            'features' => $features,
            'reasons' => $reasons,
        ];
    }

    /** @return array<string, float> */
    public function buildTrainingFeatures(User $user, MissionVolunteer $mission): array
    {
        $skills = $this->skillsScore($user, $mission);
        $geo = $this->geoScore($user, $mission);
        $availability = $this->availabilityScore($user, $mission);
        $history = $this->historyScore($user, $mission);
        $social = $this->socialScore($user, $mission);

        return $this->buildFeatureVector($mission, $skills, $geo, $availability, $history, $social);
    }

    /**
     * @param list<MissionVolunteer> $missions
     * @return array{openCount: int, urgentCount: int, hardCount: int, topMissingSkills: array<string, int>}
     */
    public function adminInsights(array $missions): array
    {
        $open = array_filter($missions, static fn (MissionVolunteer $m): bool => $m->getStatut() === 'Ouverte');
        $urgent = array_filter($open, static fn (MissionVolunteer $m): bool => ($m->getUrgencyLevel() ?? 0) >= 4);
        $hard = array_filter($open, static fn (MissionVolunteer $m): bool => ($m->getDifficultyLevel() ?? 0) >= 4);

        $missingSkills = [];
        foreach ($open as $mission) {
            foreach ($mission->requiredSkillsAsArray() as $skill) {
                $missingSkills[$skill] = ($missingSkills[$skill] ?? 0) + 1;
            }
        }

        arsort($missingSkills);
        $topMissing = array_slice($missingSkills, 0, 5, true);

        return [
            'openCount' => count($open),
            'urgentCount' => count($urgent),
            'hardCount' => count($hard),
            'topMissingSkills' => $topMissing,
        ];
    }

    /** @return array<string, float> */
    private function buildFeatureVector(
        MissionVolunteer $mission,
        float $skills,
        float $geo,
        float $availability,
        float $history,
        float $social
    ): array {
        return [
            'skills' => $skills,
            'geo' => $geo,
            'availability' => $availability,
            'history' => $history,
            'social' => $social,
            'urgency' => max(0.0, min(1.0, (($mission->getUrgencyLevel() ?? 3) / 5))),
            'difficulty' => max(0.0, min(1.0, (($mission->getDifficultyLevel() ?? 3) / 5))),
            'duration_days' => $this->durationDaysScore($mission),
        ];
    }

    private function durationDaysScore(MissionVolunteer $mission): float
    {
        $start = $mission->getDateDebut();
        $end = $mission->getDateFin();
        if (!$start || !$end) {
            return 0.5;
        }

        $days = max(1, (int) $start->diff($end)->format('%a') + 1);

        // 1 day -> 1.0, 30+ days -> close to 0.0
        return max(0.0, min(1.0, 1 - (($days - 1) / 30)));
    }

    private function skillsScore(User $user, MissionVolunteer $mission): float
    {
        return $this->overlapScore($user->skillsProfileAsArray(), $mission->requiredSkillsAsArray());
    }

    private function availabilityScore(User $user, MissionVolunteer $mission): float
    {
        return $this->overlapScore($user->availabilityProfileAsArray(), $mission->criticalPeriodsAsArray());
    }

    private function historyScore(User $user, MissionVolunteer $mission): float
    {
        /** @var Volunteer[] $history */
        $history = $this->volunteerRepository->findBy(['user' => $user], ['id' => 'DESC'], 30);
        if ($history === []) {
            return 0.5;
        }

        $targetTags = array_merge($mission->requiredSkillsAsArray(), $mission->thematicTagsAsArray());
        if ($targetTags === []) {
            return 0.5;
        }

        $total = 0.0;
        $count = 0;
        foreach ($history as $application) {
            $previous = $application->getMission();
            if (!$previous instanceof MissionVolunteer) {
                continue;
            }

            $previousTags = array_merge($previous->requiredSkillsAsArray(), $previous->thematicTagsAsArray());
            $total += $this->overlapScore($targetTags, $previousTags);
            ++$count;
        }

        return $count > 0 ? ($total / $count) : 0.5;
    }

    private function socialScore(User $user, MissionVolunteer $mission): float
    {
        $allRatings = $this->ratingRepository->findBy(['mission' => $mission]);
        $avg = 0.0;
        if (count($allRatings) > 0) {
            $sum = 0;
            foreach ($allRatings as $rating) {
                $sum += $rating->getNote() ?? 0;
            }
            $avg = ($sum / count($allRatings)) / 5.0;
        }

        $likeCount = $this->likeRepository->count(['mission' => $mission]);
        $likeBoost = min(1.0, $likeCount / 20.0);

        return min(1.0, ($avg * 0.7) + ($likeBoost * 0.3));
    }

    private function geoScore(User $user, MissionVolunteer $mission): float
    {
        if (
            $user->getLatitude() !== null && $user->getLongitude() !== null
            && $mission->getLatitude() !== null && $mission->getLongitude() !== null
        ) {
            $distance = $this->distanceKm(
                $user->getLatitude(),
                $user->getLongitude(),
                $mission->getLatitude(),
                $mission->getLongitude()
            );

            $radius = $user->getActionRadiusKm() ?? 20;
            if ($distance <= 0.0) {
                return 1.0;
            }

            if ($distance >= ($radius * 2)) {
                return 0.0;
            }

            return max(0.0, 1 - ($distance / ($radius * 2)));
        }

        $city = mb_strtolower(trim((string) $user->getPreferredCity()));
        $lieu = mb_strtolower(trim((string) $mission->getLieu()));
        if ($city !== '' && $lieu !== '' && str_contains($lieu, $city)) {
            return 1.0;
        }

        return 0.5;
    }

    /**
     * @param list<string> $left
     * @param list<string> $right
     */
    private function overlapScore(array $left, array $right): float
    {
        $left = array_values(array_unique(array_filter($left)));
        $right = array_values(array_unique(array_filter($right)));

        if ($right === []) {
            return 0.5;
        }

        if ($left === []) {
            return 0.0;
        }

        $intersection = array_intersect($left, $right);
        $union = array_unique(array_merge($left, $right));

        return count($intersection) / count($union);
    }

    private function distanceKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371.0;
        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lon2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(
            (sin($latDelta / 2) ** 2) +
            cos($latFrom) * cos($latTo) * (sin($lonDelta / 2) ** 2)
        ));

        return $earthRadius * $angle;
    }

    /**
     * @param array<string, float|int> $customWeights
     * @return array<string, float>
     */
    private function normalizeWeights(array $customWeights): array
    {
        $weights = self::DEFAULT_WEIGHTS;
        foreach ($weights as $key => $default) {
            if (isset($customWeights[$key])) {
                $weights[$key] = max(0.0, (float) $customWeights[$key]);
            }
        }

        $sum = array_sum($weights);
        if ($sum <= 0.0) {
            return self::DEFAULT_WEIGHTS;
        }

        foreach ($weights as $key => $value) {
            $weights[$key] = $value / $sum;
        }

        return $weights;
    }

    /** @return list<string> */
    private function buildReasons(
        MissionVolunteer $mission,
        float $skills,
        float $geo,
        float $availability,
        float $history,
        float $social
    ): array {
        $reasons = [];
        if ($skills >= 0.5) {
            $reasons[] = 'Vos competences correspondent aux besoins de la mission.';
        }
        if ($geo >= 0.7) {
            $reasons[] = 'Mission proche de votre zone preferee.';
        }
        if ($availability >= 0.5) {
            $reasons[] = 'Les horaires sont compatibles avec vos disponibilites.';
        }
        if ($history >= 0.5) {
            $reasons[] = 'Vous avez deja participe a des missions similaires.';
        }
        if ($social >= 0.6) {
            $reasons[] = 'Cette mission est appreciee par la communaute.';
        }
        if ($mission->getUrgencyLevel() !== null && $mission->getUrgencyLevel() >= 4) {
            $reasons[] = 'Mission urgente necessitant une intervention rapide.';
        }

        if ($reasons === []) {
            $reasons[] = 'Recommandation basee sur votre profil global.';
        }

        return array_slice($reasons, 0, 2);
    }

    private function getMlBlendWeight(): float
    {
        $rows = $this->mlModelScoringService->getTrainingRows();

        if ($rows < 50) {
            return 0.20;
        }

        if ($rows < 200) {
            return 0.40;
        }

        if ($rows < 1000) {
            return 0.55;
        }

        return 0.70;
    }
}
