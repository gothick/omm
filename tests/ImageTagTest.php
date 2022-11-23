<?php

namespace App\Tests;

use App\Entity\Image;
use App\Entity\Tag;
use App\Repository\ImageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Beelab\TagBundle\Tag\TagInterface;
use Symfony\Component\Translation\Util\ArrayConverter;

class ImageTagTest extends TestCase
{
    /** @var TagInterface */
    private $alphaTag;

    /** @var TagInterface */
    private $betaTag;

    /** @var TagInterface */
    private $betaUppercaseTag;

    /** @var TagInterface */
    private $gammaTag;

    protected function setUp(): void
    {
        $this->alphaTag = new Tag();
        $this->alphaTag->setName('alpha');
        $this->betaTag = new Tag();
        $this->betaTag->setName('beta');
        $this->gammaTag = new Tag();
        $this->gammaTag->setName('gamma');
        $this->betaUppercaseTag = new Tag();
        $this->betaUppercaseTag->setName('BETA');
    }

    /**
     * @group tags
     */
    public function testGetTags(): void
    {
        $image = new Image();
        /** @var iterable<TagInterface> */ $tags = $image->getTags();
        $this->assertIsIterable($tags, "GetTags() should return an iterable even if no tags are present.");
        $this->assertEmpty($tags, "GetTags() should return an empty iterable if no tags are present.");

        $clint = new Tag(); /* The tag with no name */
        $image->addTag($clint);
        $tags = $image->getTags();
        $this->assertIsIterable($tags, "GetTags() should return an iterable.");
        $this->assertCount(1, $tags, "GetTags() should find one tags when we've added one tag.");
        /** @var TagInterface */
        $retrievedTag = $tags->current();
        $this->assertNotNull($retrievedTag, "Retrieved tag should not be null.");
        $this->assertNull($retrievedTag->getName(), "Tag with no name should have a null name.");

        $floopy = new Tag();
        $floopy->setName("floopy");
        $image = new Image();
        $image->addTag($floopy);
        $tags = $image->getTags();
        /** @var TagInterface */
        $hopefullyFloopy = $tags->current();
        $this->assertEquals("floopy", $hopefullyFloopy->getName(), "Unexpected tag name retrieved.");

        $tagAlpha = new Tag();
        $tagAlpha->setName("alpha");
        $tagBeta = new Tag();
        $tagBeta->setName("beta");
        $image = new Image();
        $image->addTag($tagAlpha);
        $image->addTag($tagBeta);
        $this->assertCount(2, $image->getTags(), "Expected two tags after setting two tags.");
        $retrievedTags = $image->getTags();
        $this->assertCount(1, $retrievedTags->filter(fn($tag) => $tag->getName() === 'alpha'), "Couldn't find alpha tag");
        $this->assertCount(1, $retrievedTags->filter(fn($tag) => $tag->getName() === 'beta'), "Couldn't find beta tag");
    }
    /**
     * @group tags
     */
    public function testAddTag(): void
    {
        $image = new Image();
        $image->addTag($this->alphaTag);
        $image->addTag($this->betaTag);
        $image->addTag($this->betaUppercaseTag);
        $tags = $image->getTags();
        // We're expecting three because our tags are case sensitive. I mean, they probably *shouldn't* be, but hey...
        $this->assertCount(3, $tags);
        $this->assertCount(1, $tags->filter(fn($tag) => $tag->getName() === 'alpha'), "Couldn't find alpha tag");
        $this->assertCount(1, $tags->filter(fn($tag) => $tag->getName() === 'beta'), "Couldn't find beta tag");
        $this->assertCount(1, $tags->filter(fn($tag) => $tag->getName() === 'BETA'), "Couldn't find BETA tag");

        // Bizarrely, a nameless tag seems to be okay with the Beelab tag bundle.
        $image->addTag(new Tag());
        $this->assertCount(4, $tags);
    }
    /**
     * @group tags
     */
    public function testClearTags(): void
    {
        $image = new Image();
        $this->assertCount(0, $image->getTags(), "We should start with no tags on a fresh image.");
        // Should do nothing, i.e. throw no exception
        $image->clearTags();

        $image->addTag($this->alphaTag);
        $image->clearTags();
        $this->assertCount(0, $image->getTags(), "Cleared a single tag but we now don't have zero tags.");
        $image->addTag($this->alphaTag);
        $image->addTag($this->betaTag);
        $image->clearTags();
        $this->assertCount(0, $image->getTags(), "Cleared two tags but don't have zero tags.");
    }
    /**
     * @group tags
     */
    public function testRemoveTag(): void {
        $image = new Image();
        $image->addTag($this->alphaTag);
        $image->removeTag($this->alphaTag);
        $this->assertCount(0, $image->getTags());

        $image->addTag($this->alphaTag);
        $newAlphaTag = new Tag();
        $newAlphaTag->setName($this->alphaTag->getName());
        $image->removeTag($newAlphaTag);
        // Tags are removed when the actual *object* is the same, not when the name is the same.
        $this->assertCount(1, $image->getTags(), "Tags shouldn't be removed by name.");
    }
    /**
     * @group tags
     */
    public function testHasTag(): void
    {
        // I don't think hasTag is actually used anywhere, but better safe than sorry
        $image = new Image();
        $this->assertFalse($image->hasTag($this->alphaTag));
        $image->addTag($this->alphaTag);
        $this->assertTrue($image->hasTag($this->alphaTag), "Couldn't find newly-added tag with hasTag.");

        $newAlphaTag = new Tag();
        $newAlphaTag->setName($this->alphaTag->getName());
        $this->assertFalse($image->hasTag($newAlphaTag), "hasTag should compare tags by identity, not just name.");
    }
    /**
     * @group tags
     */
    public function testSetTags(): void
    {
        $image = new Image();
        $image->setTags(new ArrayCollection([]));
        $this->assertCount(0, $image->getTags());
        // TODO: Figure out how to fix the phpstan warning we get here because we've not been
        // explicit in the right way about covariant types.
        $image->setTags(new ArrayCollection([$this->alphaTag]));
        $this->assertCount(1, $image->getTags());

        // Adding a new tag should clear existing tags
        $image->setTags(new ArrayCollection([$this->betaTag]));
        $this->assertCount(1, $image->getTags(), "setTags didn't clear existing tags");
        $this->assertTrue($image->hasTag($this->betaTag), "Resetting tags failed.");
    }
    /**
     * @group tags
     */
    public function testSetTagsText(): void
    {
        // This is fairly minimal interface, but it's an important part of both how Beelab's tag
        // feature works and how it interacts with Symfony forms.

        $image = new Image();
        $originalUpdatedAt = $image->getUpdatedAt();
        $image->setTagsText('one, two, three');
        // NB: DO NOT CALL getTagsText() here. You might expect it to return you the tag text it just
        // set. However, it will instead overwrite it with nothing after setting it from the Tag
        // objects (of which there are none, because they're tricksily set up by the Beelab tag system's
        // event subscriber on persist, and not before.)

        // The real thing that needs to be set up (for the event subscriber) is
        // that we change something on the class to make sure the subscriber gets
        // poked:
        $newUpdatedAt = $image->getUpdatedAt();
        $this->assertNotEquals($originalUpdatedAt, $newUpdatedAt, "updatedAt should have been changed when TagsText was set.");
        // And that the *names* we get back from getTagNames are read correctly from the tags text:
        $names = $image->getTagNames();
        $this->assertIsArray($names, "Expected an array from getTagNames()");
        $this->assertCount(3, $names, "Set three tags via setTagsText, expected three names back from getTagNames()");
    }
    /**
     * @group tags
     */
    public function testGetTagsText(): void
    {
        $image = new Image();
        $this->assertIsString($image->getTagsText(), "Expected a string result even if no tags are set.");
        $this->assertEquals("", $image->getTagsText(), "Expected an empty string if no tags set");
        $image->addTag($this->alphaTag);
        $image->addTag($this->betaTag);
        // Just in case; we might need this later, but I think they'll come out in the order we put them in.
        // $this->assertThat($tagsText, $this->logicalOr($this->equalTo('alpha, beta'), $this->equalTo('beta, alpha')), "Tags were not converted to expected tags text.");
        $this->assertEquals('alpha, beta', $image->getTagsText(), "Tags were not converted to expected tags text.");
        $image->addTag($this->gammaTag);
        $this->assertEquals('alpha, beta, gamma', $image->getTagsText(), "Tags were not converted to expected tags text.");
    }
    /**
     * @group tags
     */
    public function testGetTagNames(): void
    {
        // GetTagNames is just used by Beelab's tagging system to read back anything set with
        // setTagsText, e.g. when setting form data back onto the Taggable entity.
        $image = new Image();
        $names = $image->getTagNames();
        $this->assertIsArray($names, "Expected array back from getTagNames even if no tags exist.");
        $this->assertCount(0, $names, "Expected zero-length array from getTagNames when no tag names exist.");

        $image->setTagsText('one, two, three');
        $names = $image->getTagNames();
        $this->assertIsArray($names, "Expected an array from getTagNames()");
        $this->assertCount(3, $names, "Set three tags via setTagsText, expected three names back from getTagNames()");
        $this->assertContains('one', $names, "Expected tag set via setTagsText() to come back from getTagNames()");
        $this->assertContains('two', $names, "Expected tag set via setTagsText() to come back from getTagNames()");
        $this->assertContains('three', $names, "Expected tag set via setTagsText() to come back from getTagNames()");
    }

    /**
     * AUTO TAGS
     */

    /**
     * @group tags
     */
    public function testAutoTags(): void
    {
        $image = new Image();
        $this->assertIsArray($image->getAutoTags(), "Expected no auto tags still to be an array");
        $this->assertEmpty($image->getAutoTags(), "Expected no auto tags to be an empty array");
        $this->assertEquals(0, $image->getAutoTagsCount(), "Expected empty tags to have a zero count.");

        $image->setAutoTags(null);
        $this->assertIsArray($image->getAutoTags(), "Expected setting Auto Tags to null still to be an array");
        $this->assertEmpty($image->getAutoTags(), "Expected setting Auto Tags to null to still be an empty array");

        $image->setAutoTags([]);
        $this->assertIsArray($image->getAutoTags(), "Expected setting Auto Tags to empty array to result in array");
        $this->assertEmpty($image->getAutoTags(), "Expected setting Auto Tags to empty array to result in an empty array");

        $image->setAutoTags(['foo', 'bar', 'baz']);
        $this->assertEquals(3, $image->getAutoTagsCount(), "Set three auto tags, expected count of 3");
        $this->assertEquals(['foo', 'bar', 'baz'], $image->getAutoTags(), "Expected to get same tags out as we put in.");
        $image->setAutoTags(['foop']);
        /** @var array<string> $tags */
        $tags = $image->getAutoTags();
        $this->assertCount(1, $tags, "Resetting auto tags with a single tag should result in a tag count of one.");
        $this->assertEquals(1, $image->getAutoTagsCount(), "Resetting to one auto tag should result in getAutoTagsCount() of 1");
    }

    /**
     * TEXT TAGS
     */

    /**
     * @group tags
     */
    public function testTextTags(): void
    {
        $image = new Image();
        $this->assertIsArray($image->getTextTags(), "Expected no text tags still to be an array");
        $this->assertEmpty($image->getTextTags(), "Expected no text tags to be an empty array");
        $this->assertEquals(0, $image->getTextTagsCount(), "Expected empty text tags to have a zero count.");

        $image->setTextTags([]);
        $this->assertIsArray($image->getTextTags(), "Expected setting text Tags to empty array to result in array");
        $this->assertEmpty($image->getTextTags(), "Expected setting text Tags to empty array to result in an empty array");

        $image->setTextTags(['foo', 'bar', 'baz']);
        $this->assertEquals(3, $image->getTextTagsCount(), "Set three text tags, expected count of 3");
        $this->assertEquals(['foo', 'bar', 'baz'], $image->getTextTags(), "Expected to get same text tags out as we put in.");
        $image->setTextTags(['foop']);
        /** @var array<string> $tags */
        $tags = $image->getTextTags();
        $this->assertCount(1, $tags, "Resetting text tags with a single tag should result in a tag count of one.");
        $this->assertEquals(1, $image->getTextTagsCount(), "Resetting to one text tag should result in getTextTagsCount() of 1");
    }
}
