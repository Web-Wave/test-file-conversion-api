<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261016201550 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE conversion_job (id BLOB NOT NULL, status VARCHAR(255) NOT NULL, input_format VARCHAR(10) NOT NULL, output_format VARCHAR(10) NOT NULL, original_name VARCHAR(255) NOT NULL, input_path VARCHAR(255) NOT NULL, output_path VARCHAR(255) DEFAULT NULL, error CLOB DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY (id))');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE conversion_job');
    }
}
