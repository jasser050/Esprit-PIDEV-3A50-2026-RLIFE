<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260207235602 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create coping_session table only (safe, do not touch other tables)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS coping_session (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT DEFAULT NULL,
            tool_key VARCHAR(50) NOT NULL,
            tool_name VARCHAR(120) NOT NULL,
            duration_seconds INT NOT NULL,
            started_at DATETIME NOT NULL,
            finished_at DATETIME DEFAULT NULL,
            actual_seconds INT DEFAULT NULL,
            status VARCHAR(20) NOT NULL,
            notes LONGTEXT DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME DEFAULT NULL,
            INDEX idx_coping_tool_key (tool_key),
            INDEX idx_coping_status (status),
            INDEX IDX_COPING_USER (user_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // ✅ FK safe
        $this->addSql('ALTER TABLE coping_session
            ADD CONSTRAINT FK_COPING_USER FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE coping_session DROP FOREIGN KEY FK_COPING_USER');
        $this->addSql('DROP TABLE IF EXISTS coping_session');
    }
}
