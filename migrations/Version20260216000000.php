<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add email verification fields to User table
 */
final class Version20260216000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add email verification fields (isVerified, verificationToken, verificationTokenExpiresAt) to User table';
    }

    public function up(Schema $schema): void
    {
        // Add verification fields
        $this->addSql('ALTER TABLE `user` ADD is_verified TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE `user` ADD verification_token VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE `user` ADD verification_token_expires_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        
        // Create index on verification_token for faster lookups
        $this->addSql('CREATE INDEX IDX_verification_token ON `user` (verification_token)');
    }

    public function down(Schema $schema): void
    {
        // Remove index first
        $this->addSql('DROP INDEX IDX_verification_token ON `user`');
        
        // Remove verification fields
        $this->addSql('ALTER TABLE `user` DROP is_verified');
        $this->addSql('ALTER TABLE `user` DROP verification_token');
        $this->addSql('ALTER TABLE `user` DROP verification_token_expires_at');
    }
}
