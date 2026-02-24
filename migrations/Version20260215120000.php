<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration pour la table flashcard_translation.
 *
 * Crée la table pour stocker les traductions multilingues des flashcards.
 *
 * IMPORTANT: Renommer ce fichier en Version20260215120000.php
 * et la classe en Version20260215120000 avant d'exécuter.
 */
final class Version20260215120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Créer la table flashcard_translation pour les traductions multilingues';
    }

    public function up(Schema $schema): void
    {
        // Créer la table flashcard_translation
        $this->addSql('
            CREATE TABLE flashcard_translation (
                id INT AUTO_INCREMENT NOT NULL,
                flashcard_id INT NOT NULL,
                language VARCHAR(5) NOT NULL,
                titre VARCHAR(255) NOT NULL,
                question TEXT NOT NULL,
                reponse TEXT NOT NULL,
                description TEXT DEFAULT NULL,
                difficulty_level SMALLINT DEFAULT NULL,
                translator_notes TEXT DEFAULT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME DEFAULT NULL,
                quality_score SMALLINT DEFAULT NULL,
                is_verified TINYINT(1) DEFAULT 0 NOT NULL,
                INDEX idx_flashcard_lang (flashcard_id, language),
                INDEX idx_language (language),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        ');

        // Ajouter la contrainte de clé étrangère
        // IMPORTANT: 'id_flashcard' doit correspondre au nom de colonne dans votre table flashcard
        $this->addSql('
            ALTER TABLE flashcard_translation
            ADD CONSTRAINT FK_flashcard_translation_flashcard
            FOREIGN KEY (flashcard_id)
            REFERENCES flashcard (id_flashcard)
            ON DELETE CASCADE
        ');

        // Index unique pour éviter les doublons (une traduction par flashcard par langue)
        $this->addSql('
            CREATE UNIQUE INDEX UNIQ_flashcard_translation_unique
            ON flashcard_translation (flashcard_id, language)
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE flashcard_translation DROP FOREIGN KEY FK_flashcard_translation_flashcard');
        $this->addSql('DROP TABLE flashcard_translation');
    }
}
