<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250908193709 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE manga (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, title VARCHAR(160) NOT NULL, slug VARCHAR(180) NOT NULL, synopsis CLOB DEFAULT NULL, authors VARCHAR(255) DEFAULT NULL, cover_url VARCHAR(255) DEFAULT NULL, status VARCHAR(32) DEFAULT NULL, links CLOB DEFAULT NULL --(DC2Type:json)
        )');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_765A9E03989D9B62 ON manga (slug)');
        $this->addSql('CREATE TABLE manga_tag (manga_id INTEGER NOT NULL, tag_id INTEGER NOT NULL, PRIMARY KEY(manga_id, tag_id), CONSTRAINT FK_52E8F5BA7B6461 FOREIGN KEY (manga_id) REFERENCES manga (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_52E8F5BABAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_52E8F5BA7B6461 ON manga_tag (manga_id)');
        $this->addSql('CREATE INDEX IDX_52E8F5BABAD26311 ON manga_tag (tag_id)');
        $this->addSql('CREATE TABLE manga_post (manga_id INTEGER NOT NULL, post_id INTEGER NOT NULL, PRIMARY KEY(manga_id, post_id), CONSTRAINT FK_5DE85C77B6461 FOREIGN KEY (manga_id) REFERENCES manga (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_5DE85C74B89032C FOREIGN KEY (post_id) REFERENCES post (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_5DE85C77B6461 ON manga_post (manga_id)');
        $this->addSql('CREATE INDEX IDX_5DE85C74B89032C ON manga_post (post_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE manga');
        $this->addSql('DROP TABLE manga_tag');
        $this->addSql('DROP TABLE manga_post');
    }
}
