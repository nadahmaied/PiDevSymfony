<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260210211815 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE annonce (id INT AUTO_INCREMENT NOT NULL, titre_annonce VARCHAR(150) NOT NULL, description LONGTEXT NOT NULL, date_publication DATE NOT NULL, urgence VARCHAR(50) NOT NULL, etat_annonce VARCHAR(50) NOT NULL, donation_id INT NOT NULL, UNIQUE INDEX UNIQ_F65593E54DC1279C (donation_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE donation (id INT AUTO_INCREMENT NOT NULL, type_don VARCHAR(50) NOT NULL, quantite INT NOT NULL, date_donation DATE NOT NULL, statut VARCHAR(50) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE annonce ADD CONSTRAINT FK_F65593E54DC1279C FOREIGN KEY (donation_id) REFERENCES donation (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE annonce DROP FOREIGN KEY FK_F65593E54DC1279C');
        $this->addSql('DROP TABLE annonce');
        $this->addSql('DROP TABLE donation');
    }
}
