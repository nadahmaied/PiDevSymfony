<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260303113000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add AI moderation fields to forum question and response';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE question ADD moderation_status VARCHAR(20) NOT NULL DEFAULT 'safe', ADD moderation_reason LONGTEXT DEFAULT NULL, ADD toxicity_score DOUBLE PRECISION DEFAULT NULL, ADD sensitive_score DOUBLE PRECISION DEFAULT NULL, ADD medical_risk_score DOUBLE PRECISION DEFAULT NULL, ADD flagged_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', ADD reviewed_by_id INT DEFAULT NULL, ADD reviewed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'");
        $this->addSql("ALTER TABLE question ADD CONSTRAINT FK_B6F7494E919BFA22 FOREIGN KEY (reviewed_by_id) REFERENCES user (id)");
        $this->addSql('CREATE INDEX IDX_B6F7494E919BFA22 ON question (reviewed_by_id)');
        $this->addSql('CREATE INDEX IDX_B6F7494EDAFEA131 ON question (moderation_status)');

        $this->addSql("ALTER TABLE reponse ADD moderation_status VARCHAR(20) NOT NULL DEFAULT 'safe', ADD moderation_reason LONGTEXT DEFAULT NULL, ADD toxicity_score DOUBLE PRECISION DEFAULT NULL, ADD sensitive_score DOUBLE PRECISION DEFAULT NULL, ADD medical_risk_score DOUBLE PRECISION DEFAULT NULL, ADD flagged_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', ADD reviewed_by_id INT DEFAULT NULL, ADD reviewed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'");
        $this->addSql("ALTER TABLE reponse ADD CONSTRAINT FK_4B89032A919BFA22 FOREIGN KEY (reviewed_by_id) REFERENCES user (id)");
        $this->addSql('CREATE INDEX IDX_4B89032A919BFA22 ON reponse (reviewed_by_id)');
        $this->addSql('CREATE INDEX IDX_4B89032ADAFEA131 ON reponse (moderation_status)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE question DROP FOREIGN KEY FK_B6F7494E919BFA22');
        $this->addSql('DROP INDEX IDX_B6F7494E919BFA22 ON question');
        $this->addSql('DROP INDEX IDX_B6F7494EDAFEA131 ON question');
        $this->addSql('ALTER TABLE question DROP moderation_status, DROP moderation_reason, DROP toxicity_score, DROP sensitive_score, DROP medical_risk_score, DROP flagged_at, DROP reviewed_by_id, DROP reviewed_at');

        $this->addSql('ALTER TABLE reponse DROP FOREIGN KEY FK_4B89032A919BFA22');
        $this->addSql('DROP INDEX IDX_4B89032A919BFA22 ON reponse');
        $this->addSql('DROP INDEX IDX_4B89032ADAFEA131 ON reponse');
        $this->addSql('ALTER TABLE reponse DROP moderation_status, DROP moderation_reason, DROP toxicity_score, DROP sensitive_score, DROP medical_risk_score, DROP flagged_at, DROP reviewed_by_id, DROP reviewed_at');
    }
}

