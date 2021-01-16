<?php
declare(strict_types=1);

namespace LotGD\Core\Tests\Models;

use LotGD\Core\Action;
use LotGD\Core\ActionGroup;
use LotGD\Core\Attachment;
use LotGD\Core\Models\Character;
use LotGD\Core\Models\Viewpoint;
use LotGD\Core\Models\Scene;
use LotGD\Core\Services\TwigSceneRenderer;
use LotGD\Core\Tests\CoreModelTestCase;

class SampleAttachment extends Attachment
{
    protected $foo;

    public function __construct(string $foo)
    {
        parent::__construct('bar');
        $this->foo = $foo;
    }

    public function getFoo()
    {
        return $this->foo;
    }
}

/**
 * Tests the management of Viewpoints
 */
class ViewpointTest extends CoreModelTestCase
{
    /** @var string default data set */
    protected $dataset = "viewpoints";

    public function testGetters()
    {
        $em = $this->getEntityManager();

        // Test character with a characterScene
        $testCharacter = $em->getRepository(Character::class)->find("10000000-0000-0000-0000-000000000002");
        $this->assertSame("10000000-0000-0000-0000-000000000002", (string)$testCharacter->getId());
        $characterScene = $testCharacter->getViewpoint();

        $this->assertInstanceOf(Viewpoint::class, $characterScene);
        $this->assertSame("The Village", $characterScene->getTitle());
        $this->assertSame("This is the village.", $characterScene->getDescription());

        // Test character without a characterScene
        $testCharacter = $em->getRepository(Character::class)->find("10000000-0000-0000-0000-000000000001");
        $this->assertSame("10000000-0000-0000-0000-000000000001", (string)$testCharacter->getId());
        $characterScene = $testCharacter->getViewpoint();

        $this->assertNull($characterScene);

        $em->flush();
    }

    // Tests if a scene can be changed correctly.
    public function testSceneChange()
    {
        $rendererMock = $this->createMock(TwigSceneRenderer::class);
        $rendererMock->method("render")->willReturnArgument(0);

        $em = $this->getEntityManager();

        $testCharacter = $em->getRepository(Character::class)->find("10000000-0000-0000-0000-000000000002");

        $testScene = $em->getRepository(Scene::class)->find("30000000-0000-0000-0000-000000000002");

        $this->assertSame("The Village", $testCharacter->getViewpoint()->getTitle());

        $testCharacter->getViewpoint()->changeFromScene($testScene, $rendererMock);

        $this->assertSame("The Forest", $testCharacter->getViewpoint()->getTitle());

        $em->flush();
    }

    public function testActions()
    {
        $em = $this->getEntityManager();

        $ag1 = new ActionGroup('id1', 'title1', 42);
        $ag1->setActions([
            new Action("30000000-0000-0000-0000-000000000001"),
            new Action("30000000-0000-0000-0000-000000000002")
        ]);
        $ag2 = new ActionGroup('id2', 'title2', 101);
        $ag2->setActions([
            new Action("30000000-0000-0000-0000-000000000003")
        ]);

        $actionGroups = [
            $ag1,
            $ag2
        ];

        $input = $em->getRepository(Viewpoint::class)->find("10000000-0000-0000-0000-000000000002");
        $input->setActionGroups($actionGroups);
        $input->save($em);

        $em->clear();

        $output = $em->getRepository(Viewpoint::class)->find("10000000-0000-0000-0000-000000000002");
        $this->assertEquals($actionGroups, $output->getActionGroups());

        $this->assertEquals($ag2, $input->findActionGroupById('id2'));
        $this->assertNull($input->findActionGroupById('not-there'));

        $testAction = new Action("30000000-0000-0000-0000-000000000004");
        $input->addActionToGroupId($testAction, 'not-there');
        $this->assertNull($input->findActionById($testAction->getId()));

        $input->addActionToGroupId($testAction, 'id2');
        $this->assertNotNull($input->findActionById($testAction->getId()));
    }

    public function testAttachments()
    {
        $em = $this->getEntityManager();

        $a1 = new SampleAttachment('baz');
        $a2 = new SampleAttachment('fiz');

        $attachments = [$a1, $a2];

        $input = $em->getRepository(Viewpoint::class)->find("10000000-0000-0000-0000-000000000002");
        $input->setAttachments($attachments);
        $input->save($em);

        $em->clear();

        $output = $em->getRepository(Viewpoint::class)->find("10000000-0000-0000-0000-000000000002");
        $this->assertEquals($attachments, $output->getAttachments());
        $this->assertEquals('baz', $output->getAttachments()[0]->getFoo());
        $this->assertEquals('fiz', $output->getAttachments()[1]->getFoo());
    }

    public function testRemoveActionsWithSceneId()
    {
        $em = $this->getEntityManager();

        $a1 = new Action("30000000-0000-0000-0000-000000000001");
        $a2 = new Action("30000000-0000-0000-0000-000000000002");
        $a3 = new Action("30000000-0000-0000-0000-000000000003");

        $ag1 = new ActionGroup('id1', 'title1', 42);
        $ag1->setActions([
            $a1,
            $a2,
            $a3
        ]);
        $ag2 = new ActionGroup('id2', 'title2', 101);
        $ag2->setActions([
            new Action("30000000-0000-0000-0000-000000000004")
        ]);

        $actionGroups = [
            $ag1,
            $ag2
        ];

        $input = $em->getRepository(Viewpoint::class)->find("10000000-0000-0000-0000-000000000002");
        $input->setActionGroups($actionGroups);
        $input->save($em);

        $em->clear();

        $output = $em->getRepository(Viewpoint::class)->find("10000000-0000-0000-0000-000000000002");

        // Not finding the scene ID should change nothing.
        $output->removeActionsWithSceneId("30000000-0000-0000-0000-000000001000");
        $this->assertEquals($actionGroups, $output->getActionGroups());

        $ag1_output = new ActionGroup('id1', 'title1', 42);
        $ag1_output->setActions([
            $a1,
            $a3
        ]);

        $actionGroupsWithout2 = [
            $ag1_output,
            $ag2
        ];
        $output->removeActionsWithSceneId("30000000-0000-0000-0000-000000000002");
        $this->assertEquals($actionGroupsWithout2, $output->getActionGroups());
    }

    public function testChangingSceneDescription()
    {
        $em = $this->getEntityManager();
        $testCharacter = $em->getRepository(Character::class)->find("10000000-0000-0000-0000-000000000002");
        $characterScene = $testCharacter->getViewpoint();

        $this->assertSame("This is the village.", $characterScene->getDescription());

        $characterScene->addDescriptionParagraph("You enjoy being here.");
        $this->assertSame("This is the village.\n\nYou enjoy being here.", $characterScene->getDescription());
    }

    public function testClearingSceneDescription()
    {
        $em = $this->getEntityManager();
        $testCharacter = $em->getRepository(Character::class)->find("10000000-0000-0000-0000-000000000002");
        $characterScene = $testCharacter->getViewpoint();

        $characterScene->clearDescription();
        $this->assertSame("", $characterScene->getDescription());

        $characterScene->addDescriptionParagraph("You enjoy being here.");
        $this->assertSame("You enjoy being here.", $characterScene->getDescription());
    }

    public function testIfGetActionGroupByIdReturnsTheCorrectActionGroupOrNull()
    {
        $em = $this->getEntityManager();

        $a1 = new Action("30000000-0000-0000-0000-000000000001");
        $a2 = new Action("30000000-0000-0000-0000-000000000002");
        $a3 = new Action("30000000-0000-0000-0000-000000000003");

        $ag1 = new ActionGroup('id1', 'title1', 42);
        $ag1->setActions([
            $a1,
            $a2,
            $a3
        ]);
        $ag2 = new ActionGroup('id2', 'title2', 101);
        $ag2->setActions([
            new Action("30000000-0000-0000-0000-000000000004")
        ]);

        $actionGroups = [
            $ag1,
            $ag2
        ];

        $input = $em->getRepository(Viewpoint::class)->find("10000000-0000-0000-0000-000000000002");
        $input->setActionGroups($actionGroups);
        $input->save($em);

        $em->clear();

        /** @var Viewpoint $viewpoint */
        $viewpoint = $em->getRepository(Viewpoint::class)->find("10000000-0000-0000-0000-000000000002");

        $actionGroupId1 = $viewpoint->findActionGroupById("id1");
        $actionGroupId2 = $viewpoint->findActionGroupById("id2");
        $actionGroupId3 = $viewpoint->findActionGroupById("id3");

        $this->assertInstanceOf(ActionGroup::class, $actionGroupId1);
        $this->assertInstanceOf(ActionGroup::class, $actionGroupId2);
        $this->assertNull($actionGroupId3);

        $actions = $actionGroupId1->getActions();
        foreach ($actions as $action) {
            $this->assertSame($action, $viewpoint->findActionById($action->getId()));
        }

        $this->assertNull($viewpoint->findActionById("anId"));
    }

    public function testIfViewpointCanGetRemovedAndDeleted()
    {
        $em = $this->getEntityManager();
        $testCharacter = $em->getRepository(Character::class)->find("10000000-0000-0000-0000-000000000002");
        $this->getEntityManager()->remove($testCharacter->getViewpoint());
        $testCharacter->setViewpoint(null);

        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        $viewpoint = $em->getRepository(Viewpoint::class)->findOneBy(["owner" => "10000000-0000-0000-0000-000000000002"]);
        $this->assertNull($viewpoint, "Viewpoint is not null");
    }
}
