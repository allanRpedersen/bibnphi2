<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230524164001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE book_table (id INT AUTO_INCREMENT NOT NULL, anchor_paragraph_id INT NOT NULL, nb_columns INT NOT NULL, nb_rows INT NOT NULL, UNIQUE INDEX UNIQ_F8EB9A3F31659B52 (anchor_paragraph_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE table_cell (id INT AUTO_INCREMENT NOT NULL, book_table_id INT NOT NULL, INDEX IDX_8151C729F5003E05 (book_table_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE table_cell_paragraph (id INT AUTO_INCREMENT NOT NULL, table_cell_id INT NOT NULL, content LONGTEXT NOT NULL, paragraph_styles VARCHAR(255) NOT NULL, book_id INT, INDEX IDX_E84C6DFAC90A9835 (table_cell_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE book_table ADD CONSTRAINT FK_F8EB9A3F31659B52 FOREIGN KEY (anchor_paragraph_id) REFERENCES book_paragraph (id)');
        $this->addSql('ALTER TABLE table_cell ADD CONSTRAINT FK_8151C729F5003E05 FOREIGN KEY (book_table_id) REFERENCES book_table (id)');
        $this->addSql('ALTER TABLE table_cell_paragraph ADD CONSTRAINT FK_E84C6DFAC90A9835 FOREIGN KEY (table_cell_id) REFERENCES table_cell (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE book_table DROP FOREIGN KEY FK_F8EB9A3F31659B52');
        $this->addSql('ALTER TABLE table_cell DROP FOREIGN KEY FK_8151C729F5003E05');
        $this->addSql('ALTER TABLE table_cell_paragraph DROP FOREIGN KEY FK_E84C6DFAC90A9835');
        $this->addSql('DROP TABLE book_table');
        $this->addSql('DROP TABLE table_cell');
        $this->addSql('DROP TABLE table_cell_paragraph');
    }
}
