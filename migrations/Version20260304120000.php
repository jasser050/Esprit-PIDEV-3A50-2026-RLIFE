<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260304120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add user ownership to type_seance and backfill from existing seances.';
    }

    public function up(Schema $schema): void
    {
        // 1) Add column nullable for backfill
        $this->addSql('ALTER TABLE type_seance ADD user_id INT DEFAULT NULL');

        // 2) Backfill from seances (pick a user if multiple)
        $this->addSql(
            'UPDATE type_seance ts
             JOIN (
                 SELECT type_seance_id, MIN(user_id) AS user_id
                 FROM seance
                 WHERE type_seance_id IS NOT NULL
                 GROUP BY type_seance_id
             ) s ON s.type_seance_id = ts.id
             SET ts.user_id = s.user_id
             WHERE ts.user_id IS NULL'
        );

        // 3) Any remaining types -> assign to first user
        $this->addSql('UPDATE type_seance SET user_id = (SELECT MIN(id) FROM user) WHERE user_id IS NULL');

        // 4) Add FK + unique per user
        $this->addSql('ALTER TABLE type_seance ADD CONSTRAINT FK_TYPE_SEANCE_USER FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_TYPE_SEANCE_USER ON type_seance (user_id)');
        $this->addSql('ALTER TABLE type_seance DROP INDEX uniq_type_seance_name');
        $this->addSql('CREATE UNIQUE INDEX uniq_type_seance_user_name ON type_seance (user_id, name)');

        // 5) Make user_id NOT NULL
        $this->addSql('ALTER TABLE type_seance MODIFY user_id INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE type_seance DROP FOREIGN KEY FK_TYPE_SEANCE_USER');
        $this->addSql('DROP INDEX IDX_TYPE_SEANCE_USER ON type_seance');
        $this->addSql('DROP INDEX uniq_type_seance_user_name ON type_seance');
        $this->addSql('ALTER TABLE type_seance ADD CONSTRAINT uniq_type_seance_name UNIQUE (name)');
        $this->addSql('ALTER TABLE type_seance DROP user_id');
    }
}

