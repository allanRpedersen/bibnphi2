<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220413104425 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE text_alteration (id INT AUTO_INCREMENT NOT NULL, book_paragraph_id INT NOT NULL, name VARCHAR(255) NOT NULL, begin_tag VARCHAR(255) NOT NULL, end_tag VARCHAR(255) NOT NULL, length INT NOT NULL, position INT NOT NULL, INDEX IDX_31A8E5ED2B19327 (book_paragraph_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE text_alteration ADD CONSTRAINT FK_31A8E5ED2B19327 FOREIGN KEY (book_paragraph_id) REFERENCES book_paragraph (id)');
        $this->addSql('DROP TABLE highlighted_content');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE highlighted_content (id INT AUTO_INCREMENT NOT NULL, content_type VARCHAR(64) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, orig_id INT NOT NULL, highlighted_string VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, matching_indexes LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:array)\', book_id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('DROP TABLE text_alteration');
    }
}
