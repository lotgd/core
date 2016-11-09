<?php
declare(strict_types=1);

namespace LotGD\Core\Tests\Models;

use LotGD\Core\Action;
use LotGD\Core\ActionGroup;
use LotGD\Core\Attachment;
use LotGD\Core\Models\Character;
use LotGD\Core\Models\Viewpoint;
use LotGD\Core\Models\Scene;
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
        $testCharacter = $em->getRepository(Character::class)->find(2);
        $this->assertSame(2, $testCharacter->getId());
        $characterScene = $testCharacter->getViewpoint();

        $this->assertInstanceOf(Viewpoint::class, $characterScene);
        $this->assertSame("The Village", $characterScene->getTitle());
        $this->assertSame("This is the village.", $characterScene->getDescription());

        // Test character without a characterScene
        $testCharacter = $em->getRepository(Character::class)->find(1);
        $this->assertSame(1, $testCharacter->getId());
        $characterScene = $testCharacter->getViewpoint();

        $this->assertNull($characterScene);

        $em->flush();
    }

    // Tests if a scene can be changed correctly.
    public function testSceneChange()
    {
        $em = $this->getEntityManager();

        $testCharacter = $em->getRepository(Character::class)->find(2);

        $testScene = $em->getRepository(Scene::class)->find(2);

        $this->assertSame("The Village", $testCharacter->getViewpoint()->getTitle());

        $testCharacter->getViewpoint()->changeFromScene($testScene);

        $this->assertSame("The Forest", $testCharacter->getViewpoint()->getTitle());

        $em->flush();
    }

    public function testActions()
    {
        $em = $this->getEntityManager();

        $ag1 = new ActionGroup('id1', 'title1', 42);
        $ag1->setActions([
            new Action(1),
            new Action(2)
        ]);
        $ag2 = new ActionGroup('id2', 'title2', 101);
        $ag2->setActions([
            new Action(3)
        ]);

        $actionGroups = [
            $ag1,
            $ag2
        ];

        $input = $em->getRepository(Viewpoint::class)->find(2);
        $input->setActionGroups($actionGroups);
        $input->save($em);

        $em->clear();

        $output = $em->getRepository(Viewpoint::class)->find(2);
        $this->assertEquals($actionGroups, $output->getActionGroups());

        $this->assertEquals($ag2, $input->findActionGroupById('id2'));
        $this->assertNull($input->findActionGroupById('not-there'));

        $testAction = new Action(4);
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

        $input = $em->getRepository(Viewpoint::class)->find(2);
        $input->setAttachments($attachments);
        $input->save($em);

        $em->clear();

        $output = $em->getRepository(Viewpoint::class)->find(2);
        $this->assertEquals($attachments, $output->getAttachments());
        $this->assertEquals('baz', $output->getAttachments()[0]->getFoo());
        $this->assertEquals('fiz', $output->getAttachments()[1]->getFoo());
    }
}
