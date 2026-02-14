<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210424131056 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE image_tag (image_id INT NOT NULL, tag_id INT NOT NULL, INDEX IDX_5B6367D03DA5256D (image_id), INDEX IDX_5B6367D0BAD26311 (tag_id), PRIMARY KEY(image_id, tag_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE image_tag ADD CONSTRAINT FK_5B6367D03DA5256D FOREIGN KEY (image_id) REFERENCES image (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE image_tag ADD CONSTRAINT FK_5B6367D0BAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE image_tag');
    }
    public function postUp(Schema $schema): void
    {
        $images = $this->connection->fetchAllAssociative('SELECT id, keywords FROM image');
        // First, gather unique keywords
        $allKeywords = [];
        foreach ($images as $image) {
            $keywords = unserialize($image['keywords']);
            $allKeywords = array_merge($allKeywords, $keywords);
        }
        $unique_keywords = array_unique($allKeywords);
        $insertTagStmt = $this->connection->prepare('INSERT INTO tag (name) VALUES (:name)');

        // Insert our unique tags into the database, recording the ID values
        // we insert to use when populating image_tag.
        $tagsToIds = [];
        foreach ($unique_keywords as $newTag) {
            $insertTagStmt->bindValue('name', $newTag);
            $insertTagStmt->executeStatement();
            $tagsToIds[$newTag] = $this->connection->lastInsertId();
        };

        // Now go through the images again, creating relationships to our newly-
        // created tags from their existing keywords.
        $insertImageTagStmt = $this->connection->prepare('INSERT INTO image_tag (image_id, tag_id) VALUES (:image_id, :tag_id)');
        foreach ($images as $image) {
            // Uniquify, just in case we had two of the same tag on one image
            $keywords = array_unique(unserialize($image['keywords']));
            foreach ($keywords as $keyword) {
                $imageId = $image['id'];
                $tagId = $tagsToIds[$keyword];
                $insertImageTagStmt->bindValue('image_id', $imageId);
                $insertImageTagStmt->bindValue('tag_id', $tagId);
                $insertImageTagStmt->executeStatement();
            }
        }
    }
}
