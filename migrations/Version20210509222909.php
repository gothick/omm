<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Exception;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use App\Service\GpxService;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210509222909 extends AbstractMigration implements ContainerAwareInterface
{
    /** @var ContainerInterface */
    private $container;

    public function getDescription(): string
    {
        return '';
    }

    public function setContainer(?ContainerInterface $container = null): void
    {
        if ($container === null) {
            throw new Exception("Wasn't given a container in this container-aware migration");
        }
        $this->container = $container;
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
    public function postUp(Schema $schema): void
    {
        if ($this->container == null) {
            throw new Exception('No container available in container-aware migration');
        }

        /** @var GpxService */
        $gpxService = $this->container->get('App\Service\GpxService');

        $updateGeoJsonStatement = $this->connection->prepare('UPDATE wander SET geo_json = (:geo_json) WHERE id = :id');

        $wanders = $this->connection->fetchAllAssociative('SELECT id, gpx_filename FROM wander WHERE gpx_filename IS NOT NULL');
        foreach ($wanders as $wander) {
            $geoJson = $gpxService->gpxToGeoJson($gpxService->getGpxStringFromFilename($wander['gpx_filename']));
            $updateGeoJsonStatement->bindValue('id', $wander['id']);
            $updateGeoJsonStatement->bindValue('geo_json', $geoJson);
            $updateGeoJsonStatement->executeStatement();
        }
    }
}
