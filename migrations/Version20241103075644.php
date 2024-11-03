<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241103075644 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE capital_account_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE capital_security_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE command_queue_storage_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE crypto_wallet_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE queued_capital_deposit_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE queued_deposit_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE transaction_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE "user_id_seq" INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE capital_account (id INT NOT NULL, is_main BOOLEAN DEFAULT false NOT NULL, email VARCHAR(255) NOT NULL, account_name VARCHAR(255) NOT NULL, available_balance NUMERIC(10, 2) NOT NULL, allocated_balance NUMERIC(10, 2) NOT NULL, assigned_users_count INT NOT NULL, cst VARCHAR(255) DEFAULT NULL, x_security_token VARCHAR(255) DEFAULT NULL, restrict_user_assign BOOLEAN DEFAULT false NOT NULL, api_identifier VARCHAR(50) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE capital_security (id INT NOT NULL, cst VARCHAR(255) NOT NULL, x_security_token VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN capital_security.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN capital_security.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE command_queue_storage (id INT NOT NULL, user_id INT NOT NULL, command_name VARCHAR(255) NOT NULL, last_question VARCHAR(255) NOT NULL, instructions JSON NOT NULL, count INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_AFBEDCC7A76ED395 ON command_queue_storage (user_id)');
        $this->addSql('COMMENT ON COLUMN command_queue_storage.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN command_queue_storage.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE crypto_wallet (id INT NOT NULL, user_id INT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, coin_name VARCHAR(50) NOT NULL, network VARCHAR(70) NOT NULL, address_base58 VARCHAR(255) NOT NULL, address_hex VARCHAR(255) NOT NULL, private_key TEXT NOT NULL, public_key VARCHAR(255) NOT NULL, balance NUMERIC(30, 8) NOT NULL, network_balance NUMERIC(30, 8) NOT NULL, last_transaction_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_BBA98CBBD650A821 ON crypto_wallet (address_base58)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_BBA98CBBB49F7FE9 ON crypto_wallet (address_hex)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_BBA98CBB66F9D463 ON crypto_wallet (public_key)');
        $this->addSql('CREATE INDEX IDX_BBA98CBBA76ED395 ON crypto_wallet (user_id)');
        $this->addSql('COMMENT ON COLUMN crypto_wallet.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN crypto_wallet.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN crypto_wallet.last_transaction_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE queued_capital_deposit (id INT NOT NULL, capital_account_id INT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, amount NUMERIC(8, 2) NOT NULL, status VARCHAR(30) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_F5CA5D59EB66A785 ON queued_capital_deposit (capital_account_id)');
        $this->addSql('COMMENT ON COLUMN queued_capital_deposit.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN queued_capital_deposit.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE queued_deposit (id INT NOT NULL, crypto_wallet_id INT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, amount NUMERIC(8, 2) NOT NULL, status VARCHAR(30) NOT NULL, cron_ignore BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_8C74BD7B97761871 ON queued_deposit (crypto_wallet_id)');
        $this->addSql('COMMENT ON COLUMN queued_deposit.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN queued_deposit.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE transaction (id INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, ex_transaction_id VARCHAR(255) NOT NULL, symbol VARCHAR(50) NOT NULL, token_address VARCHAR(255) NOT NULL, decimals INT NOT NULL, block_timestamp BIGINT NOT NULL, from_address VARCHAR(255) NOT NULL, to_address VARCHAR(255) NOT NULL, ex_type VARCHAR(50) NOT NULL, value NUMERIC(18, 8) NOT NULL, status VARCHAR(10) NOT NULL, type VARCHAR(10) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_723705D1C6BBF30E ON transaction (ex_transaction_id)');
        $this->addSql('COMMENT ON COLUMN transaction.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN transaction.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE "user" (id INT NOT NULL, capital_account_id INT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) DEFAULT NULL, username VARCHAR(255) DEFAULT NULL, telegram_id VARCHAR(20) NOT NULL, photo_url VARCHAR(255) DEFAULT NULL, telegram_auth_date BIGINT DEFAULT NULL, telegram_hash VARCHAR(255) DEFAULT NULL, telegram_chat_id VARCHAR(15) DEFAULT NULL, balance NUMERIC(10, 2) DEFAULT \'0\' NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_8D93D649EB66A785 ON "user" (capital_account_id)');
        $this->addSql('COMMENT ON COLUMN "user".created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN "user".updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE command_queue_storage ADD CONSTRAINT FK_AFBEDCC7A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE crypto_wallet ADD CONSTRAINT FK_BBA98CBBA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE queued_capital_deposit ADD CONSTRAINT FK_F5CA5D59EB66A785 FOREIGN KEY (capital_account_id) REFERENCES capital_account (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE queued_deposit ADD CONSTRAINT FK_8C74BD7B97761871 FOREIGN KEY (crypto_wallet_id) REFERENCES crypto_wallet (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE "user" ADD CONSTRAINT FK_8D93D649EB66A785 FOREIGN KEY (capital_account_id) REFERENCES capital_account (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE capital_account_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE capital_security_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE command_queue_storage_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE crypto_wallet_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE queued_capital_deposit_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE queued_deposit_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE transaction_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE "user_id_seq" CASCADE');
        $this->addSql('ALTER TABLE command_queue_storage DROP CONSTRAINT FK_AFBEDCC7A76ED395');
        $this->addSql('ALTER TABLE crypto_wallet DROP CONSTRAINT FK_BBA98CBBA76ED395');
        $this->addSql('ALTER TABLE queued_capital_deposit DROP CONSTRAINT FK_F5CA5D59EB66A785');
        $this->addSql('ALTER TABLE queued_deposit DROP CONSTRAINT FK_8C74BD7B97761871');
        $this->addSql('ALTER TABLE "user" DROP CONSTRAINT FK_8D93D649EB66A785');
        $this->addSql('DROP TABLE capital_account');
        $this->addSql('DROP TABLE capital_security');
        $this->addSql('DROP TABLE command_queue_storage');
        $this->addSql('DROP TABLE crypto_wallet');
        $this->addSql('DROP TABLE queued_capital_deposit');
        $this->addSql('DROP TABLE queued_deposit');
        $this->addSql('DROP TABLE transaction');
        $this->addSql('DROP TABLE "user"');
    }
}
