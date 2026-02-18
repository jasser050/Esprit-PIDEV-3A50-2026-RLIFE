<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration : Ajout des colonnes manquantes dans rating
 *           + Création des tables student_points et student_badge (gamification)
 */
final class Version20260217000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout comment, tags, criteria dans rating + tables gamification student_points et student_badge';
    }

    public function up(Schema $schema): void
    {
        // ══════════════════════════════════════════════════════════
        //  1. Colonnes manquantes dans la table rating
        // ══════════════════════════════════════════════════════════
        $this->addSql('ALTER TABLE rating
            ADD comment       LONGTEXT     DEFAULT NULL,
            ADD tags          JSON         DEFAULT NULL,
            ADD clarity       TINYINT(1)   DEFAULT NULL,
            ADD completeness  TINYINT(1)   DEFAULT NULL,
            ADD difficulty    TINYINT(1)   DEFAULT NULL,
            ADD usefulness    TINYINT(1)   DEFAULT NULL
        ');

        // ══════════════════════════════════════════════════════════
        //  2. Table student_points  (points + level + streak)
        // ══════════════════════════════════════════════════════════
        $this->addSql('CREATE TABLE student_points (
            id                  INT          NOT NULL AUTO_INCREMENT,
            user_id             INT          NOT NULL,
            points              INT          NOT NULL DEFAULT 0,
            level               INT          NOT NULL DEFAULT 1,
            streak              INT          NOT NULL DEFAULT 0,
            last_activity_at    DATETIME     DEFAULT NULL,
            created_at          DATETIME     NOT NULL,
            updated_at          DATETIME     DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY unique_user_points (user_id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE student_points
            ADD CONSTRAINT FK_SP_user
                FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');

        // ══════════════════════════════════════════════════════════
        //  3. Table student_badge  (badges gagnés)
        // ══════════════════════════════════════════════════════════
        $this->addSql('CREATE TABLE student_badge (
            id          INT          NOT NULL AUTO_INCREMENT,
            user_id     INT          NOT NULL,
            badge_slug  VARCHAR(50)  NOT NULL COMMENT "master-deck, streak-7, top-rater ...",
            earned_at   DATETIME     NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY unique_user_badge (user_id, badge_slug)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE student_badge
            ADD CONSTRAINT FK_SB_user
                FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // Supprimer les colonnes ajoutées dans rating
        $this->addSql('ALTER TABLE rating
            DROP COLUMN comment,
            DROP COLUMN tags,
            DROP COLUMN clarity,
            DROP COLUMN completeness,
            DROP COLUMN difficulty,
            DROP COLUMN usefulness
        ');

        // Supprimer les tables gamification
        $this->addSql('ALTER TABLE student_badge DROP FOREIGN KEY FK_SB_user');
        $this->addSql('DROP TABLE student_badge');

        $this->addSql('ALTER TABLE student_points DROP FOREIGN KEY FK_SP_user');
        $this->addSql('DROP TABLE student_points');
    }
}
