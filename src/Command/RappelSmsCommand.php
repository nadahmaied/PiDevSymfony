<?php

namespace App\Command;

use App\Repository\RdvRepository;
use App\Service\SmsService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:rappel-sms',
    description: 'Envoie les SMS de rappel pour les RDV de demain'
)]
class RappelSmsCommand extends Command
{
    public function __construct(
        private RdvRepository $repo,
        private SmsService    $sms
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $demain = new \DateTime('+1 day');
        $rdvs   = $this->repo->findByDate($demain);
        $count  = 0;

        foreach ($rdvs as $rdv) {
            if ($rdv->getStatut() === 'Annulé') continue;

            $ok = $this->sms->sendRappel(
                '+21629254485',
                $rdv->getMedecin(),
                $rdv->getDate()->format('d/m/Y'),
                $rdv->getHdebut()->format('H:i')
            );

            if ($ok) {
                $output->writeln("✅ SMS envoyé → {$rdv->getMedecin()}");
                $count++;
            } else {
                $output->writeln("❌ Echec → {$rdv->getMedecin()}");
            }
        }

        $output->writeln("Total : {$count} SMS envoyé(s).");
        return Command::SUCCESS;
    }
}