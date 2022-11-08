<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221108132242 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE "user" DROP CONSTRAINT fk_8d93d649d60322ac');
        $this->addSql('DROP SEQUENCE role_id_seq CASCADE');
        $this->addSql('DROP TABLE role');
        $this->addSql('DROP INDEX idx_8d93d649d60322ac');
        $this->addSql('ALTER TABLE "user" RENAME COLUMN role_id TO role');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('CREATE SEQUENCE role_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE role (id INT NOT NULL, role VARCHAR(32) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE "user" RENAME COLUMN role TO role_id');
        $this->addSql('ALTER TABLE "user" ADD CONSTRAINT fk_8d93d649d60322ac FOREIGN KEY (role_id) REFERENCES role (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_8d93d649d60322ac ON "user" (role_id)');
    }
}
