<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260217203213 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE coin_transaction DROP FOREIGN KEY fk_coin_tx_user_final');
        $this->addSql('ALTER TABLE revision_flashcard DROP FOREIGN KEY FK_D1CD878F064A62E');
        $this->addSql('DROP INDEX idx_revision_flashcard ON revision_flashcard');
        $this->addSql('CREATE INDEX IDX_D1CD878F064A62E ON revision_flashcard (id_flashcard)');
        $this->addSql('ALTER TABLE revision_flashcard ADD CONSTRAINT FK_D1CD878F064A62E FOREIGN KEY (id_flashcard) REFERENCES flashcard (id_flashcard)');
        $this->addSql('ALTER TABLE evaluation_matiere DROP FOREIGN KEY FK_EVALUATION_USER');
        $this->addSql('ALTER TABLE evaluation_matiere CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('DROP INDEX idx_evaluation_user ON evaluation_matiere');
        $this->addSql('CREATE INDEX IDX_B3074FC0A76ED395 ON evaluation_matiere (user_id)');
        $this->addSql('ALTER TABLE evaluation_matiere ADD CONSTRAINT FK_EVALUATION_USER FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE flashcard DROP FOREIGN KEY FK_70511A09E62C7D3');
        $this->addSql('ALTER TABLE flashcard CHANGE titre titre VARCHAR(255) DEFAULT NULL, CHANGE niveau_difficulte niveau_difficulte INT DEFAULT NULL, CHANGE etat etat VARCHAR(20) DEFAULT NULL, CHANGE date_creation date_creation DATETIME NOT NULL');
        $this->addSql('DROP INDEX idx_70511a09e62c7d3 ON flashcard');
        $this->addSql('CREATE INDEX IDX_70511A09C48C2A35 ON flashcard (id_deck)');
        $this->addSql('ALTER TABLE flashcard ADD CONSTRAINT FK_70511A09E62C7D3 FOREIGN KEY (id_deck) REFERENCES deck (id_deck) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE matiere DROP FOREIGN KEY FK_MATIERE_USER');
        $this->addSql('ALTER TABLE matiere CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('DROP INDEX idx_matiere_user ON matiere');
        $this->addSql('CREATE INDEX IDX_9014574AA76ED395 ON matiere (user_id)');
        $this->addSql('ALTER TABLE matiere ADD CONSTRAINT FK_MATIERE_USER FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE pet DROP FOREIGN KEY fk_pet_user_rel_final');
        $this->addSql('ALTER TABLE pet CHANGE name name VARCHAR(100) NOT NULL, CHANGE hunger hunger INT NOT NULL, CHANGE coins_spent coins_spent INT NOT NULL');
        $this->addSql('ALTER TABLE planning DROP FOREIGN KEY FK_planning_seance');
        $this->addSql('ALTER TABLE planning DROP FOREIGN KEY FK_planning_user');
        $this->addSql('ALTER TABLE planning DROP FOREIGN KEY FK_planning_seance');
        $this->addSql('ALTER TABLE planning DROP FOREIGN KEY FK_planning_user');
        $this->addSql('ALTER TABLE planning DROP created_at, DROP updated_at, CHANGE color color VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE planning ADD CONSTRAINT FK_D499BFF6E3797A94 FOREIGN KEY (seance_id) REFERENCES seance (id)');
        $this->addSql('ALTER TABLE planning ADD CONSTRAINT FK_D499BFF6A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('DROP INDEX idx_planning_seance ON planning');
        $this->addSql('CREATE INDEX IDX_D499BFF6E3797A94 ON planning (seance_id)');
        $this->addSql('DROP INDEX idx_planning_user ON planning');
        $this->addSql('CREATE INDEX IDX_D499BFF6A76ED395 ON planning (user_id)');
        $this->addSql('ALTER TABLE planning ADD CONSTRAINT FK_planning_seance FOREIGN KEY (seance_id) REFERENCES seance (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE planning ADD CONSTRAINT FK_planning_user FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE project DROP FOREIGN KEY FK_PROJECT_USER');
        $this->addSql('ALTER TABLE project CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('DROP INDEX idx_project_user ON project');
        $this->addSql('CREATE INDEX IDX_2FB3D0EEA76ED395 ON project (user_id)');
        $this->addSql('ALTER TABLE project ADD CONSTRAINT FK_PROJECT_USER FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('DROP INDEX idx_recommendation_stress_level ON recommendation_stress');
        $this->addSql('DROP INDEX idx_recommendation_stress_active ON recommendation_stress');
        $this->addSql('ALTER TABLE recommendation_stress CHANGE content content LONGTEXT NOT NULL, CHANGE is_active is_active TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE seance DROP FOREIGN KEY FK_seance_type_seance');
        $this->addSql('ALTER TABLE seance DROP FOREIGN KEY FK_seance_user');
        $this->addSql('ALTER TABLE seance DROP FOREIGN KEY FK_seance_type_seance');
        $this->addSql('ALTER TABLE seance DROP type_seance, DROP partage_avec, DROP statut, CHANGE type_seance_id type_seance_id INT NOT NULL, CHANGE description description LONGTEXT NOT NULL, CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE seance ADD CONSTRAINT FK_DF7DFD0E57BF3B84 FOREIGN KEY (type_seance_id) REFERENCES type_seance (id)');
        $this->addSql('DROP INDEX idx_user_id ON seance');
        $this->addSql('CREATE INDEX IDX_DF7DFD0EA76ED395 ON seance (user_id)');
        $this->addSql('DROP INDEX fk_seance_type_seance ON seance');
        $this->addSql('CREATE INDEX IDX_DF7DFD0E57BF3B84 ON seance (type_seance_id)');
        $this->addSql('ALTER TABLE seance ADD CONSTRAINT FK_seance_user FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE seance ADD CONSTRAINT FK_seance_type_seance FOREIGN KEY (type_seance_id) REFERENCES type_seance (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE user CHANGE profile_pic profile_pic VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE coin_transaction ADD CONSTRAINT fk_coin_tx_user_final FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE evaluation_matiere DROP FOREIGN KEY FK_B3074FC0A76ED395');
        $this->addSql('ALTER TABLE evaluation_matiere CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('DROP INDEX idx_b3074fc0a76ed395 ON evaluation_matiere');
        $this->addSql('CREATE INDEX IDX_EVALUATION_USER ON evaluation_matiere (user_id)');
        $this->addSql('ALTER TABLE evaluation_matiere ADD CONSTRAINT FK_B3074FC0A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE flashcard DROP FOREIGN KEY FK_70511A09C48C2A35');
        $this->addSql('ALTER TABLE flashcard CHANGE titre titre VARCHAR(255) NOT NULL, CHANGE niveau_difficulte niveau_difficulte INT NOT NULL, CHANGE etat etat VARCHAR(20) NOT NULL, CHANGE date_creation date_creation DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('DROP INDEX idx_70511a09c48c2a35 ON flashcard');
        $this->addSql('CREATE INDEX IDX_70511A09E62C7D3 ON flashcard (id_deck)');
        $this->addSql('ALTER TABLE flashcard ADD CONSTRAINT FK_70511A09C48C2A35 FOREIGN KEY (id_deck) REFERENCES deck (id_deck) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE matiere DROP FOREIGN KEY FK_9014574AA76ED395');
        $this->addSql('ALTER TABLE matiere CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('DROP INDEX idx_9014574aa76ed395 ON matiere');
        $this->addSql('CREATE INDEX IDX_MATIERE_USER ON matiere (user_id)');
        $this->addSql('ALTER TABLE matiere ADD CONSTRAINT FK_9014574AA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE pet CHANGE name name VARCHAR(255) NOT NULL, CHANGE hunger hunger INT DEFAULT 100 NOT NULL, CHANGE coins_spent coins_spent INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE pet ADD CONSTRAINT fk_pet_user_rel_final FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE planning DROP FOREIGN KEY FK_D499BFF6E3797A94');
        $this->addSql('ALTER TABLE planning DROP FOREIGN KEY FK_D499BFF6A76ED395');
        $this->addSql('ALTER TABLE planning DROP FOREIGN KEY FK_D499BFF6E3797A94');
        $this->addSql('ALTER TABLE planning DROP FOREIGN KEY FK_D499BFF6A76ED395');
        $this->addSql('ALTER TABLE planning ADD created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, ADD updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE color color VARCHAR(30) DEFAULT NULL');
        $this->addSql('ALTER TABLE planning ADD CONSTRAINT FK_planning_seance FOREIGN KEY (seance_id) REFERENCES seance (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE planning ADD CONSTRAINT FK_planning_user FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('DROP INDEX idx_d499bff6e3797a94 ON planning');
        $this->addSql('CREATE INDEX IDX_planning_seance ON planning (seance_id)');
        $this->addSql('DROP INDEX idx_d499bff6a76ed395 ON planning');
        $this->addSql('CREATE INDEX IDX_planning_user ON planning (user_id)');
        $this->addSql('ALTER TABLE planning ADD CONSTRAINT FK_D499BFF6E3797A94 FOREIGN KEY (seance_id) REFERENCES seance (id)');
        $this->addSql('ALTER TABLE planning ADD CONSTRAINT FK_D499BFF6A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE project DROP FOREIGN KEY FK_2FB3D0EEA76ED395');
        $this->addSql('ALTER TABLE project CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('DROP INDEX idx_2fb3d0eea76ed395 ON project');
        $this->addSql('CREATE INDEX IDX_PROJECT_USER ON project (user_id)');
        $this->addSql('ALTER TABLE project ADD CONSTRAINT FK_2FB3D0EEA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE recommendation_stress CHANGE content content TEXT NOT NULL, CHANGE is_active is_active TINYINT(1) DEFAULT 1 NOT NULL');
        $this->addSql('CREATE INDEX idx_recommendation_stress_level ON recommendation_stress (level)');
        $this->addSql('CREATE INDEX idx_recommendation_stress_active ON recommendation_stress (is_active)');
        $this->addSql('ALTER TABLE revision_flashcard DROP FOREIGN KEY FK_D1CD878F064A62E');
        $this->addSql('DROP INDEX idx_d1cd878f064a62e ON revision_flashcard');
        $this->addSql('CREATE INDEX idx_revision_flashcard ON revision_flashcard (id_flashcard)');
        $this->addSql('ALTER TABLE revision_flashcard ADD CONSTRAINT FK_D1CD878F064A62E FOREIGN KEY (id_flashcard) REFERENCES flashcard (id_flashcard)');
        $this->addSql('ALTER TABLE seance DROP FOREIGN KEY FK_DF7DFD0E57BF3B84');
        $this->addSql('ALTER TABLE seance DROP FOREIGN KEY FK_DF7DFD0EA76ED395');
        $this->addSql('ALTER TABLE seance DROP FOREIGN KEY FK_DF7DFD0E57BF3B84');
        $this->addSql('ALTER TABLE seance ADD type_seance VARCHAR(50) DEFAULT NULL, ADD partage_avec LONGTEXT DEFAULT NULL, ADD statut VARCHAR(30) DEFAULT NULL, CHANGE type_seance_id type_seance_id INT DEFAULT NULL, CHANGE description description LONGTEXT DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE seance ADD CONSTRAINT FK_seance_type_seance FOREIGN KEY (type_seance_id) REFERENCES type_seance (id) ON DELETE SET NULL');
        $this->addSql('DROP INDEX idx_df7dfd0ea76ed395 ON seance');
        $this->addSql('CREATE INDEX IDX_user_id ON seance (user_id)');
        $this->addSql('DROP INDEX idx_df7dfd0e57bf3b84 ON seance');
        $this->addSql('CREATE INDEX FK_seance_type_seance ON seance (type_seance_id)');
        $this->addSql('ALTER TABLE seance ADD CONSTRAINT FK_DF7DFD0EA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE seance ADD CONSTRAINT FK_DF7DFD0E57BF3B84 FOREIGN KEY (type_seance_id) REFERENCES type_seance (id)');
        $this->addSql('ALTER TABLE `user` CHANGE profile_pic profile_pic VARCHAR(500) DEFAULT NULL');
    }
}
