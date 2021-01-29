<?php
declare(strict_types=1);

namespace LotGD\Core\Tests\Models;

use LotGD\Core\Action;
use LotGD\Core\ActionGroup;
use LotGD\Core\Attachment;
use LotGD\Core\Exceptions\ArgumentException;
use LotGD\Core\Models\{Character, Scene, SceneAttachment, SceneConnection, SceneConnectionGroup, SceneTemplate};
use LotGD\Core\Game;
use LotGD\Core\Tests\CoreModelTestCase;
use LotGD\Core\Tests\SceneTemplates\NewSceneSceneTemplate;

class TestAttachment extends Attachment
{
    public function getData(): array
    {
        return [];
    }

    public function getActions(): array
    {
        return [];
    }
}

class TestAttachmentWithActions extends Attachment
{
    public Action $action;
    public static string $actionId;
    public static string $attachmentId;

    public function __construct(Game $game, Scene $scene)
    {
        parent::__construct($game, $scene);

        $this->action = new Action($scene->getId(), "Attachment Action");
        self::$attachmentId = $this->getId();
        self::$actionId = $this->action->getId();
    }

    public function getData(): array
    {
        return [];
    }

    public function getActions(): array
    {
        return [$this->action];
    }
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
    protected $dataset = "scene-attachment";

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

        $scene->addSceneAttachment($sceneAttachment);
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

    public function testIfGameLoopAddsAttachmentsToInstances()
    {
        $em = $this->getEntityManager();
        $game = $this->g;

        // Get the character
        /** @var Character $character */
        $character = $em->getRepository(Character::class)->find("10000000-0000-0000-0000-000000000002");
        $this->assertNotNull($character);

        // Set them as active
        $this->g->setCharacter($character);

        // Get the target scene and add an active action to it
        /** @var Scene $targetScene */
        $targetScene = $em->getRepository(Scene::class)->find("30000000-0000-0000-0000-000000000002");
        $viewpoint = $character->getViewpoint();
        $actionGroup = new ActionGroup("action-group", "action group", 0);
        $action = new Action($targetScene->getId(), "To the forest");
        $actionId = $action->getId();
        $actionGroup->addAction($action);
        $viewpoint->addActionGroup($actionGroup);

        // Change the viewpoint by taking an action.
        $game->takeAction($actionId);

        // Assert that target scene has the desired attachment
        $newViewpoint = $character->getViewpoint();

        $this->assertCount(1, $newViewpoint->getAttachments());
        $this->assertSame(TestAttachmentWithActions::$attachmentId, $newViewpoint->getAttachments()[0]->getId());
    }
}
