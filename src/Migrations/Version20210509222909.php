<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Exception;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210509222909 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE wander ADD geo_json LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE wander DROP geo_json');
    }
    // This is handled by Version20211213220450, which adds the google_polyline column and fills it
    // with data from the GPX files. That's why we don't have the gpxToGeoJson function available
    // any more; we'll use the polyline from the later migration instead.

    //     public function postUp(Schema $schema): void
    //     {
    //         if ($this->gpxService == null) {
    //             throw new Exception('No GpxService available in migration');
    //         }

    //         $updateGeoJsonStatement = $this->connection->prepare('UPDATE wander SET geo_json = (:geo_json) WHERE id = :id');

    //         $wanders = $this->connection->fetchAllAssociative('SELECT id, gpx_filename FROM wander WHERE gpx_filename IS NOT NULL');
    //         foreach ($wanders as $wander) {
    //             $geoJson = $this->gpxService->gpxToGeoJson($gpxService->getGpxStringFromFilename($wander['gpx_filename']));
    //             $updateGeoJsonStatement->bindValue('id', $wander['id']);
    //             $updateGeoJsonStatement->bindValue('geo_json', $geoJson);
    //             $updateGeoJsonStatement->executeStatement();
    //         }
    //     }
}
