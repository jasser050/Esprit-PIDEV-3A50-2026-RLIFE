<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260203000742 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, username VARCHAR(50) NOT NULL, profile_pic VARCHAR(255) DEFAULT NULL, phone_number VARCHAR(20) DEFAULT NULL, gender VARCHAR(10) NOT NULL, bio LONGTEXT DEFAULT NULL, student_id VARCHAR(100) DEFAULT NULL, university VARCHAR(255) DEFAULT NULL, is_banned TINYINT NOT NULL, banned_at DATETIME DEFAULT NULL, ban_reason LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), UNIQUE INDEX UNIQ_IDENTIFIER_USERNAME (username), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user_career (id INT AUTO_INCREMENT NOT NULL, career_name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, priority INT DEFAULT NULL, is_primary TINYINT NOT NULL, user_id INT NOT NULL, INDEX IDX_D70977B9A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user_settings (id INT AUTO_INCREMENT NOT NULL, study_level VARCHAR(100) DEFAULT NULL, weekly_goal INT DEFAULT NULL, interests JSON DEFAULT NULL, notification_enabled TINYINT NOT NULL, email_notifications TINYINT NOT NULL, theme_preference VARCHAR(20) NOT NULL, language VARCHAR(10) NOT NULL, user_id INT NOT NULL, UNIQUE INDEX UNIQ_5C844C5A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE user_career ADD CONSTRAINT FK_D70977B9A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE user_settings ADD CONSTRAINT FK_5C844C5A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_career DROP FOREIGN KEY FK_D70977B9A76ED395');
        $this->addSql('ALTER TABLE user_settings DROP FOREIGN KEY FK_5C844C5A76ED395');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP TABLE user_career');
        $this->addSql('DROP TABLE user_settings');
    }
}
