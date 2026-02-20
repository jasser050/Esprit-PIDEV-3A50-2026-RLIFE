<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260219000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add star_name and star_type columns to user_settings for Galaxy AI feature';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_settings ADD star_name VARCHAR(150) DEFAULT NULL, ADD star_type VARCHAR(50) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_settings DROP COLUMN star_name, DROP COLUMN star_type');
    }
}
