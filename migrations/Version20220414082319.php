<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220414082319 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE text_alteration ADD book_note_id INT DEFAULT NULL, CHANGE book_paragraph_id book_paragraph_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE text_alteration ADD CONSTRAINT FK_31A8E5EB5CD20C3 FOREIGN KEY (book_note_id) REFERENCES book_note (id)');
        $this->addSql('CREATE INDEX IDX_31A8E5EB5CD20C3 ON text_alteration (book_note_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE text_alteration DROP FOREIGN KEY FK_31A8E5EB5CD20C3');
        $this->addSql('DROP INDEX IDX_31A8E5EB5CD20C3 ON text_alteration');
        $this->addSql('ALTER TABLE text_alteration DROP book_note_id, CHANGE book_paragraph_id book_paragraph_id INT NOT NULL');
    }
}
