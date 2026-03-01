<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260301200336 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE image ADD copyright VARCHAR(1024) DEFAULT NULL');
        // Reasonable default value for copyright is "Copyright © Matt Gibson <year of capture>". I'll
        // update the photos that aren't mine retroactively.
        $this->addSql("UPDATE image SET copyright = CONCAT('Copyright © Matt Gibson ', YEAR(captured_at));");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE image DROP copyright');
    }
}
