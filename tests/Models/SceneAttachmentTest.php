<?php
declare(strict_types=1);

namespace LotGD\Core\Tests\Models;

use LotGD\Core\Attachment;
use LotGD\Core\Exceptions\ArgumentException;
use LotGD\Core\Models\{Scene, SceneAttachment, SceneConnection, SceneConnectionGroup, SceneTemplate};
use LotGD\Core\Tests\CoreModelTestCase;
use LotGD\Core\Tests\SceneTemplates\NewSceneSceneTemplate;

class TestAttachment extends Attachment
{

}

class InvalidTestAttachment
{

}

/**
 * Tests for creating scenes and moving them around.
 */
class SceneAttachmentTest extends CoreModelTestCase
{
    /** @var string default data set */
    protected $dataset = "scene";

    public function testSceneAttachmentCreationWithValidParameters()
    {
        $sceneAttachment = new SceneAttachment(TestAttachment::class, "Test Attachment");

        $this->assertInstanceOf(SceneAttachment::class, $sceneAttachment);
    }

    public function testIfSceneAttachmentCreationFailsIfAttachmentIsNotSubclassOfAttachment()
    {
        $this->expectException(ArgumentException::class);

        $sceneAttachment = new SceneAttachment(InvalidTestAttachment::class, "Invalid Test Attachment");
    }

    public function testIfValidSceneAttachmentCanBePersistedAndGottenBackFromTheDatabase()
    {
        $em = $this->getEntityManager();
        $sceneAttachment = new SceneAttachment(TestAttachment::class, "Test Attachment");

        // persist
        $em->persist($sceneAttachment);
        $em->flush();
        $em->clear();

        // retrieve
        $retrievedSceneAttachment = $em->getRepository(SceneAttachment::class)->find(TestAttachment::class);

        $this->assertInstanceOf(SceneAttachment::class, $retrievedSceneAttachment);

        // Delete again
        $em->remove($retrievedSceneAttachment);
        $em->flush();
    }

    public function testIfSceneGettersReturnGivenValuesProperly()
    {
        $sceneAttachment = new SceneAttachment(TestAttachment::class, "Test Attachment");

        $this->assertSame($sceneAttachment->getClass(), TestAttachment::class);
        $this->assertSame($sceneAttachment->getTitle(), "Test Attachment");
    }

    public function testIfPersistingASceneAlsoPersistsASceneAttachment()
    {
        $em = $this->getEntityManager();

        $scene = new Scene("Test scene", "A test scene");
        $sceneAttachment = new SceneAttachment(TestAttachment::class, "Test Attachment");

        $scene->addAttachment($sceneAttachment);
        $sceneId = $scene->getId();

        // persist
        $em->persist($scene);
        $em->flush();
        $em->clear();

        // retrieve
        $retrievedSceneAttachment = $em->getRepository(SceneAttachment::class)->find(TestAttachment::class);

        // assert
        $this->assertInstanceOf(SceneAttachment::class, $retrievedSceneAttachment);
        $this->assertCount(1, $retrievedSceneAttachment->getScenes());

        // remove scene
        $scene = $retrievedSceneAttachment->getScenes()[0];
        $em->remove($scene);
        $em->flush();
        $em->clear();

        // retrieve
        $retrievedSceneAttachment = $em->getRepository(SceneAttachment::class)->find(TestAttachment::class);

        // assert
        $this->assertInstanceOf(SceneAttachment::class, $retrievedSceneAttachment);
        $this->assertCount(0, $retrievedSceneAttachment->getScenes());

        // remove attachment
        $em->remove($retrievedSceneAttachment);
        $em->flush();
        $em->clear();

        // retrieve
        $retrievedSceneAttachment = $em->getRepository(SceneAttachment::class)->find(TestAttachment::class);

        // assert
        $this->assertNull($retrievedSceneAttachment);
    }
}
