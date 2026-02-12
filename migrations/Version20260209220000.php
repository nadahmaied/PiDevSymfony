<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260209220000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create rdv table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE rdv (id INT AUTO_INCREMENT NOT NULL, date DATE NOT NULL, hdebut TIME NOT NULL, hfin TIME NOT NULL, statut VARCHAR(255) NOT NULL, motif VARCHAR(255) NOT NULL, medecin VARCHAR(255) NOT NULL, message VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE rdv');
    }
}
