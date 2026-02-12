<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260212015000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE annonce (id INT AUTO_INCREMENT NOT NULL, titre_annonce VARCHAR(150) NOT NULL, description LONGTEXT NOT NULL, date_publication DATE NOT NULL, urgence VARCHAR(50) NOT NULL, etat_annonce VARCHAR(50) NOT NULL, donation_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_F65593E54DC1279C (donation_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE donation (id INT AUTO_INCREMENT NOT NULL, type_don VARCHAR(50) NOT NULL, quantite INT NOT NULL, date_donation DATE NOT NULL, statut VARCHAR(50) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE fiche (id INT AUTO_INCREMENT NOT NULL, poids DOUBLE PRECISION NOT NULL, taille DOUBLE PRECISION NOT NULL, grp_sanguin VARCHAR(10) NOT NULL, allergie VARCHAR(100) DEFAULT NULL, maladie_chronique VARCHAR(100) DEFAULT NULL, tension VARCHAR(20) NOT NULL, glycemie DOUBLE PRECISION NOT NULL, date DATE NOT NULL, libelle_maladie VARCHAR(100) NOT NULL, gravite VARCHAR(20) NOT NULL, recommandation LONGTEXT DEFAULT NULL, id_u_id INT NOT NULL, INDEX IDX_4C13CC786F858F92 (id_u_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE medicament (id INT AUTO_INCREMENT NOT NULL, nom_medicament VARCHAR(255) NOT NULL, categorie VARCHAR(50) NOT NULL, dosage VARCHAR(50) NOT NULL, forme VARCHAR(50) NOT NULL, date_expiration DATE NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE mission_like (id INT AUTO_INCREMENT NOT NULL, mission_id INT DEFAULT NULL, user_id INT DEFAULT NULL, INDEX IDX_8521ECBCBE6CAE90 (mission_id), INDEX IDX_8521ECBCA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE mission_rating (id INT AUTO_INCREMENT NOT NULL, note INT NOT NULL, mission_id INT DEFAULT NULL, user_id INT DEFAULT NULL, INDEX IDX_8071FC6CBE6CAE90 (mission_id), INDEX IDX_8071FC6CA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE mission_volunteer (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(50) NOT NULL, description VARCHAR(255) NOT NULL, lieu VARCHAR(255) NOT NULL, date_debut DATE NOT NULL, date_fin DATE NOT NULL, statut VARCHAR(50) NOT NULL, photo VARCHAR(255) DEFAULT NULL, user_id INT DEFAULT NULL, INDEX IDX_E3F529F2A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE ordonnance (id INT AUTO_INCREMENT NOT NULL, posologie VARCHAR(100) NOT NULL, frequence VARCHAR(100) NOT NULL, duree_traitement INT NOT NULL, id_u_id INT NOT NULL, id_fiche_id INT NOT NULL, INDEX IDX_924B326C6F858F92 (id_u_id), INDEX IDX_924B326C8F89C99D (id_fiche_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE ord_med (id_ord INT NOT NULL, id_medicament INT NOT NULL, INDEX IDX_4BFEE31175B80360 (id_ord), INDEX IDX_4BFEE3113F16F2ED (id_medicament), PRIMARY KEY(id_ord, id_medicament)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE password_reset_token (id INT AUTO_INCREMENT NOT NULL, token VARCHAR(64) NOT NULL, expires_at DATETIME NOT NULL, created_at DATETIME NOT NULL, used_at DATETIME DEFAULT NULL, user_id INT NOT NULL, UNIQUE INDEX UNIQ_6B7BA4B65F37A13B (token), INDEX IDX_6B7BA4B6A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE question (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, contenu LONGTEXT NOT NULL, date_creation DATETIME NOT NULL, auteur_id INT NOT NULL, INDEX IDX_B6F7494E60BB6FE6 (auteur_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE reponse (id INT AUTO_INCREMENT NOT NULL, contenu LONGTEXT NOT NULL, date_creation DATETIME NOT NULL, auteur_id INT NOT NULL, question_id INT NOT NULL, INDEX IDX_5FB6DEC760BB6FE6 (auteur_id), INDEX IDX_5FB6DEC71E27F6BF (question_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE sponsor (id INT AUTO_INCREMENT NOT NULL, nom_societe VARCHAR(255) NOT NULL, contact_email VARCHAR(255) NOT NULL, logo VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE sponsor_mission_volunteer (sponsor_id INT NOT NULL, mission_volunteer_id INT NOT NULL, INDEX IDX_9462BD4312F7FB51 (sponsor_id), INDEX IDX_9462BD435990FB15 (mission_volunteer_id), PRIMARY KEY(sponsor_id, mission_volunteer_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, nom VARCHAR(255) NOT NULL, prenom VARCHAR(255) NOT NULL, adresse VARCHAR(255) DEFAULT NULL, telephone VARCHAR(255) DEFAULT NULL, role VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE volunteer (id INT AUTO_INCREMENT NOT NULL, motivation LONGTEXT NOT NULL, disponibilites JSON NOT NULL, telephone VARCHAR(20) NOT NULL, statut VARCHAR(50) NOT NULL, user_id INT NOT NULL, mission_id INT NOT NULL, INDEX IDX_5140DEDBA76ED395 (user_id), INDEX IDX_5140DEDBBE6CAE90 (mission_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE annonce ADD CONSTRAINT FK_F65593E54DC1279C FOREIGN KEY (donation_id) REFERENCES donation (id)');
        $this->addSql('ALTER TABLE fiche ADD CONSTRAINT FK_4C13CC786F858F92 FOREIGN KEY (id_u_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE mission_like ADD CONSTRAINT FK_8521ECBCBE6CAE90 FOREIGN KEY (mission_id) REFERENCES mission_volunteer (id)');
        $this->addSql('ALTER TABLE mission_like ADD CONSTRAINT FK_8521ECBCA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE mission_rating ADD CONSTRAINT FK_8071FC6CBE6CAE90 FOREIGN KEY (mission_id) REFERENCES mission_volunteer (id)');
        $this->addSql('ALTER TABLE mission_rating ADD CONSTRAINT FK_8071FC6CA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE mission_volunteer ADD CONSTRAINT FK_E3F529F2A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE ordonnance ADD CONSTRAINT FK_924B326C6F858F92 FOREIGN KEY (id_u_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE ordonnance ADD CONSTRAINT FK_924B326C8F89C99D FOREIGN KEY (id_fiche_id) REFERENCES fiche (id)');
        $this->addSql('ALTER TABLE ord_med ADD CONSTRAINT FK_4BFEE31175B80360 FOREIGN KEY (id_ord) REFERENCES ordonnance (id)');
        $this->addSql('ALTER TABLE ord_med ADD CONSTRAINT FK_4BFEE3113F16F2ED FOREIGN KEY (id_medicament) REFERENCES medicament (id)');
        $this->addSql('ALTER TABLE password_reset_token ADD CONSTRAINT FK_6B7BA4B6A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE question ADD CONSTRAINT FK_B6F7494E60BB6FE6 FOREIGN KEY (auteur_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE reponse ADD CONSTRAINT FK_5FB6DEC760BB6FE6 FOREIGN KEY (auteur_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE reponse ADD CONSTRAINT FK_5FB6DEC71E27F6BF FOREIGN KEY (question_id) REFERENCES question (id)');
        $this->addSql('ALTER TABLE sponsor_mission_volunteer ADD CONSTRAINT FK_9462BD4312F7FB51 FOREIGN KEY (sponsor_id) REFERENCES sponsor (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE sponsor_mission_volunteer ADD CONSTRAINT FK_9462BD435990FB15 FOREIGN KEY (mission_volunteer_id) REFERENCES mission_volunteer (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE volunteer ADD CONSTRAINT FK_5140DEDBA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE volunteer ADD CONSTRAINT FK_5140DEDBBE6CAE90 FOREIGN KEY (mission_id) REFERENCES mission_volunteer (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE annonce DROP FOREIGN KEY FK_F65593E54DC1279C');
        $this->addSql('ALTER TABLE fiche DROP FOREIGN KEY FK_4C13CC786F858F92');
        $this->addSql('ALTER TABLE mission_like DROP FOREIGN KEY FK_8521ECBCBE6CAE90');
        $this->addSql('ALTER TABLE mission_like DROP FOREIGN KEY FK_8521ECBCA76ED395');
        $this->addSql('ALTER TABLE mission_rating DROP FOREIGN KEY FK_8071FC6CBE6CAE90');
        $this->addSql('ALTER TABLE mission_rating DROP FOREIGN KEY FK_8071FC6CA76ED395');
        $this->addSql('ALTER TABLE mission_volunteer DROP FOREIGN KEY FK_E3F529F2A76ED395');
        $this->addSql('ALTER TABLE ordonnance DROP FOREIGN KEY FK_924B326C6F858F92');
        $this->addSql('ALTER TABLE ordonnance DROP FOREIGN KEY FK_924B326C8F89C99D');
        $this->addSql('ALTER TABLE ord_med DROP FOREIGN KEY FK_4BFEE31175B80360');
        $this->addSql('ALTER TABLE ord_med DROP FOREIGN KEY FK_4BFEE3113F16F2ED');
        $this->addSql('ALTER TABLE password_reset_token DROP FOREIGN KEY FK_6B7BA4B6A76ED395');
        $this->addSql('ALTER TABLE question DROP FOREIGN KEY FK_B6F7494E60BB6FE6');
        $this->addSql('ALTER TABLE reponse DROP FOREIGN KEY FK_5FB6DEC760BB6FE6');
        $this->addSql('ALTER TABLE reponse DROP FOREIGN KEY FK_5FB6DEC71E27F6BF');
        $this->addSql('ALTER TABLE sponsor_mission_volunteer DROP FOREIGN KEY FK_9462BD4312F7FB51');
        $this->addSql('ALTER TABLE sponsor_mission_volunteer DROP FOREIGN KEY FK_9462BD435990FB15');
        $this->addSql('ALTER TABLE volunteer DROP FOREIGN KEY FK_5140DEDBA76ED395');
        $this->addSql('ALTER TABLE volunteer DROP FOREIGN KEY FK_5140DEDBBE6CAE90');
        $this->addSql('DROP TABLE annonce');
        $this->addSql('DROP TABLE donation');
        $this->addSql('DROP TABLE fiche');
        $this->addSql('DROP TABLE medicament');
        $this->addSql('DROP TABLE mission_like');
        $this->addSql('DROP TABLE mission_rating');
        $this->addSql('DROP TABLE mission_volunteer');
        $this->addSql('DROP TABLE ordonnance');
        $this->addSql('DROP TABLE ord_med');
        $this->addSql('DROP TABLE password_reset_token');
        $this->addSql('DROP TABLE question');
        $this->addSql('DROP TABLE reponse');
        $this->addSql('DROP TABLE sponsor');
        $this->addSql('DROP TABLE sponsor_mission_volunteer');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP TABLE volunteer');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
