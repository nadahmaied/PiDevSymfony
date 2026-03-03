<?php

namespace App\Command;

use App\Entity\RecommendationEvent;
use App\Entity\MissionVolunteer;
use App\Entity\User;
use App\Repository\MissionVolunteerRepository;
use App\Repository\UserRepository;
use App\Service\MissionRecommendationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:ml:seed-recommendation-events',
    description: 'Generate synthetic recommendation events for ML training.',
)]
class SeedRecommendationEventsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
        private readonly MissionVolunteerRepository $missionRepository,
        private readonly MissionRecommendationService $recommendationService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('count', null, InputOption::VALUE_REQUIRED, 'Number of events to generate', '200')
            ->addOption('user-id', null, InputOption::VALUE_REQUIRED, 'Limit generation to one user ID')
            ->addOption('clear', null, InputOption::VALUE_NONE, 'Clear existing recommendation_event rows before seeding');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $count = max(1, (int) $input->getOption('count'));
        $userId = $input->getOption('user-id');
        $clear = (bool) $input->getOption('clear');

        $missions = $this->missionRepository->findBy(['statut' => 'Ouverte']);
        if ($missions === []) {
            $io->error('No open missions found. Create some missions first.');

            return Command::FAILURE;
        }
        $missionIds = array_map(static fn (MissionVolunteer $mission): int => (int) $mission->getId(), $missions);

        $users = [];
        if ($userId !== null) {
            $user = $this->userRepository->find((int) $userId);
            if (!$user instanceof User) {
                $io->error(sprintf('User %s not found.', $userId));

                return Command::FAILURE;
            }
            $users[] = $user;
        } else {
            $users = $this->userRepository->findAll();
        }

        if ($users === []) {
            $io->error('No users found.');

            return Command::FAILURE;
        }
        $userIds = array_map(static fn (User $user): int => (int) $user->getId(), $users);

        if ($clear) {
            $deleted = $this->entityManager->createQuery('DELETE FROM App\Entity\RecommendationEvent e')->execute();
            $io->note(sprintf('Cleared %d existing recommendation events.', $deleted));
        }

        $eventCounts = [
            'apply_created' => 0,
            'mission_liked' => 0,
            'mission_rated' => 0,
            'mission_ignored' => 0,
        ];

        for ($i = 0; $i < $count; ++$i) {
            $userIdPicked = $userIds[array_rand($userIds)];
            $missionIdPicked = $missionIds[array_rand($missionIds)];

            /** @var User $user */
            $user = $this->entityManager->getReference(User::class, $userIdPicked);
            /** @var MissionVolunteer $mission */
            $mission = $this->entityManager->getReference(MissionVolunteer::class, $missionIdPicked);

            $features = $this->recommendationService->buildTrainingFeatures($user, $mission);
            $affinity = (
                (($features['skills'] ?? 0.0) * 0.35)
                + (($features['geo'] ?? 0.0) * 0.25)
                + (($features['availability'] ?? 0.0) * 0.20)
                + (($features['history'] ?? 0.0) * 0.10)
                + (($features['social'] ?? 0.0) * 0.10)
            );

            $event = new RecommendationEvent();
            $event->setUser($user);
            $event->setMission($mission);
            $event->setCreatedAt($this->randomRecentDate());

            // Simulate behavior: high affinity tends to positive actions.
            if ($affinity >= 0.70) {
                $type = $this->pick(['apply_created', 'mission_rated', 'mission_liked'], [45, 35, 20]);
            } elseif ($affinity >= 0.45) {
                $type = $this->pick(['mission_liked', 'mission_rated', 'mission_ignored'], [45, 30, 25]);
            } else {
                $type = $this->pick(['mission_ignored', 'mission_liked', 'mission_rated'], [60, 25, 15]);
            }

            $event->setEventType($type);
            ++$eventCounts[$type];

            if ($type === 'mission_rated') {
                $note = $this->noteFromAffinity($affinity);
                $event->setSignalStrength($note / 5);
                $event->setMetadata(['note' => $note]);
            } elseif ($type === 'mission_ignored') {
                $event->setSignalStrength(0.2);
                $event->setMetadata([]);
            } elseif ($type === 'mission_liked') {
                $event->setSignalStrength(0.85);
                $event->setMetadata([]);
            } else {
                $event->setSignalStrength(1.0);
                $event->setMetadata([
                    'disponibilites' => $user->availabilityProfileAsArray(),
                ]);
            }

            $this->entityManager->persist($event);

            if (($i + 1) % 100 === 0) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
        }

        $this->entityManager->flush();

        $io->success(sprintf(
            'Seeded %d recommendation events. apply=%d liked=%d rated=%d ignored=%d',
            $count,
            $eventCounts['apply_created'],
            $eventCounts['mission_liked'],
            $eventCounts['mission_rated'],
            $eventCounts['mission_ignored']
        ));

        return Command::SUCCESS;
    }

    private function randomRecentDate(): \DateTime
    {
        $daysAgo = random_int(0, 30);
        $hoursAgo = random_int(0, 23);
        $minutesAgo = random_int(0, 59);

        return (new \DateTime())
            ->sub(new \DateInterval(sprintf('P%dDT%dH%dM', $daysAgo, $hoursAgo, $minutesAgo)));
    }

    private function noteFromAffinity(float $affinity): int
    {
        if ($affinity >= 0.75) {
            return random_int(4, 5);
        }

        if ($affinity >= 0.50) {
            return random_int(3, 4);
        }

        return random_int(1, 3);
    }

    /**
     * @param list<string> $values
     * @param list<int> $weights
     */
    private function pick(array $values, array $weights): string
    {
        $sum = (int) array_sum($weights);
        $roll = random_int(1, max(1, $sum));
        $acc = 0;

        foreach ($values as $index => $value) {
            $acc += (int) ($weights[$index] ?? 0);
            if ($roll <= $acc) {
                return (string) $value;
            }
        }

        $lastKey = array_key_last($values);
        if ($lastKey === null) {
            return '';
        }

        return (string) $values[$lastKey];
    }
}
