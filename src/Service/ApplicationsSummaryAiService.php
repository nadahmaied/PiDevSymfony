<?php

namespace App\Service;

use App\Entity\MissionVolunteer;
use App\Entity\Volunteer;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ApplicationsSummaryAiService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly ?string $apiKey,
        private readonly string $apiUrl,
        private readonly string $model,
    ) {
    }

    public function summarize(MissionVolunteer $mission): array
    {
        $applications = $this->buildApplicationScores($mission);

        if (count($applications) === 0) {
            return [
                'source' => 'fallback',
                'summary' => 'Aucune candidature pour cette mission.',
                'topCandidates' => [],
                'risks' => ['Publier une relance pour attirer des benevoles.'],
                'recommendation' => 'Activer des notifications ciblees sur les competences requises.',
                'localRanking' => [],
            ];
        }

        if (!$this->apiKey) {
            return $this->fallbackSummary($applications, $mission);
        }

        $payload = $this->callAi($applications, $mission);
        if (!is_array($payload)) {
            return $this->fallbackSummary($applications, $mission);
        }

        $summary = $this->sanitizeText($payload['summary'] ?? '');
        $recommendation = $this->sanitizeText($payload['recommendation'] ?? '');
        $risks = $this->sanitizeList($payload['risks'] ?? []);
        $topCandidates = $this->sanitizeTopCandidates($payload['topCandidates'] ?? [], $applications);

        if ($summary === '' || $recommendation === '' || count($topCandidates) === 0) {
            return $this->fallbackSummary($applications, $mission);
        }

        return [
            'source' => 'ai',
            'summary' => $summary,
            'topCandidates' => $topCandidates,
            'risks' => $risks,
            'recommendation' => $recommendation,
            'localRanking' => array_slice($applications, 0, 5),
        ];
    }

    private function callAi(array $applications, MissionVolunteer $mission): ?array
    {
        $missionData = [
            'id' => $mission->getId(),
            'title' => (string) $mission->getTitre(),
            'location' => (string) $mission->getLieu(),
            'requiredSkills' => $mission->requiredSkillsAsArray(),
            'criticalPeriods' => $mission->criticalPeriodsAsArray(),
            'urgencyLevel' => $mission->getUrgencyLevel() ?? 3,
            'difficultyLevel' => $mission->getDifficultyLevel() ?? 3,
        ];

        $shortlist = array_map(static function (array $item): array {
            return [
                'volunteerId' => $item['volunteerId'],
                'userName' => $item['userName'],
                'userEmail' => $item['userEmail'],
                'finalScore' => $item['finalScore'],
                'skillsScore' => $item['skillsScore'],
                'geoScore' => $item['geoScore'],
                'availabilityScore' => $item['availabilityScore'],
                'motivationScore' => $item['motivationScore'],
                'status' => $item['status'],
                'motivationPreview' => $item['motivationPreview'],
            ];
        }, array_slice($applications, 0, 12));

        $prompt = <<<PROMPT
Tu es un assistant RH pour une plateforme de benevolat.
Analyse la mission et les candidatures scorees.
Reponds en JSON strict avec:
- summary: texte court (1-3 phrases)
- topCandidates: tableau de 3 objets max
  - volunteerId (integer)
  - rank (1..3)
  - reason (phrase courte)
- risks: tableau de 2 a 4 risques
- recommendation: 1 action concrete pour l'admin
Contraintes:
- Francais
- Base uniquement sur les donnees fournies
- Pas de texte hors JSON
PROMPT;

        $response = $this->httpClient->request('POST', $this->apiUrl, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => $prompt],
                    ['role' => 'user', 'content' => json_encode([
                        'mission' => $missionData,
                        'applications' => $shortlist,
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)],
                ],
                'temperature' => 0.2,
                'response_format' => ['type' => 'json_object'],
            ],
            'timeout' => 20,
        ]);

        $raw = $response->toArray(false)['choices'][0]['message']['content'] ?? null;
        if (!is_string($raw) || trim($raw) === '') {
            return null;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return null;
        }

        return $decoded;
    }

    private function fallbackSummary(array $applications, MissionVolunteer $mission): array
    {
        $top = array_slice($applications, 0, 3);
        $topCandidates = [];
        foreach ($top as $idx => $item) {
            $topCandidates[] = [
                'volunteerId' => $item['volunteerId'],
                'userName' => $item['userName'],
                'userEmail' => $item['userEmail'],
                'rank' => $idx + 1,
                'reason' => sprintf(
                    'Score %d%% (competences %d%%, disponibilite %d%%, localisation %d%%).',
                    $item['finalScore'],
                    $item['skillsScore'],
                    $item['availabilityScore'],
                    $item['geoScore']
                ),
            ];
        }

        $avgScore = (int) round(array_sum(array_column($applications, 'finalScore')) / max(1, count($applications)));
        $avgSkills = (int) round(array_sum(array_column($applications, 'skillsScore')) / max(1, count($applications)));

        $risks = [];
        if ($avgSkills < 45) {
            $risks[] = 'Moyenne competences basse: renforcer la communication ciblee.';
        }
        if (($mission->getUrgencyLevel() ?? 3) >= 4 && count($applications) < 5) {
            $risks[] = 'Mission urgente avec peu de candidatures.';
        }
        if (count($risks) === 0) {
            $risks[] = 'Aucun risque majeur detecte sur les candidatures actuelles.';
        }

        return [
            'source' => 'fallback',
            'summary' => sprintf(
                '%d candidatures analysees. Score moyen estime: %d%%. Prioriser les profils les mieux alignes avec les competences requises.',
                count($applications),
                $avgScore
            ),
            'topCandidates' => $topCandidates,
            'risks' => $risks,
            'recommendation' => 'Valider rapidement les 2 meilleurs profils puis relancer les candidats en attente.',
            'localRanking' => array_slice($applications, 0, 5),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildApplicationScores(MissionVolunteer $mission): array
    {
        $requiredSkills = $this->normalizeTokens($mission->requiredSkillsAsArray());
        $criticalPeriods = $this->normalizeTokens($mission->criticalPeriodsAsArray());
        $location = mb_strtolower(trim((string) $mission->getLieu()));

        $rows = [];
        /** @var Volunteer $application */
        foreach ($mission->getVolunteers() as $application) {
            $user = $application->getUser();
            if ($user === null) {
                continue;
            }

            $userSkills = $this->normalizeTokens($user->skillsProfileAsArray());
            $appAvailability = $this->normalizeTokens($application->getDisponibilites());
            $userAvailability = $this->normalizeTokens($user->availabilityProfileAsArray());
            $mergedAvailability = array_values(array_unique(array_merge($appAvailability, $userAvailability)));

            $skillsScore = $this->overlapScore($requiredSkills, $userSkills);
            $availabilityScore = $this->overlapScore($criticalPeriods, $mergedAvailability);
            $geoScore = $this->geoScore($location, $user->getPreferredCity());
            $motivationScore = $this->motivationScore((string) $application->getMotivation());
            $statusScore = $this->statusScore((string) $application->getStatut());

            $final = (int) round(
                ($skillsScore * 0.40)
                + ($availabilityScore * 0.25)
                + ($geoScore * 0.15)
                + ($motivationScore * 0.15)
                + ($statusScore * 0.05)
            );

            $rows[] = [
                'volunteerId' => $application->getId(),
                'userName' => trim((string) $user->getPrenom() . ' ' . (string) $user->getNom()),
                'userEmail' => (string) $user->getEmail(),
                'status' => (string) $application->getStatut(),
                'skillsScore' => $skillsScore,
                'availabilityScore' => $availabilityScore,
                'geoScore' => $geoScore,
                'motivationScore' => $motivationScore,
                'finalScore' => max(0, min(100, $final)),
                'motivationPreview' => mb_substr($this->sanitizeText((string) $application->getMotivation()), 0, 140),
            ];
        }

        usort($rows, static fn (array $a, array $b): int => $b['finalScore'] <=> $a['finalScore']);

        return $rows;
    }

    private function overlapScore(array $expected, array $actual): int
    {
        if (count($expected) === 0) {
            return 50;
        }

        $intersection = array_intersect($expected, $actual);
        $ratio = count($intersection) / max(1, count($expected));

        return (int) round($ratio * 100);
    }

    private function geoScore(string $missionLocation, ?string $userPreferredCity): int
    {
        $city = mb_strtolower(trim((string) $userPreferredCity));
        if ($city === '') {
            return 50;
        }

        return $city === $missionLocation ? 100 : 40;
    }

    private function motivationScore(string $motivation): int
    {
        $len = mb_strlen(trim($motivation));
        if ($len >= 180) {
            return 100;
        }
        if ($len >= 120) {
            return 80;
        }
        if ($len >= 60) {
            return 60;
        }

        return 35;
    }

    private function statusScore(string $status): int
    {
        $status = mb_strtolower(trim($status));
        if ($status === 'accepte') {
            return 100;
        }
        if ($status === 'en attente') {
            return 70;
        }
        if ($status === 'refuse') {
            return 20;
        }

        return 50;
    }

    private function sanitizeTopCandidates(array $rawTop, array $applications): array
    {
        $validIds = array_map(static fn (array $a): int => (int) $a['volunteerId'], $applications);
        $validIds = array_values(array_unique($validIds));

        $top = [];
        foreach ($rawTop as $item) {
            if (!is_array($item)) {
                continue;
            }

            $id = (int) ($item['volunteerId'] ?? 0);
            if ($id <= 0 || !in_array($id, $validIds, true)) {
                continue;
            }

            $top[] = [
                'volunteerId' => $id,
                'userName' => $this->findApplicationValue($applications, $id, 'userName'),
                'userEmail' => $this->findApplicationValue($applications, $id, 'userEmail'),
                'rank' => max(1, min(3, (int) ($item['rank'] ?? (count($top) + 1)))),
                'reason' => $this->sanitizeText($item['reason'] ?? ''),
            ];
        }

        if (count($top) === 0) {
            return array_map(static fn (array $row, int $index): array => [
                'volunteerId' => (int) $row['volunteerId'],
                'userName' => (string) ($row['userName'] ?? ''),
                'userEmail' => (string) ($row['userEmail'] ?? ''),
                'rank' => $index + 1,
                'reason' => sprintf('Score local %d%%.', $row['finalScore']),
            ], array_slice($applications, 0, 3), array_keys(array_slice($applications, 0, 3)));
        }

        usort($top, static fn (array $a, array $b): int => $a['rank'] <=> $b['rank']);

        return array_slice($top, 0, 3);
    }

    private function sanitizeText(mixed $text): string
    {
        if (!is_string($text)) {
            return '';
        }

        $text = str_replace(["\r\n", "\r"], "\n", trim($text));
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text);
    }

    private function sanitizeList(mixed $list): array
    {
        if (!is_array($list)) {
            return [];
        }

        $out = [];
        foreach ($list as $item) {
            $text = $this->sanitizeText($item);
            if ($text !== '') {
                $out[] = $text;
            }
        }

        return array_slice(array_values(array_unique($out)), 0, 4);
    }

    private function normalizeTokens(array $values): array
    {
        $items = [];
        foreach ($values as $value) {
            if (!is_string($value)) {
                continue;
            }

            $parts = array_map('trim', explode(',', mb_strtolower($value)));
            foreach ($parts as $part) {
                if ($part !== '') {
                    $items[] = $part;
                }
            }
        }

        return array_values(array_unique($items));
    }

    private function findApplicationValue(array $applications, int $volunteerId, string $key): string
    {
        foreach ($applications as $row) {
            if ((int) ($row['volunteerId'] ?? 0) === $volunteerId) {
                return (string) ($row[$key] ?? '');
            }
        }

        return '';
    }
}
