<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210401194805 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE image ADD wander_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE image ADD CONSTRAINT FK_C53D045F2F82EC6B FOREIGN KEY (wander_id) REFERENCES wander (id)');
        $this->addSql('CREATE INDEX IDX_C53D045F2F82EC6B ON image (wander_id)');
        $this->addSql('UPDATE image INNER JOIN wander_image ON image.id = wander_image.image_id SET image.wander_id = wander_image.wander_id');
        $this->addSql('DROP TABLE wander_image');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE wander_image (wander_id INT NOT NULL, image_id INT NOT NULL, INDEX IDX_69C7253A2F82EC6B (wander_id), INDEX IDX_69C7253A3DA5256D (image_id), PRIMARY KEY(wander_id, image_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE wander_image ADD CONSTRAINT FK_69C7253A2F82EC6B FOREIGN KEY (wander_id) REFERENCES wander (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE wander_image ADD CONSTRAINT FK_69C7253A3DA5256D FOREIGN KEY (image_id) REFERENCES image (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE image DROP FOREIGN KEY FK_C53D045F2F82EC6B');
        $this->addSql('DROP INDEX IDX_C53D045F2F82EC6B ON image');
        $this->addSql('ALTER TABLE image DROP wander_id');
    }
}
