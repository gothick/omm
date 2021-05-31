<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210531094747 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE image ADD featuring_wander_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE image ADD CONSTRAINT FK_C53D045F5ED948F0 FOREIGN KEY (featuring_wander_id) REFERENCES wander (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C53D045F5ED948F0 ON image (featuring_wander_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE image DROP FOREIGN KEY FK_C53D045F5ED948F0');
        $this->addSql('DROP INDEX UNIQ_C53D045F5ED948F0 ON image');
        $this->addSql('ALTER TABLE image DROP featuring_wander_id');
    }
}
