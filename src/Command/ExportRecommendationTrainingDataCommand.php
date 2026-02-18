<?php

namespace App\Command;

use App\Entity\RecommendationEvent;
use App\Repository\RecommendationEventRepository;
use App\Service\MissionRecommendationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:ml:export-recommendation-data',
    description: 'Export recommendation events as a ML training dataset (CSV).',
)]
class ExportRecommendationTrainingDataCommand extends Command
{
    public function __construct(
        private readonly RecommendationEventRepository $eventRepository,
        private readonly MissionRecommendationService $recommendationService,
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('output', null, InputOption::VALUE_REQUIRED, 'CSV output path')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Max events to export', '5000');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $outputPath = (string) ($input->getOption('output') ?: ($this->projectDir . '/var/ml/recommendation_training.csv'));
        $limit = max(100, (int) $input->getOption('limit'));

        $events = $this->eventRepository->findForTraining($limit);
        if ($events === []) {
            $io->warning('No recommendation events found. Generate interactions before exporting training data.');

            return Command::SUCCESS;
        }

        $directory = dirname($outputPath);
        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            $io->error('Unable to create output directory: ' . $directory);

            return Command::FAILURE;
        }

        $file = fopen($outputPath, 'wb');
        if ($file === false) {
            $io->error('Unable to open output file: ' . $outputPath);

            return Command::FAILURE;
        }

        $headers = ['skills', 'geo', 'availability', 'history', 'social', 'urgency', 'difficulty', 'duration_days', 'label'];
        fputcsv($file, $headers);

        $rows = 0;
        foreach ($events as $event) {
            if (!$event instanceof RecommendationEvent) {
                continue;
            }

            $label = $this->labelFromEvent($event);
            if ($label === null) {
                continue;
            }

            $user = $event->getUser();
            $mission = $event->getMission();
            if ($user === null || $mission === null) {
                continue;
            }

            $features = $this->recommendationService->buildTrainingFeatures($user, $mission);
            $row = [
                $features['skills'] ?? 0.0,
                $features['geo'] ?? 0.0,
                $features['availability'] ?? 0.0,
                $features['history'] ?? 0.0,
                $features['social'] ?? 0.0,
                $features['urgency'] ?? 0.0,
                $features['difficulty'] ?? 0.0,
                $features['duration_days'] ?? 0.0,
                $label,
            ];

            fputcsv($file, $row);
            ++$rows;
        }

        fclose($file);

        $io->success(sprintf('Training dataset exported: %s (%d rows)', $outputPath, $rows));

        return Command::SUCCESS;
    }

    private function labelFromEvent(RecommendationEvent $event): ?int
    {
        $type = (string) $event->getEventType();
        if ($type === 'apply_created' || $type === 'mission_liked') {
            return 1;
        }

        if ($type === 'mission_ignored') {
            return 0;
        }

        if ($type === 'mission_rated') {
            $metadata = $event->getMetadata();
            $note = isset($metadata['note']) ? (int) $metadata['note'] : null;
            if ($note !== null) {
                if ($note >= 4) {
                    return 1;
                }
                if ($note <= 2) {
                    return 0;
                }
            }

            return ((float) $event->getSignalStrength()) >= 0.6 ? 1 : 0;
        }

        return null;
    }
}
