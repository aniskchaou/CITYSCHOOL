<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260503110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create school table with subscription and free-trial lifecycle fields';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
CREATE TABLE IF NOT EXISTS school (
    id INT AUTO_INCREMENT NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    address VARCHAR(255) DEFAULT NULL,
    country VARCHAR(100) DEFAULT NULL,
    timezone VARCHAR(100) DEFAULT NULL,
    plan VARCHAR(50) DEFAULT NULL,
    billing_cycle VARCHAR(20) DEFAULT NULL,
    stripe_customer_id VARCHAR(255) DEFAULT NULL,
    stripe_subscription_id VARCHAR(255) DEFAULT NULL,
    stripe_checkout_session_id VARCHAR(255) DEFAULT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
    activated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
    trial_starts_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
    trial_ends_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
    trial_reminder_stage INT DEFAULT NULL,
    UNIQUE INDEX UNIQ_5B870257E7927C74 (email),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS school');
    }
}