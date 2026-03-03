<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260301090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Pet v2: personality/stats/xp/rarity + pet_event + pet_achievement tables.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE pet ADD personality VARCHAR(30) NOT NULL DEFAULT 'calm', ADD rarity VARCHAR(20) NOT NULL DEFAULT 'common', ADD xp INT NOT NULL DEFAULT 0, ADD happiness INT NOT NULL DEFAULT 70, ADD energy INT NOT NULL DEFAULT 80, ADD health INT NOT NULL DEFAULT 100, ADD last_interaction_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', ADD last_event_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', ADD state_flags JSON DEFAULT NULL");
        $this->addSql('UPDATE pet SET last_interaction_at = last_hunger_at WHERE last_interaction_at IS NULL');

        $this->addSql('CREATE TABLE pet_event (id INT AUTO_INCREMENT NOT NULL, pet_id INT NOT NULL, event_type VARCHAR(60) NOT NULL, rarity VARCHAR(30) NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, effects JSON DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_72764BE95C47B8A3 (pet_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE pet_event ADD CONSTRAINT FK_72764BE95C47B8A3 FOREIGN KEY (pet_id) REFERENCES pet (id) ON DELETE CASCADE');

        $this->addSql('CREATE TABLE pet_achievement (id INT AUTO_INCREMENT NOT NULL, pet_id INT NOT NULL, code VARCHAR(100) NOT NULL, title VARCHAR(160) NOT NULL, description LONGTEXT NOT NULL, reward_coins INT NOT NULL DEFAULT 0, reward_xp INT NOT NULL DEFAULT 0, unlocked_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_B3801E5A5C47B8A3 (pet_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE pet_achievement ADD CONSTRAINT FK_B3801E5A5C47B8A3 FOREIGN KEY (pet_id) REFERENCES pet (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE pet_event DROP FOREIGN KEY FK_72764BE95C47B8A3');
        $this->addSql('DROP TABLE pet_event');
        $this->addSql('ALTER TABLE pet_achievement DROP FOREIGN KEY FK_B3801E5A5C47B8A3');
        $this->addSql('DROP TABLE pet_achievement');
        $this->addSql('ALTER TABLE pet DROP personality, DROP rarity, DROP xp, DROP happiness, DROP energy, DROP health, DROP last_interaction_at, DROP last_event_at, DROP state_flags');
    }
}

