<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220420143654 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE illustration (id INT AUTO_INCREMENT NOT NULL, book_paragraph_id INT NOT NULL, illustration_index INT NOT NULL, name VARCHAR(255) NOT NULL, svg_width VARCHAR(64) DEFAULT NULL, svg_height VARCHAR(64) DEFAULT NULL, file_name VARCHAR(255) NOT NULL, mime_type VARCHAR(64) DEFAULT NULL, svg_title VARCHAR(255) DEFAULT NULL, INDEX IDX_D67B9A42D2B19327 (book_paragraph_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE illustration ADD CONSTRAINT FK_D67B9A42D2B19327 FOREIGN KEY (book_paragraph_id) REFERENCES book_paragraph (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE illustration');
    }
}
