<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260209172402 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE mission_like (id INT AUTO_INCREMENT NOT NULL, mission_id INT DEFAULT NULL, user_id INT DEFAULT NULL, INDEX IDX_8521ECBCBE6CAE90 (mission_id), INDEX IDX_8521ECBCA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE mission_rating (id INT AUTO_INCREMENT NOT NULL, note INT NOT NULL, mission_id INT DEFAULT NULL, user_id INT DEFAULT NULL, INDEX IDX_8071FC6CBE6CAE90 (mission_id), INDEX IDX_8071FC6CA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE mission_like ADD CONSTRAINT FK_8521ECBCBE6CAE90 FOREIGN KEY (mission_id) REFERENCES mission_volunteer (id)');
        $this->addSql('ALTER TABLE mission_like ADD CONSTRAINT FK_8521ECBCA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE mission_rating ADD CONSTRAINT FK_8071FC6CBE6CAE90 FOREIGN KEY (mission_id) REFERENCES mission_volunteer (id)');
        $this->addSql('ALTER TABLE mission_rating ADD CONSTRAINT FK_8071FC6CA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE mission_like DROP FOREIGN KEY FK_8521ECBCBE6CAE90');
        $this->addSql('ALTER TABLE mission_like DROP FOREIGN KEY FK_8521ECBCA76ED395');
        $this->addSql('ALTER TABLE mission_rating DROP FOREIGN KEY FK_8071FC6CBE6CAE90');
        $this->addSql('ALTER TABLE mission_rating DROP FOREIGN KEY FK_8071FC6CA76ED395');
        $this->addSql('DROP TABLE mission_like');
        $this->addSql('DROP TABLE mission_rating');
    }
}
