<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210125191023 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE wander_backup');
        $this->addSql('ALTER TABLE wander ADD centroid LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', ADD angle_from_home DOUBLE PRECISION DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE wander_backup (id INT DEFAULT 0 NOT NULL, title VARCHAR(1024) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, start_time DATETIME NOT NULL, end_time DATETIME NOT NULL, description LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, gpx_filename VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, distance DOUBLE PRECISION DEFAULT NULL, avg_speed DOUBLE PRECISION DEFAULT NULL, avg_pace DOUBLE PRECISION DEFAULT NULL, min_altitude DOUBLE PRECISION DEFAULT NULL, max_altitude DOUBLE PRECISION DEFAULT NULL, cumulative_elevation_gain DOUBLE PRECISION DEFAULT NULL) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE wander DROP centroid, DROP angle_from_home');
    }
}
