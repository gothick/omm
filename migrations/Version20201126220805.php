<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201126220805 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE wander_image (wander_id INT NOT NULL, image_id INT NOT NULL, INDEX IDX_69C7253A2F82EC6B (wander_id), INDEX IDX_69C7253A3DA5256D (image_id), PRIMARY KEY(wander_id, image_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE wander_image ADD CONSTRAINT FK_69C7253A2F82EC6B FOREIGN KEY (wander_id) REFERENCES wander (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE wander_image ADD CONSTRAINT FK_69C7253A3DA5256D FOREIGN KEY (image_id) REFERENCES image (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE wander_image');
    }
}
