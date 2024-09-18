<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240917160016 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE command_queue_storage_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE command_queue_storage (id INT NOT NULL, user_id INT NOT NULL, command_name VARCHAR(255) NOT NULL, last_question VARCHAR(255) NOT NULL, instructions JSON NOT NULL, count INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_AFBEDCC7A76ED395 ON command_queue_storage (user_id)');
        $this->addSql('COMMENT ON COLUMN command_queue_storage.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN command_queue_storage.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE command_queue_storage ADD CONSTRAINT FK_AFBEDCC7A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE command_queue_storage_id_seq CASCADE');
        $this->addSql('ALTER TABLE command_queue_storage DROP CONSTRAINT FK_AFBEDCC7A76ED395');
        $this->addSql('DROP TABLE command_queue_storage');
    }
}
