<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230430135823 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE bookmark (id INT AUTO_INCREMENT NOT NULL, book_id INT NOT NULL, paragraph_id INT NOT NULL, name VARCHAR(255) NOT NULL, INDEX IDX_DA62921D16A2B381 (book_id), UNIQUE INDEX UNIQ_DA62921D8B50597F (paragraph_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE bookmark ADD CONSTRAINT FK_DA62921D16A2B381 FOREIGN KEY (book_id) REFERENCES book (id)');
        $this->addSql('ALTER TABLE bookmark ADD CONSTRAINT FK_DA62921D8B50597F FOREIGN KEY (paragraph_id) REFERENCES book_paragraph (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bookmark DROP FOREIGN KEY FK_DA62921D16A2B381');
        $this->addSql('ALTER TABLE bookmark DROP FOREIGN KEY FK_DA62921D8B50597F');
        $this->addSql('DROP TABLE bookmark');
    }
}
