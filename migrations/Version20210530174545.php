<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210530174545 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE wander ADD featured_image_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE wander ADD CONSTRAINT FK_3111AE193569D950 FOREIGN KEY (featured_image_id) REFERENCES image (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_3111AE193569D950 ON wander (featured_image_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE wander DROP FOREIGN KEY FK_3111AE193569D950');
        $this->addSql('DROP INDEX UNIQ_3111AE193569D950 ON wander');
        $this->addSql('ALTER TABLE wander DROP featured_image_id');
    }
}
