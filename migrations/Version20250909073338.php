<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250909073338 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE manga ADD COLUMN featured_until DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__manga AS SELECT id, title, slug, synopsis, authors, cover_url, status, links FROM manga');
        $this->addSql('DROP TABLE manga');
        $this->addSql('CREATE TABLE manga (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, title VARCHAR(160) NOT NULL, slug VARCHAR(180) NOT NULL, synopsis CLOB DEFAULT NULL, authors VARCHAR(255) DEFAULT NULL, cover_url VARCHAR(255) DEFAULT NULL, status VARCHAR(32) DEFAULT NULL, links CLOB DEFAULT NULL --(DC2Type:json)
        )');
        $this->addSql('INSERT INTO manga (id, title, slug, synopsis, authors, cover_url, status, links) SELECT id, title, slug, synopsis, authors, cover_url, status, links FROM __temp__manga');
        $this->addSql('DROP TABLE __temp__manga');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_765A9E03989D9B62 ON manga (slug)');
    }
}
