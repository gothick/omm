<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201201201808 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE wander ADD distance DOUBLE PRECISION DEFAULT NULL, ADD avg_speed DOUBLE PRECISION DEFAULT NULL, ADD avg_pace DOUBLE PRECISION DEFAULT NULL, ADD min_altitude DOUBLE PRECISION DEFAULT NULL, ADD max_altitude DOUBLE PRECISION DEFAULT NULL, ADD cumulative_elevation_gain DOUBLE PRECISION DEFAULT NULL, ADD duration DOUBLE PRECISION DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE wander DROP distance, DROP avg_speed, DROP avg_pace, DROP min_altitude, DROP max_altitude, DROP cumulative_elevation_gain, DROP duration');
    }
}
