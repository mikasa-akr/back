<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240407222921 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE rattrapage CHANGE date_at date_at DATETIME NULL');
        $this->addSql('ALTER TABLE session CHANGE date_seance date_seance DATETIME NULL, CHANGE time_start time_start DATETIME NULL, CHANGE time_end time_end DATETIME NULL');
        $this->addSql('ALTER TABLE student ADD date_at DATETIME NULL, CHANGE parent_email parent_email VARCHAR(255) NULL');
        $this->addSql('ALTER TABLE teacher CHANGE registered_at registered_at DATETIME NULL');
        $this->addSql('ALTER TABLE vote CHANGE date date DATETIME NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE session CHANGE date_seance date_seance DATETIME DEFAULT NULL, CHANGE time_start time_start DATETIME DEFAULT NULL, CHANGE time_end time_end DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE rattrapage CHANGE date_at date_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE student DROP date_at, CHANGE parent_email parent_email VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE vote CHANGE date date DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE teacher CHANGE registered_at registered_at DATETIME DEFAULT NULL');
    }
}
