<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240930191802 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE crypto_wallet_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE crypto_wallet (id INT NOT NULL, user_id INT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, coin_name VARCHAR(50) NOT NULL, network VARCHAR(70) NOT NULL, address_base58 VARCHAR(255) NOT NULL, address_hex VARCHAR(255) NOT NULL, private_key TEXT NOT NULL, balance NUMERIC(30, 8) NOT NULL, last_transaction_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_BBA98CBBD650A821 ON crypto_wallet (address_base58)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_BBA98CBBB49F7FE9 ON crypto_wallet (address_hex)');
        $this->addSql('CREATE INDEX IDX_BBA98CBBA76ED395 ON crypto_wallet (user_id)');
        $this->addSql('COMMENT ON COLUMN crypto_wallet.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN crypto_wallet.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN crypto_wallet.last_transaction_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE crypto_wallet ADD CONSTRAINT FK_BBA98CBBA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE crypto_wallet_id_seq CASCADE');
        $this->addSql('ALTER TABLE crypto_wallet DROP CONSTRAINT FK_BBA98CBBA76ED395');
        $this->addSql('DROP TABLE crypto_wallet');
    }
}
