<?php

namespace App\Tests\Service;

use App\Entity\Annonce;
use App\Service\AnnonceManager;
use PHPUnit\Framework\TestCase;

final class AnnonceManagerTest extends TestCase
{
    public function testValidAnnonce(): void
    {
        $annonce = new Annonce();
        $annonce->setTitreAnnonce('Besoin urgent de sang');
        $annonce->setDescription('Nous recherchons des donneurs de sang pour une intervention urgente.');
        $annonce->setUrgence('moyenne');
        $annonce->setEtatAnnonce('active');

        $manager = new AnnonceManager();

        $this->assertTrue($manager->validate($annonce));
    }

    public function testAnnonceWithoutTitle(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $annonce = new Annonce();
        $annonce->setDescription('Une annonce sans titre ne doit pas être valide.');
        $annonce->setUrgence('faible');
        $annonce->setEtatAnnonce('active');

        $manager = new AnnonceManager();
        $manager->validate($annonce);
    }

    public function testAnnonceWithInvalidUrgence(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $annonce = new Annonce();
        $annonce->setTitreAnnonce('Titre valide');
        $annonce->setDescription('Description valide pour tester une urgence invalide.');
        $annonce->setUrgence('urgente'); // valeur non autorisée
        $annonce->setEtatAnnonce('active');

        $manager = new AnnonceManager();
        $manager->validate($annonce);
    }

    public function testAnnonceWithPastDatePublication(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $annonce = new Annonce();
        $annonce->setTitreAnnonce('Titre avec date passée');
        $annonce->setDescription('Description quelconque.');
        $annonce->setUrgence('faible');
        $annonce->setEtatAnnonce('active');
        $annonce->setDatePublication(new \DateTimeImmutable('yesterday'));

        $manager = new AnnonceManager();
        $manager->validate($annonce);
    }
}

