<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use App\Service\GpxService;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use App\Entity\Wander;


/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211213220450 extends AbstractMigration
{
    public function __construct(Connection $connection, LoggerInterface $logger, private readonly GpxService $gpxService)
    {
        parent::__construct($connection, $logger);
    }
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE wander ADD google_polyline LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE wander DROP google_polyline');
    }
    public function postUp(Schema $schema): void
    {
        $updateGeoJsonStatement = $this->connection->prepare('UPDATE wander SET google_polyline = (:google_polyline) WHERE id = :id');

        /** @var array{Wander} $wanders */
        $wanders = $this->connection->fetchAllAssociative('SELECT id, gpx_filename FROM wander WHERE gpx_filename IS NOT NULL');

        foreach ($wanders as $wander) {
            $filename = $wander->getGpxFilename();
            if (!$filename) {
                continue;
            }
            $googlePolyline = $this->gpxService->gpxToGooglePolyline($this->gpxService->getGpxStringFromFilename($filename));
            $updateGeoJsonStatement->bindValue('id', $wander->getId());
            $updateGeoJsonStatement->bindValue('google_polyline', $googlePolyline);
            $updateGeoJsonStatement->executeStatement();
        }
    }
}
