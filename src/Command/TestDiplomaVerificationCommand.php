<?php

namespace App\Command;

use App\Service\DocumentVerificationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-diploma',
    description: 'Test diploma verification with an image file',
)]
class TestDiplomaVerificationCommand extends Command
{
    public function __construct(
        private DocumentVerificationService $verificationService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('imagePath', InputArgument::REQUIRED, 'Path to the diploma image')
            ->addArgument('firstName', InputArgument::REQUIRED, 'First name to verify')
            ->addArgument('lastName', InputArgument::REQUIRED, 'Last name to verify')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $imagePath = $input->getArgument('imagePath');
        $firstName = $input->getArgument('firstName');
        $lastName = $input->getArgument('lastName');

        $io->title('Testing Diploma Verification');
        $io->section('Input Parameters');
        $io->table(
            ['Parameter', 'Value'],
            [
                ['Image Path', $imagePath],
                ['First Name', $firstName],
                ['Last Name', $lastName],
            ]
        );

        if (!file_exists($imagePath)) {
            $io->error('Image file does not exist: ' . $imagePath);
            return Command::FAILURE;
        }

        $io->section('Extracting Text from Image');
        $io->text('Calling Groq Vision API...');
        
        $extractedText = $this->verificationService->extractTextFromImage($imagePath);
        
        if (!$extractedText) {
            $io->error('Failed to extract text from image. Check the logs for details.');
            return Command::FAILURE;
        }

        $io->success('Text extracted successfully!');
        $io->section('Extracted Text');
        $io->text($extractedText);

        $io->section('Running Verification');
        $result = $this->verificationService->verifyDiploma($imagePath, $firstName, $lastName);
        
        $io->table(
            ['Result', 'Value'],
            [
                ['Verified', $result['verified'] ? 'YES' : 'NO'],
                ['Message', $result['message']],
            ]
        );

        if ($result['verified']) {
            $io->success('Diploma verification PASSED!');
            return Command::SUCCESS;
        } else {
            $io->error('Diploma verification FAILED!');
            return Command::FAILURE;
        }
    }
}
