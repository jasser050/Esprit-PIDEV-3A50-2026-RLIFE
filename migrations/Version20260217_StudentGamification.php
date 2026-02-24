<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260217_StudentGamification extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create student_gamification table with points, level, streak, badges, deck_id';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE student_gamification (
                id INT AUTO_INCREMENT NOT NULL,
                user_id INT NOT NULL,
                deck_id INT DEFAULT NULL,
                points INT NOT NULL DEFAULT 0,
                level INT NOT NULL DEFAULT 1,
                streak INT NOT NULL DEFAULT 0,
                last_activity DATE DEFAULT NULL,
                badges JSON DEFAULT NULL,
                total_decks_completed INT NOT NULL DEFAULT 0,
                total_correct_answers INT NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE INDEX UNIQ_USER (user_id),
                INDEX IDX_DECK (deck_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        ');

        $this->addSql('
            ALTER TABLE student_gamification
                ADD CONSTRAINT FK_GAMIFICATION_USER
                    FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE,
                ADD CONSTRAINT FK_GAMIFICATION_DECK
                    FOREIGN KEY (deck_id) REFERENCES deck (id_deck) ON DELETE SET NULL
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE student_gamification DROP FOREIGN KEY FK_GAMIFICATION_USER');
        $this->addSql('ALTER TABLE student_gamification DROP FOREIGN KEY FK_GAMIFICATION_DECK');
        $this->addSql('DROP TABLE student_gamification');
    }
}
