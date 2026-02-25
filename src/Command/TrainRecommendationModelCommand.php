<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

#[AsCommand(
    name: 'app:ml:train-recommendation-model',
    description: 'Train recommendation ML model with Python trainer script.',
)]
class TrainRecommendationModelCommand extends Command
{
    public function __construct(
        private readonly string $projectDir,
        private readonly string $modelPath,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('python-bin', null, InputOption::VALUE_REQUIRED, 'Python executable', 'python')
            ->addOption('dataset', null, InputOption::VALUE_REQUIRED, 'Dataset CSV path')
            ->addOption('model-output', null, InputOption::VALUE_REQUIRED, 'Model output path');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $pythonBin = (string) $input->getOption('python-bin');
        $dataset = (string) ($input->getOption('dataset') ?: ($this->projectDir . '/var/ml/recommendation_training.csv'));
        $modelOutput = (string) ($input->getOption('model-output') ?: $this->modelPath);
        $scriptPath = $this->projectDir . '/ml/train.py';

        if (!is_file($scriptPath)) {
            $io->error('Trainer script not found: ' . $scriptPath);

            return Command::FAILURE;
        }

        if (!is_file($dataset)) {
            $io->error('Dataset not found. Run app:ml:export-recommendation-data first.');

            return Command::FAILURE;
        }

        $directory = dirname($modelOutput);
        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            $io->error('Unable to create model output directory: ' . $directory);

            return Command::FAILURE;
        }

        $process = new Process([
            $pythonBin,
            $scriptPath,
            '--input',
            $dataset,
            '--output',
            $modelOutput,
        ]);
        $process->setTimeout(300);
        $process->run();

        if (!$process->isSuccessful()) {
            $io->error('Training failed.');
            $io->writeln($process->getErrorOutput() ?: $process->getOutput());
            $io->note('If Python is missing, install it and retry with --python-bin.');

            return Command::FAILURE;
        }

        $io->success('Model trained successfully: ' . $modelOutput);
        $io->writeln(trim($process->getOutput()));

        return Command::SUCCESS;
    }
}
