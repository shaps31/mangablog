<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250908194652 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE manga_release (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, manga_id INTEGER DEFAULT NULL, title VARCHAR(160) NOT NULL, release_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , link VARCHAR(255) DEFAULT NULL, CONSTRAINT FK_F0D61C7B6461 FOREIGN KEY (manga_id) REFERENCES manga (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_F0D61C7B6461 ON manga_release (manga_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE manga_release');
    }
}
