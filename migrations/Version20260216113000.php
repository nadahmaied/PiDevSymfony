<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260216113000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'AI matching fields for users/missions and recommendation event log';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` ADD skills_profile LONGTEXT DEFAULT NULL, ADD interests_profile LONGTEXT DEFAULT NULL, ADD availability_profile LONGTEXT DEFAULT NULL, ADD preferred_city VARCHAR(255) DEFAULT NULL, ADD action_radius_km INT DEFAULT NULL, ADD latitude DOUBLE PRECISION DEFAULT NULL, ADD longitude DOUBLE PRECISION DEFAULT NULL, ADD recommendation_weights JSON NOT NULL');
        $this->addSql('ALTER TABLE mission_volunteer ADD required_skills LONGTEXT DEFAULT NULL, ADD thematic_tags LONGTEXT DEFAULT NULL, ADD critical_periods LONGTEXT DEFAULT NULL, ADD target_audience VARCHAR(100) DEFAULT NULL, ADD difficulty_level INT DEFAULT NULL, ADD urgency_level INT DEFAULT NULL, ADD latitude DOUBLE PRECISION DEFAULT NULL, ADD longitude DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('CREATE TABLE recommendation_event (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, mission_id INT NOT NULL, event_type VARCHAR(50) NOT NULL, signal_strength DOUBLE PRECISION NOT NULL, metadata JSON NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_1B5B5247A76ED395 (user_id), INDEX IDX_1B5B5247BE6CAE90 (mission_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE recommendation_event ADD CONSTRAINT FK_1B5B5247A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE recommendation_event ADD CONSTRAINT FK_1B5B5247BE6CAE90 FOREIGN KEY (mission_id) REFERENCES mission_volunteer (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE recommendation_event DROP FOREIGN KEY FK_1B5B5247A76ED395');
        $this->addSql('ALTER TABLE recommendation_event DROP FOREIGN KEY FK_1B5B5247BE6CAE90');
        $this->addSql('DROP TABLE recommendation_event');
        $this->addSql('ALTER TABLE mission_volunteer DROP required_skills, DROP thematic_tags, DROP critical_periods, DROP target_audience, DROP difficulty_level, DROP urgency_level, DROP latitude, DROP longitude');
        $this->addSql('ALTER TABLE `user` DROP skills_profile, DROP interests_profile, DROP availability_profile, DROP preferred_city, DROP action_radius_km, DROP latitude, DROP longitude, DROP recommendation_weights');
    }
}
