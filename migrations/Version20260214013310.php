<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260214013310 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE task ADD started_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE task ADD last_resume_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE task ADD finished_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE task ADD is_running BOOLEAN NOT NULL DEFAULT false');
        $this->addSql('ALTER TABLE task ADD accumulated_time NUMERIC(10, 2) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD reset_token VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD reset_token_expires_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE task DROP started_at');
        $this->addSql('ALTER TABLE task DROP last_resume_at');
        $this->addSql('ALTER TABLE task DROP finished_at');
        $this->addSql('ALTER TABLE task DROP is_running');
        $this->addSql('ALTER TABLE task DROP accumulated_time');
        $this->addSql('ALTER TABLE "user" DROP reset_token');
        $this->addSql('ALTER TABLE "user" DROP reset_token_expires_at');
    }
}
