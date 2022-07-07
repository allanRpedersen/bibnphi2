<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220707075431 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE illustration ADD book_note_id INT DEFAULT NULL, CHANGE book_paragraph_id book_paragraph_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE illustration ADD CONSTRAINT FK_D67B9A42B5CD20C3 FOREIGN KEY (book_note_id) REFERENCES book_paragraph (id)');
        $this->addSql('CREATE INDEX IDX_D67B9A42B5CD20C3 ON illustration (book_note_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE illustration DROP FOREIGN KEY FK_D67B9A42B5CD20C3');
        $this->addSql('DROP INDEX IDX_D67B9A42B5CD20C3 ON illustration');
        $this->addSql('ALTER TABLE illustration DROP book_note_id, CHANGE book_paragraph_id book_paragraph_id INT NOT NULL');
    }
}
