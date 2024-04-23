<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240420174015 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `group` ADD gender_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE `group` ADD CONSTRAINT FK_6DC044C5708A0E0 FOREIGN KEY (gender_id) REFERENCES gender (id)');
        $this->addSql('CREATE INDEX IDX_6DC044C5708A0E0 ON `group` (gender_id)');
        $this->addSql('ALTER TABLE rattrapage CHANGE date_at date_at DATETIME NULL');
        $this->addSql('ALTER TABLE session CHANGE date_seance date_seance DATETIME NULL, CHANGE time_start time_start DATETIME NULL, CHANGE time_end time_end DATETIME NULL');
        $this->addSql('ALTER TABLE student CHANGE parent_email parent_email VARCHAR(255) NULL, CHANGE date_at date_at DATETIME NULL');
        $this->addSql('ALTER TABLE teacher CHANGE registered_at registered_at DATETIME NULL');
        $this->addSql('ALTER TABLE vote CHANGE date date DATETIME NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `group` DROP FOREIGN KEY FK_6DC044C5708A0E0');
        $this->addSql('DROP INDEX IDX_6DC044C5708A0E0 ON `group`');
        $this->addSql('ALTER TABLE `group` DROP gender_id');
        $this->addSql('ALTER TABLE rattrapage CHANGE date_at date_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE session CHANGE date_seance date_seance DATETIME DEFAULT NULL, CHANGE time_start time_start DATETIME DEFAULT NULL, CHANGE time_end time_end DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE student CHANGE parent_email parent_email VARCHAR(255) DEFAULT NULL, CHANGE date_at date_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE teacher CHANGE registered_at registered_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE vote CHANGE date date DATETIME DEFAULT NULL');
    }
}
