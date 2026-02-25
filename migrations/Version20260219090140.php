<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260219090140 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Change Annonce-Donation relationship from OneToOne to OneToMany';
    }

    public function up(Schema $schema): void
    {
        // Drop the old foreign key constraint
        $this->addSql('ALTER TABLE annonce DROP FOREIGN KEY FK_F65593E54DC1279C');
        
        // Drop the donation_id column from annonce table
        $this->addSql('ALTER TABLE annonce DROP INDEX UNIQ_F65593E54DC1279C');
        $this->addSql('ALTER TABLE annonce DROP COLUMN donation_id');
        
        // Add annonce_id column to donation table
        $this->addSql('ALTER TABLE donation ADD annonce_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_31E581A0F8697D13 ON donation (annonce_id)');
        $this->addSql('ALTER TABLE donation ADD CONSTRAINT FK_31E581A0F8697D13 FOREIGN KEY (annonce_id) REFERENCES annonce (id)');
    }

    public function down(Schema $schema): void
    {
        // Drop the new foreign key constraint
        $this->addSql('ALTER TABLE donation DROP FOREIGN KEY FK_31E581A0F8697D13');
        $this->addSql('DROP INDEX IDX_31E581A0F8697D13 ON donation');
        $this->addSql('ALTER TABLE donation DROP COLUMN annonce_id');
        
        // Restore the old structure
        $this->addSql('ALTER TABLE annonce ADD donation_id INT NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F65593E54DC1279C ON annonce (donation_id)');
        $this->addSql('ALTER TABLE annonce ADD CONSTRAINT FK_F65593E54DC1279C FOREIGN KEY (donation_id) REFERENCES donation (id)');
    }
}
