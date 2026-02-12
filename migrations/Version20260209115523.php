<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260209115523 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE sponsor (id INT AUTO_INCREMENT NOT NULL, nom_societe VARCHAR(255) NOT NULL, contact_email VARCHAR(255) NOT NULL, logo VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE sponsor_mission_volunteer (sponsor_id INT NOT NULL, mission_volunteer_id INT NOT NULL, INDEX IDX_9462BD4312F7FB51 (sponsor_id), INDEX IDX_9462BD435990FB15 (mission_volunteer_id), PRIMARY KEY(sponsor_id, mission_volunteer_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE sponsor_mission_volunteer ADD CONSTRAINT FK_9462BD4312F7FB51 FOREIGN KEY (sponsor_id) REFERENCES sponsor (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE sponsor_mission_volunteer ADD CONSTRAINT FK_9462BD435990FB15 FOREIGN KEY (mission_volunteer_id) REFERENCES mission_volunteer (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE sponsor_mission_volunteer DROP FOREIGN KEY FK_9462BD4312F7FB51');
        $this->addSql('ALTER TABLE sponsor_mission_volunteer DROP FOREIGN KEY FK_9462BD435990FB15');
        $this->addSql('DROP TABLE sponsor');
        $this->addSql('DROP TABLE sponsor_mission_volunteer');
    }
}
