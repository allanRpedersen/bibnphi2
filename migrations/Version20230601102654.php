<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230601102654 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE book_note ADD cell_paragraph_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE book_note ADD CONSTRAINT FK_D620B11CD51422F1 FOREIGN KEY (cell_paragraph_id) REFERENCES cell_paragraph (id)');
        $this->addSql('CREATE INDEX IDX_D620B11CD51422F1 ON book_note (cell_paragraph_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE book_note DROP FOREIGN KEY FK_D620B11CD51422F1');
        $this->addSql('DROP INDEX IDX_D620B11CD51422F1 ON book_note');
        $this->addSql('ALTER TABLE book_note DROP cell_paragraph_id');
    }
}
