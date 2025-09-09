<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250909112153 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE watch (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, user_id INTEGER NOT NULL, post_id INTEGER NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , CONSTRAINT FK_500B4A26A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_500B4A264B89032C FOREIGN KEY (post_id) REFERENCES post (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_500B4A26A76ED395 ON watch (user_id)');
        $this->addSql('CREATE INDEX IDX_500B4A264B89032C ON watch (post_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_user_post ON watch (user_id, post_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__manga AS SELECT id, title, slug, synopsis, authors, cover_url, status, links, featured_until FROM manga');
        $this->addSql('DROP TABLE manga');
        $this->addSql('CREATE TABLE manga (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, title VARCHAR(160) NOT NULL, slug VARCHAR(180) NOT NULL, synopsis CLOB DEFAULT NULL, authors VARCHAR(255) DEFAULT NULL, cover_url VARCHAR(255) DEFAULT NULL, status VARCHAR(32) DEFAULT NULL, links CLOB DEFAULT NULL --(DC2Type:json)
        , featured_until DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        )');
        $this->addSql('INSERT INTO manga (id, title, slug, synopsis, authors, cover_url, status, links, featured_until) SELECT id, title, slug, synopsis, authors, cover_url, status, links, featured_until FROM __temp__manga');
        $this->addSql('DROP TABLE __temp__manga');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_765A9E03989D9B62 ON manga (slug)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE watch');
        $this->addSql('CREATE TEMPORARY TABLE __temp__manga AS SELECT id, title, slug, synopsis, authors, cover_url, status, links, featured_until FROM manga');
        $this->addSql('DROP TABLE manga');
        $this->addSql('CREATE TABLE manga (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, title VARCHAR(160) NOT NULL, slug VARCHAR(180) NOT NULL, synopsis CLOB DEFAULT NULL, authors VARCHAR(255) DEFAULT NULL, cover_url VARCHAR(255) DEFAULT NULL, status VARCHAR(32) DEFAULT NULL, links CLOB DEFAULT NULL --(DC2Type:json)
        , featured_until DATETIME DEFAULT NULL)');
        $this->addSql('INSERT INTO manga (id, title, slug, synopsis, authors, cover_url, status, links, featured_until) SELECT id, title, slug, synopsis, authors, cover_url, status, links, featured_until FROM __temp__manga');
        $this->addSql('DROP TABLE __temp__manga');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_765A9E03989D9B62 ON manga (slug)');
    }
}
