<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210219152426 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE book (id INT AUTO_INCREMENT NOT NULL, author_id INT NOT NULL, title VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, summary VARCHAR(255) DEFAULT NULL, published_year VARCHAR(11) DEFAULT NULL, odt_book_name VARCHAR(255) NOT NULL, odt_book_size INT NOT NULL, updated_at DATETIME NOT NULL, nb_paragraphs INT NOT NULL, nb_sentences INT NOT NULL, nb_words INT NOT NULL, parsing_time DOUBLE PRECISION NOT NULL, UNIQUE INDEX UNIQ_CBE5A331989D9B62 (slug), INDEX IDX_CBE5A331F675F31B (author_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE book_note (id INT AUTO_INCREMENT NOT NULL, book_id INT NOT NULL, book_paragraph_id INT NOT NULL, citation VARCHAR(255) NOT NULL, content LONGTEXT NOT NULL, citation_index INT NOT NULL, INDEX IDX_D620B11C16A2B381 (book_id), INDEX IDX_D620B11CD2B19327 (book_paragraph_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE book_paragraph (id INT AUTO_INCREMENT NOT NULL, book_id INT NOT NULL, content LONGTEXT NOT NULL, INDEX IDX_6F7D8E2416A2B381 (book_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE book ADD CONSTRAINT FK_CBE5A331F675F31B FOREIGN KEY (author_id) REFERENCES author (id)');
        $this->addSql('ALTER TABLE book_note ADD CONSTRAINT FK_D620B11C16A2B381 FOREIGN KEY (book_id) REFERENCES book (id)');
        $this->addSql('ALTER TABLE book_note ADD CONSTRAINT FK_D620B11CD2B19327 FOREIGN KEY (book_paragraph_id) REFERENCES book_paragraph (id)');
        $this->addSql('ALTER TABLE book_paragraph ADD CONSTRAINT FK_6F7D8E2416A2B381 FOREIGN KEY (book_id) REFERENCES book (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE book_note DROP FOREIGN KEY FK_D620B11C16A2B381');
        $this->addSql('ALTER TABLE book_paragraph DROP FOREIGN KEY FK_6F7D8E2416A2B381');
        $this->addSql('ALTER TABLE book_note DROP FOREIGN KEY FK_D620B11CD2B19327');
        $this->addSql('DROP TABLE book');
        $this->addSql('DROP TABLE book_note');
        $this->addSql('DROP TABLE book_paragraph');
    }
}
