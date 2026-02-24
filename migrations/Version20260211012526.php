<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260211012526 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE assignment (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, date_debut DATE DEFAULT NULL, date_fin DATE DEFAULT NULL, priorite VARCHAR(50) NOT NULL, statut VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, user_id INT NOT NULL, project_id INT NOT NULL, INDEX IDX_30C544BAA76ED395 (user_id), INDEX IDX_30C544BA166D1F9C (project_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE comment (id INT AUTO_INCREMENT NOT NULL, content LONGTEXT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, assignment_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_9474526CD19302F8 (assignment_id), INDEX IDX_9474526CA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE deck (id_deck INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, matiere VARCHAR(100) NOT NULL, niveau VARCHAR(50) NOT NULL, description LONGTEXT DEFAULT NULL, image VARCHAR(255) DEFAULT NULL, pdf VARCHAR(255) DEFAULT NULL, date_creation DATETIME NOT NULL, user_id INT NOT NULL, INDEX IDX_4FAC3637A76ED395 (user_id), PRIMARY KEY (id_deck)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE revision_flashcard (id_deck INT NOT NULL, id_flashcard INT NOT NULL, INDEX IDX_D1CD878C48C2A35 (id_deck), INDEX IDX_D1CD878F064A62E (id_flashcard), PRIMARY KEY (id_deck, id_flashcard)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE eval_mat (id INT AUTO_INCREMENT NOT NULL, matiere_id INT NOT NULL, evaluation_id INT NOT NULL, INDEX IDX_15147A50F46CD258 (matiere_id), INDEX IDX_15147A50456C5646 (evaluation_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE evaluation_matiere (id_eval INT AUTO_INCREMENT NOT NULL, score_eval DOUBLE PRECISION NOT NULL, note_maximale_eval DOUBLE PRECISION NOT NULL, date_evaluation DATETIME NOT NULL, duree_evaluation INT NOT NULL, priorite_e VARCHAR(50) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, user_id INT NOT NULL, INDEX IDX_B3074FC0A76ED395 (user_id), PRIMARY KEY (id_eval)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE flashcard (id_flashcard INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, question LONGTEXT NOT NULL, reponse LONGTEXT NOT NULL, description LONGTEXT DEFAULT NULL, niveau_difficulte INT NOT NULL, etat VARCHAR(20) NOT NULL, image VARCHAR(255) DEFAULT NULL, pdf VARCHAR(255) DEFAULT NULL, date_creation DATETIME NOT NULL, date_modification DATETIME DEFAULT NULL, id_deck INT NOT NULL, INDEX IDX_70511A09C48C2A35 (id_deck), PRIMARY KEY (id_flashcard)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE matiere (id_matiere INT AUTO_INCREMENT NOT NULL, nom_matiere VARCHAR(255) NOT NULL, coefficient_matiere DOUBLE PRECISION NOT NULL, section_matiere VARCHAR(255) NOT NULL, type_matiere VARCHAR(255) NOT NULL, heure_matiere DOUBLE PRECISION NOT NULL, code VARCHAR(10) NOT NULL, description LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, user_id INT NOT NULL, UNIQUE INDEX UNIQ_9014574A77153098 (code), INDEX IDX_9014574AA76ED395 (user_id), PRIMARY KEY (id_matiere)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE planning (id INT AUTO_INCREMENT NOT NULL, date_debut DATETIME NOT NULL, date_fin DATETIME NOT NULL, color VARCHAR(30) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, user_id INT NOT NULL, seance_id INT NOT NULL, INDEX IDX_D499BFF6A76ED395 (user_id), INDEX IDX_D499BFF6E3797A94 (seance_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE project (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, date_debut DATE NOT NULL, date_fin DATE NOT NULL, statut VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, user_id INT NOT NULL, INDEX IDX_2FB3D0EEA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE project_share (id INT AUTO_INCREMENT NOT NULL, role VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, project_id INT NOT NULL, shared_with_user_id INT NOT NULL, shared_by_user_id INT NOT NULL, INDEX IDX_FC5394C4166D1F9C (project_id), INDEX IDX_FC5394C442EBB09C (shared_with_user_id), INDEX IDX_FC5394C4A88FC4FB (shared_by_user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE seance (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, type_seance VARCHAR(50) DEFAULT NULL, description LONGTEXT DEFAULT NULL, partage_avec JSON DEFAULT NULL, statut VARCHAR(30) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, user_id INT NOT NULL, INDEX IDX_DF7DFD0EA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, username VARCHAR(50) NOT NULL, profile_pic VARCHAR(255) DEFAULT NULL, phone_number VARCHAR(20) DEFAULT NULL, gender VARCHAR(10) NOT NULL, bio LONGTEXT DEFAULT NULL, student_id VARCHAR(100) DEFAULT NULL, university VARCHAR(255) DEFAULT NULL, is_banned TINYINT NOT NULL, banned_at DATETIME DEFAULT NULL, ban_reason LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), UNIQUE INDEX UNIQ_IDENTIFIER_USERNAME (username), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user_settings (id INT AUTO_INCREMENT NOT NULL, study_level VARCHAR(100) DEFAULT NULL, weekly_goal INT DEFAULT NULL, interests JSON DEFAULT NULL, notification_enabled TINYINT NOT NULL, email_notifications TINYINT NOT NULL, theme_preference VARCHAR(20) NOT NULL, language VARCHAR(10) NOT NULL, user_id INT NOT NULL, UNIQUE INDEX UNIQ_5C844C5A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE assignment ADD CONSTRAINT FK_30C544BAA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE assignment ADD CONSTRAINT FK_30C544BA166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526CD19302F8 FOREIGN KEY (assignment_id) REFERENCES assignment (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526CA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE deck ADD CONSTRAINT FK_4FAC3637A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE revision_flashcard ADD CONSTRAINT FK_D1CD878C48C2A35 FOREIGN KEY (id_deck) REFERENCES deck (id_deck)');
        $this->addSql('ALTER TABLE revision_flashcard ADD CONSTRAINT FK_D1CD878F064A62E FOREIGN KEY (id_flashcard) REFERENCES flashcard (id_flashcard)');
        $this->addSql('ALTER TABLE eval_mat ADD CONSTRAINT FK_15147A50F46CD258 FOREIGN KEY (matiere_id) REFERENCES matiere (id_matiere) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE eval_mat ADD CONSTRAINT FK_15147A50456C5646 FOREIGN KEY (evaluation_id) REFERENCES evaluation_matiere (id_eval) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE evaluation_matiere ADD CONSTRAINT FK_B3074FC0A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE flashcard ADD CONSTRAINT FK_70511A09C48C2A35 FOREIGN KEY (id_deck) REFERENCES deck (id_deck) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE matiere ADD CONSTRAINT FK_9014574AA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE planning ADD CONSTRAINT FK_D499BFF6A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE planning ADD CONSTRAINT FK_D499BFF6E3797A94 FOREIGN KEY (seance_id) REFERENCES seance (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE project ADD CONSTRAINT FK_2FB3D0EEA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE project_share ADD CONSTRAINT FK_FC5394C4166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE project_share ADD CONSTRAINT FK_FC5394C442EBB09C FOREIGN KEY (shared_with_user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE project_share ADD CONSTRAINT FK_FC5394C4A88FC4FB FOREIGN KEY (shared_by_user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE seance ADD CONSTRAINT FK_DF7DFD0EA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_settings ADD CONSTRAINT FK_5C844C5A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE assignment DROP FOREIGN KEY FK_30C544BAA76ED395');
        $this->addSql('ALTER TABLE assignment DROP FOREIGN KEY FK_30C544BA166D1F9C');
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_9474526CD19302F8');
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_9474526CA76ED395');
        $this->addSql('ALTER TABLE deck DROP FOREIGN KEY FK_4FAC3637A76ED395');
        $this->addSql('ALTER TABLE revision_flashcard DROP FOREIGN KEY FK_D1CD878C48C2A35');
        $this->addSql('ALTER TABLE revision_flashcard DROP FOREIGN KEY FK_D1CD878F064A62E');
        $this->addSql('ALTER TABLE eval_mat DROP FOREIGN KEY FK_15147A50F46CD258');
        $this->addSql('ALTER TABLE eval_mat DROP FOREIGN KEY FK_15147A50456C5646');
        $this->addSql('ALTER TABLE evaluation_matiere DROP FOREIGN KEY FK_B3074FC0A76ED395');
        $this->addSql('ALTER TABLE flashcard DROP FOREIGN KEY FK_70511A09C48C2A35');
        $this->addSql('ALTER TABLE matiere DROP FOREIGN KEY FK_9014574AA76ED395');
        $this->addSql('ALTER TABLE planning DROP FOREIGN KEY FK_D499BFF6A76ED395');
        $this->addSql('ALTER TABLE planning DROP FOREIGN KEY FK_D499BFF6E3797A94');
        $this->addSql('ALTER TABLE project DROP FOREIGN KEY FK_2FB3D0EEA76ED395');
        $this->addSql('ALTER TABLE project_share DROP FOREIGN KEY FK_FC5394C4166D1F9C');
        $this->addSql('ALTER TABLE project_share DROP FOREIGN KEY FK_FC5394C442EBB09C');
        $this->addSql('ALTER TABLE project_share DROP FOREIGN KEY FK_FC5394C4A88FC4FB');
        $this->addSql('ALTER TABLE seance DROP FOREIGN KEY FK_DF7DFD0EA76ED395');
        $this->addSql('ALTER TABLE user_settings DROP FOREIGN KEY FK_5C844C5A76ED395');
        $this->addSql('DROP TABLE assignment');
        $this->addSql('DROP TABLE comment');
        $this->addSql('DROP TABLE deck');
        $this->addSql('DROP TABLE revision_flashcard');
        $this->addSql('DROP TABLE eval_mat');
        $this->addSql('DROP TABLE evaluation_matiere');
        $this->addSql('DROP TABLE flashcard');
        $this->addSql('DROP TABLE matiere');
        $this->addSql('DROP TABLE planning');
        $this->addSql('DROP TABLE project');
        $this->addSql('DROP TABLE project_share');
        $this->addSql('DROP TABLE seance');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP TABLE user_settings');
    }
}
