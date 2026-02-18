<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260211014826 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE question_stress ADD CONSTRAINT FK_754FDCA4853CD175 FOREIGN KEY (quiz_id) REFERENCES quiz_stress (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE quiz_stress CHANGE answers answers JSON DEFAULT NULL, CHANGE title title VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE question_stress DROP FOREIGN KEY FK_754FDCA4853CD175');
        $this->addSql('ALTER TABLE quiz_stress CHANGE title title VARCHAR(255) NOT NULL, CHANGE answers answers JSON NOT NULL');
    }
}
