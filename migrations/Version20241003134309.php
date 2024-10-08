<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241003134309 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE queued_deposit_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE queued_deposit (id INT NOT NULL, crypto_wallet_id INT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, amount NUMERIC(8, 2) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_8C74BD7B97761871 ON queued_deposit (crypto_wallet_id)');
        $this->addSql('COMMENT ON COLUMN queued_deposit.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN queued_deposit.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE queued_deposit ADD CONSTRAINT FK_8C74BD7B97761871 FOREIGN KEY (crypto_wallet_id) REFERENCES crypto_wallet (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE queued_deposit_id_seq CASCADE');
        $this->addSql('ALTER TABLE queued_deposit DROP CONSTRAINT FK_8C74BD7B97761871');
        $this->addSql('DROP TABLE queued_deposit');
    }
}
