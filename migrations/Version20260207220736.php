<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260207220736 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->dropForeignKeyIfExists('assignment', 'FK_ASSIGNMENT_USER');
        $this->addSql('ALTER TABLE assignment CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('DROP INDEX IF EXISTS idx_assignment_user ON assignment');
        $this->createIndexIfNotExists('assignment', 'IDX_30C544BAA76ED395', 'user_id');
        $this->addSql('ALTER TABLE assignment ADD CONSTRAINT `FK_ASSIGNMENT_USER` FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->dropForeignKeyIfExists('deck', 'FK_DECK_USER');
        $this->addSql('ALTER TABLE deck CHANGE date_creation date_creation DATETIME NOT NULL');
        $this->addSql('DROP INDEX IF EXISTS idx_deck_user ON deck');
        $this->createIndexIfNotExists('deck', 'IDX_4FAC3637A76ED395', 'user_id');
        $this->addSql('ALTER TABLE deck ADD CONSTRAINT `FK_DECK_USER` FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->dropForeignKeyIfExists('revision_flashcard', 'fk_revision_deck');
        $this->dropForeignKeyIfExists('revision_flashcard', 'fk_revision_flashcard');
        $this->dropForeignKeyIfExists('revision_flashcard', 'fk_revision_deck');
        $this->dropForeignKeyIfExists('revision_flashcard', 'fk_revision_flashcard');
        $this->addSql('ALTER TABLE revision_flashcard ADD CONSTRAINT FK_D1CD878C48C2A35 FOREIGN KEY (id_deck) REFERENCES deck (id_deck)');
        $this->addSql('ALTER TABLE revision_flashcard ADD CONSTRAINT FK_D1CD878F064A62E FOREIGN KEY (id_flashcard) REFERENCES flashcard (id_flashcard)');
        $this->addSql('DROP INDEX IF EXISTS idx_revision_deck ON revision_flashcard');
        $this->createIndexIfNotExists('revision_flashcard', 'IDX_D1CD878C48C2A35', 'id_deck');
        $this->addSql('DROP INDEX IF EXISTS idx_revision_flashcard ON revision_flashcard');
        $this->createIndexIfNotExists('revision_flashcard', 'IDX_D1CD878F064A62E', 'id_flashcard');
        $this->addSql('ALTER TABLE revision_flashcard ADD CONSTRAINT `fk_revision_deck` FOREIGN KEY (id_deck) REFERENCES deck (id_deck) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE revision_flashcard ADD CONSTRAINT `fk_revision_flashcard` FOREIGN KEY (id_flashcard) REFERENCES flashcard (id_flashcard) ON DELETE CASCADE');
        $this->dropForeignKeyIfExists('evaluation_matiere', 'FK_EVALUATION_USER');
        $this->addSql('ALTER TABLE evaluation_matiere CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('DROP INDEX IF EXISTS idx_evaluation_user ON evaluation_matiere');
        $this->createIndexIfNotExists('evaluation_matiere', 'IDX_B3074FC0A76ED395', 'user_id');
        $this->addSql('ALTER TABLE evaluation_matiere ADD CONSTRAINT `FK_EVALUATION_USER` FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->dropForeignKeyIfExists('flashcard', 'FK_70511A09E62C7D3');
        $this->addSql('ALTER TABLE flashcard CHANGE date_creation date_creation DATETIME NOT NULL');
        $this->addSql('DROP INDEX IF EXISTS idx_70511a09e62c7d3 ON flashcard');
        $this->createIndexIfNotExists('flashcard', 'IDX_70511A09C48C2A35', 'id_deck');
        $this->addSql('ALTER TABLE flashcard ADD CONSTRAINT `FK_70511A09E62C7D3` FOREIGN KEY (id_deck) REFERENCES deck (id_deck) ON DELETE CASCADE');
        $this->dropForeignKeyIfExists('matiere', 'FK_MATIERE_USER');
        $this->addSql('ALTER TABLE matiere CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('DROP INDEX IF EXISTS idx_matiere_user ON matiere');
        $this->createIndexIfNotExists('matiere', 'IDX_9014574AA76ED395', 'user_id');
        $this->addSql('ALTER TABLE matiere ADD CONSTRAINT `FK_MATIERE_USER` FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->dropForeignKeyIfExists('planning', 'FK_planning_seance');
        $this->dropForeignKeyIfExists('planning', 'FK_planning_user');
        $this->addSql('ALTER TABLE planning CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('DROP INDEX IF EXISTS idx_planning_user ON planning');
        $this->createIndexIfNotExists('planning', 'IDX_D499BFF6A76ED395', 'user_id');
        $this->addSql('DROP INDEX IF EXISTS idx_planning_seance ON planning');
        $this->createIndexIfNotExists('planning', 'IDX_D499BFF6E3797A94', 'seance_id');
        $this->addSql('ALTER TABLE planning ADD CONSTRAINT `FK_planning_seance` FOREIGN KEY (seance_id) REFERENCES seance (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE planning ADD CONSTRAINT `FK_planning_user` FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->dropForeignKeyIfExists('project', 'FK_PROJECT_USER');
        $this->addSql('ALTER TABLE project CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('DROP INDEX IF EXISTS idx_project_user ON project');
        $this->createIndexIfNotExists('project', 'IDX_2FB3D0EEA76ED395', 'user_id');
        $this->addSql('ALTER TABLE project ADD CONSTRAINT `FK_PROJECT_USER` FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->dropForeignKeyIfExists('recommendation_stress', 'FK_REC_QUIZ');
        $this->dropForeignKeyIfExists('recommendation_stress', 'FK_REC_QUIZ');
        $this->addSql('ALTER TABLE recommendation_stress ADD CONSTRAINT FK_79005D567C6BD396 FOREIGN KEY (quiz_stress_id) REFERENCES quiz_stress (id)');
        $this->addSql('DROP INDEX IF EXISTS idx_rec_quiz ON recommendation_stress');
        $this->createIndexIfNotExists('recommendation_stress', 'IDX_79005D567C6BD396', 'quiz_stress_id');
        $this->addSql('ALTER TABLE recommendation_stress ADD CONSTRAINT `FK_REC_QUIZ` FOREIGN KEY (quiz_stress_id) REFERENCES quiz_stress (id) ON DELETE CASCADE');
        $this->dropForeignKeyIfExists('seance', 'FK_seance_user');
        $this->addSql('ALTER TABLE seance CHANGE partage_avec partage_avec JSON DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('DROP INDEX IF EXISTS idx_user_id ON seance');
        $this->createIndexIfNotExists('seance', 'IDX_DF7DFD0EA76ED395', 'user_id');
        $this->addSql('ALTER TABLE seance ADD CONSTRAINT `FK_seance_user` FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    private function dropForeignKeyIfExists(string $table, string $fk): void
    {
        $this->addSql(
            "SET @fk := (SELECT CONSTRAINT_NAME FROM information_schema.REFERENTIAL_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE()
              AND TABLE_NAME = '{$table}'
              AND CONSTRAINT_NAME = '{$fk}'
            LIMIT 1)"
        );
        $this->addSql(
            "SET @sql := IF(@fk IS NULL, 'SELECT 1', CONCAT('ALTER TABLE `{$table}` DROP FOREIGN KEY `', @fk, '`'))"
        );
        $this->addSql('PREPARE stmt FROM @sql');
        $this->addSql('EXECUTE stmt');
        $this->addSql('DEALLOCATE PREPARE stmt');
    }

    private function createIndexIfNotExists(string $table, string $index, string $columns): void
    {
        $this->addSql(
            "SET @idx := (SELECT INDEX_NAME FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = '{$table}'
              AND INDEX_NAME = '{$index}'
            LIMIT 1)"
        );
        $this->addSql(
            "SET @sql := IF(@idx IS NULL, CONCAT('CREATE INDEX `{$index}` ON `{$table}` ({$columns})'), 'SELECT 1')"
        );
        $this->addSql('PREPARE stmt FROM @sql');
        $this->addSql('EXECUTE stmt');
        $this->addSql('DEALLOCATE PREPARE stmt');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE assignment DROP FOREIGN KEY FK_30C544BAA76ED395');
        $this->addSql('ALTER TABLE assignment CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('DROP INDEX idx_30c544baa76ed395 ON assignment');
        $this->addSql('CREATE INDEX IDX_ASSIGNMENT_USER ON assignment (user_id)');
        $this->addSql('ALTER TABLE assignment ADD CONSTRAINT FK_30C544BAA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE deck DROP FOREIGN KEY FK_4FAC3637A76ED395');
        $this->addSql('ALTER TABLE deck CHANGE date_creation date_creation DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('DROP INDEX idx_4fac3637a76ed395 ON deck');
        $this->addSql('CREATE INDEX IDX_DECK_USER ON deck (user_id)');
        $this->addSql('ALTER TABLE deck ADD CONSTRAINT FK_4FAC3637A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE evaluation_matiere DROP FOREIGN KEY FK_B3074FC0A76ED395');
        $this->addSql('ALTER TABLE evaluation_matiere CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('DROP INDEX idx_b3074fc0a76ed395 ON evaluation_matiere');
        $this->addSql('CREATE INDEX IDX_EVALUATION_USER ON evaluation_matiere (user_id)');
        $this->addSql('ALTER TABLE evaluation_matiere ADD CONSTRAINT FK_B3074FC0A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE flashcard DROP FOREIGN KEY FK_70511A09C48C2A35');
        $this->addSql('ALTER TABLE flashcard CHANGE date_creation date_creation DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('DROP INDEX idx_70511a09c48c2a35 ON flashcard');
        $this->addSql('CREATE INDEX IDX_70511A09E62C7D3 ON flashcard (id_deck)');
        $this->addSql('ALTER TABLE flashcard ADD CONSTRAINT FK_70511A09C48C2A35 FOREIGN KEY (id_deck) REFERENCES deck (id_deck) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE matiere DROP FOREIGN KEY FK_9014574AA76ED395');
        $this->addSql('ALTER TABLE matiere CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('DROP INDEX idx_9014574aa76ed395 ON matiere');
        $this->addSql('CREATE INDEX IDX_MATIERE_USER ON matiere (user_id)');
        $this->addSql('ALTER TABLE matiere ADD CONSTRAINT FK_9014574AA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE planning DROP FOREIGN KEY FK_D499BFF6A76ED395');
        $this->addSql('ALTER TABLE planning DROP FOREIGN KEY FK_D499BFF6E3797A94');
        $this->addSql('ALTER TABLE planning CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('DROP INDEX idx_d499bff6a76ed395 ON planning');
        $this->addSql('CREATE INDEX IDX_planning_user ON planning (user_id)');
        $this->addSql('DROP INDEX idx_d499bff6e3797a94 ON planning');
        $this->addSql('CREATE INDEX IDX_planning_seance ON planning (seance_id)');
        $this->addSql('ALTER TABLE planning ADD CONSTRAINT FK_D499BFF6A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE planning ADD CONSTRAINT FK_D499BFF6E3797A94 FOREIGN KEY (seance_id) REFERENCES seance (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE project DROP FOREIGN KEY FK_2FB3D0EEA76ED395');
        $this->addSql('ALTER TABLE project CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('DROP INDEX idx_2fb3d0eea76ed395 ON project');
        $this->addSql('CREATE INDEX IDX_PROJECT_USER ON project (user_id)');
        $this->addSql('ALTER TABLE project ADD CONSTRAINT FK_2FB3D0EEA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE recommendation_stress DROP FOREIGN KEY FK_79005D567C6BD396');
        $this->addSql('ALTER TABLE recommendation_stress DROP FOREIGN KEY FK_79005D567C6BD396');
        $this->addSql('ALTER TABLE recommendation_stress ADD CONSTRAINT `FK_REC_QUIZ` FOREIGN KEY (quiz_stress_id) REFERENCES quiz_stress (id) ON DELETE CASCADE');
        $this->addSql('DROP INDEX idx_79005d567c6bd396 ON recommendation_stress');
        $this->addSql('CREATE INDEX IDX_REC_QUIZ ON recommendation_stress (quiz_stress_id)');
        $this->addSql('ALTER TABLE recommendation_stress ADD CONSTRAINT FK_79005D567C6BD396 FOREIGN KEY (quiz_stress_id) REFERENCES quiz_stress (id)');
        $this->addSql('ALTER TABLE revision_flashcard DROP FOREIGN KEY FK_D1CD878C48C2A35');
        $this->addSql('ALTER TABLE revision_flashcard DROP FOREIGN KEY FK_D1CD878F064A62E');
        $this->addSql('ALTER TABLE revision_flashcard DROP FOREIGN KEY FK_D1CD878C48C2A35');
        $this->addSql('ALTER TABLE revision_flashcard DROP FOREIGN KEY FK_D1CD878F064A62E');
        $this->addSql('ALTER TABLE revision_flashcard ADD CONSTRAINT `fk_revision_deck` FOREIGN KEY (id_deck) REFERENCES deck (id_deck) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE revision_flashcard ADD CONSTRAINT `fk_revision_flashcard` FOREIGN KEY (id_flashcard) REFERENCES flashcard (id_flashcard) ON DELETE CASCADE');
        $this->addSql('DROP INDEX idx_d1cd878c48c2a35 ON revision_flashcard');
        $this->addSql('CREATE INDEX idx_revision_deck ON revision_flashcard (id_deck)');
        $this->addSql('DROP INDEX idx_d1cd878f064a62e ON revision_flashcard');
        $this->addSql('CREATE INDEX idx_revision_flashcard ON revision_flashcard (id_flashcard)');
        $this->addSql('ALTER TABLE revision_flashcard ADD CONSTRAINT FK_D1CD878C48C2A35 FOREIGN KEY (id_deck) REFERENCES deck (id_deck)');
        $this->addSql('ALTER TABLE revision_flashcard ADD CONSTRAINT FK_D1CD878F064A62E FOREIGN KEY (id_flashcard) REFERENCES flashcard (id_flashcard)');
        $this->addSql('ALTER TABLE seance DROP FOREIGN KEY FK_DF7DFD0EA76ED395');
        $this->addSql('ALTER TABLE seance CHANGE partage_avec partage_avec LONGTEXT DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('DROP INDEX idx_df7dfd0ea76ed395 ON seance');
        $this->addSql('CREATE INDEX IDX_user_id ON seance (user_id)');
        $this->addSql('ALTER TABLE seance ADD CONSTRAINT FK_DF7DFD0EA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
    }
}
