<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260205110357 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create well_being table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs

        $this->addSql('CREATE TABLE well_being (
            id INT AUTO_INCREMENT NOT NULL,
            entryDate_well DATETIME NOT NULL,
            mood_well VARCHAR(50) NOT NULL,
            stressLevel_well INT NOT NULL,
            energyLevel_well INT NOT NULL,
            sleepHours_well DOUBLE PRECISION NOT NULL,
            note_well LONGTEXT DEFAULT NULL,
            createdAt_well DATETIME NOT NULL,
            updatedAt_well DATETIME DEFAULT NULL,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE well_being');
    }
}
