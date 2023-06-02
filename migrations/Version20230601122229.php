<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230601122229 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE book_note CHANGE book_paragraph_id book_paragraph_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE book_table ADD CONSTRAINT FK_F8EB9A3F31659B52 FOREIGN KEY (anchor_paragraph_id) REFERENCES book_paragraph (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE book_table DROP FOREIGN KEY FK_F8EB9A3F31659B52');
        $this->addSql('ALTER TABLE book_note CHANGE book_paragraph_id book_paragraph_id INT NOT NULL');
    }
}
