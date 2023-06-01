<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230601093516 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE book_table (id INT AUTO_INCREMENT NOT NULL, anchor_paragraph_id INT NOT NULL, nb_columns INT NOT NULL, nb_rows INT NOT NULL, UNIQUE INDEX UNIQ_F8EB9A3F31659B52 (anchor_paragraph_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE cell_paragraph (id INT AUTO_INCREMENT NOT NULL, table_cell_id INT NOT NULL, content LONGTEXT NOT NULL, paragraph_styles VARCHAR(255) NOT NULL, INDEX IDX_9688CE07C90A9835 (table_cell_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE table_cell (id INT AUTO_INCREMENT NOT NULL, book_table_id INT NOT NULL, INDEX IDX_8151C729F5003E05 (book_table_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE book_table ADD CONSTRAINT FK_F8EB9A3F31659B52 FOREIGN KEY (anchor_paragraph_id) REFERENCES book_paragraph (id)');
        $this->addSql('ALTER TABLE cell_paragraph ADD CONSTRAINT FK_9688CE07C90A9835 FOREIGN KEY (table_cell_id) REFERENCES table_cell (id)');
        $this->addSql('ALTER TABLE table_cell ADD CONSTRAINT FK_8151C729F5003E05 FOREIGN KEY (book_table_id) REFERENCES book_table (id)');
        $this->addSql('ALTER TABLE illustration ADD cell_paragraph_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE illustration ADD CONSTRAINT FK_D67B9A42D51422F1 FOREIGN KEY (cell_paragraph_id) REFERENCES cell_paragraph (id)');
        $this->addSql('CREATE INDEX IDX_D67B9A42D51422F1 ON illustration (cell_paragraph_id)');
        $this->addSql('ALTER TABLE text_alteration ADD cell_paragraph_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE text_alteration ADD CONSTRAINT FK_31A8E5ED51422F1 FOREIGN KEY (cell_paragraph_id) REFERENCES cell_paragraph (id)');
        $this->addSql('CREATE INDEX IDX_31A8E5ED51422F1 ON text_alteration (cell_paragraph_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE illustration DROP FOREIGN KEY FK_D67B9A42D51422F1');
        $this->addSql('ALTER TABLE text_alteration DROP FOREIGN KEY FK_31A8E5ED51422F1');
        $this->addSql('ALTER TABLE book_table DROP FOREIGN KEY FK_F8EB9A3F31659B52');
        $this->addSql('ALTER TABLE cell_paragraph DROP FOREIGN KEY FK_9688CE07C90A9835');
        $this->addSql('ALTER TABLE table_cell DROP FOREIGN KEY FK_8151C729F5003E05');
        $this->addSql('DROP TABLE book_table');
        $this->addSql('DROP TABLE cell_paragraph');
        $this->addSql('DROP TABLE table_cell');
        $this->addSql('DROP INDEX IDX_D67B9A42D51422F1 ON illustration');
        $this->addSql('ALTER TABLE illustration DROP cell_paragraph_id');
        $this->addSql('DROP INDEX IDX_31A8E5ED51422F1 ON text_alteration');
        $this->addSql('ALTER TABLE text_alteration DROP cell_paragraph_id');
    }
}
