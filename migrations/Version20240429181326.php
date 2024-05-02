<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240429181326 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `group` ADD chat_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE `group` ADD CONSTRAINT FK_6DC044C51A9A7125 FOREIGN KEY (chat_id) REFERENCES chat (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6DC044C51A9A7125 ON `group` (chat_id)');
        $this->addSql('ALTER TABLE messagerie CHANGE time_send time_send DATETIME NULL');
        $this->addSql('ALTER TABLE rattrapage CHANGE date_at date_at DATETIME NULL');
        $this->addSql('ALTER TABLE session CHANGE date_seance date_seance DATETIME NULL, CHANGE time_start time_start DATETIME NULL, CHANGE time_end time_end DATETIME NULL');
        $this->addSql('ALTER TABLE student CHANGE parent_email parent_email VARCHAR(255) NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `group` DROP FOREIGN KEY FK_6DC044C51A9A7125');
        $this->addSql('DROP INDEX UNIQ_6DC044C51A9A7125 ON `group`');
        $this->addSql('ALTER TABLE `group` DROP chat_id');
        $this->addSql('ALTER TABLE messagerie CHANGE time_send time_send DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE rattrapage CHANGE date_at date_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE session CHANGE date_seance date_seance DATETIME DEFAULT NULL, CHANGE time_start time_start DATETIME DEFAULT NULL, CHANGE time_end time_end DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE student CHANGE parent_email parent_email VARCHAR(255) DEFAULT NULL');
    }
}
