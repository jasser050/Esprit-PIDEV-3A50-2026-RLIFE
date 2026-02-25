<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260225103000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add last_hunger_at on pet to support time-based hunger progression.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE pet ADD last_hunger_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('UPDATE pet SET last_hunger_at = created_at WHERE last_hunger_at IS NULL');
        $this->addSql('ALTER TABLE pet MODIFY last_hunger_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE pet DROP last_hunger_at');
    }
}

