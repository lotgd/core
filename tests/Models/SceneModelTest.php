<?php
declare(strict_types=1);

namespace LotGD\Core\Tests\Models;

use Doctrine\Common\Collections\ArrayCollection;

use LotGD\Core\Exceptions\ArgumentException;
use LotGD\Core\Models\Scene;
use LotGD\Core\Models\SceneConnectionGroup;
use LotGD\Core\Models\SceneConnection;
use LotGD\Core\Tests\CoreModelTestCase;

/**
 * Tests for creating scenes and moving them around.
 */
class SceneModelTest extends CoreModelTestCase
{
    /** @var string default data set */
    protected $dataset = "scene";

    public function testCreate()
    {
        $scene = new Scene();
    }

    /**
     * Test getter methods
     */
    public function testGetters()
    {
        $em = $this->getEntityManager();
        $scene = $em->getRepository(Scene::class)->find(2);

        $this->assertEquals("The Forest", $scene->getTitle());
        $this->assertEquals("This is a very dangerous and dark forest", $scene->getDescription());

        $em->flush();
    }

    public function testIfHasConnectionGroupReturnsTrueIfConnectionGroupExists()
    {
        $scene = $this->getEntityManager()->getRepository(Scene::class)->find(1);

        $this->assertTrue($scene->hasConnectionGroup("lotgd/tests/village/outside"));
        $this->assertTrue($scene->hasConnectionGroup("lotgd/tests/village/market"));
        $this->assertTrue($scene->hasConnectionGroup("lotgd/tests/village/empty"));
    }

    public function testIfHasConnectionGroupReturnsFalseIfConnectionGroupDoesNotExist()
    {
        $scene2 = $this->getEntityManager()->getRepository(Scene::class)->find(2);

        $this->assertFalse($scene2->hasConnectionGroup("lotgd/tests/village/outside"));
        $this->assertFalse($scene2->hasConnectionGroup("lotgd/tests/village/market"));
        $this->assertFalse($scene2->hasConnectionGroup("lotgd/tests/village/empty"));


        $scene1 = $this->getEntityManager()->getRepository(Scene::class)->find(1);

        $this->assertFalse($scene1->hasConnectionGroup("lotgd/tests/village/23426"));
    }

    public function testIfAddConnectionGroupWorks()
    {
        $connectionGroup = new SceneConnectionGroup("lotgd/tests/village/new", "New Street");
        $scene = $this->getEntityManager()->getRepository(Scene::class)->find(1);

        $this->assertFalse($scene->hasConnectionGroup("lotgd/tests/village/new"));

        $scene->addConnectionGroup($connectionGroup);

        $this->getEntityManager()->flush();

        $this->assertTrue($scene->hasConnectionGroup("lotgd/tests/village/new"));
    }

    public function testIfAddConnectionGroupThrowsArgumentExceptionIfGroupIsAlreadyAssignedToItself()
    {
        $scene = $this->getEntityManager()->getRepository(Scene::class)->find(1);
        $connectionGroup = $this->getEntityManager()->getRepository(SceneConnectionGroup::class)->findOneBy(["scene" => 1, "name" => "lotgd/tests/village/outside"]);

        $this->expectException(ArgumentException::class);
        $scene->addConnectionGroup($connectionGroup);
    }

    public function testIfAddConnectionGroupThrowsArgumentExceptionIfGroupIsAlreadyAssignedToSomwhereElse()
    {
        $scene = $this->getEntityManager()->getRepository(Scene::class)->find(2);
        $connectionGroup = $this->getEntityManager()->getRepository(SceneConnectionGroup::class)->findOneBy(["scene" => 1, "name" => "lotgd/tests/village/outside"]);

        $this->expectException(ArgumentException::class);
        $scene->addConnectionGroup($connectionGroup);
    }

    public function testifDropConnectionGroupWorks()
    {
        $scene = $this->getEntityManager()->getRepository(Scene::class)->find(1);
        $connectionGroup = $this->getEntityManager()->getRepository(SceneConnectionGroup::class)->findOneBy(["scene" => 1, "name" => "lotgd/tests/village/outside"]);

        $this->assertTrue($scene->hasConnectionGroup("lotgd/tests/village/outside"));

        $scene->dropConnectionGroup($connectionGroup);

        $this->getEntityManager()->flush();

        $this->assertFalse($scene->hasConnectionGroup("lotgd/tests/village/outside"));
    }

    public function testIfDropConnectionGroupThrowsArgumentExceptionIfEntityIsRemovedFromNonOwningScene()
    {
        $scene = $this->getEntityManager()->getRepository(Scene::class)->find(2);
        $connectionGroup = $this->getEntityManager()->getRepository(SceneConnectionGroup::class)->findOneBy(["scene" => 1, "name" => "lotgd/tests/village/outside"]);

        $this->expectException(ArgumentException::class);
        $scene->dropConnectionGroup($connectionGroup);
    }

    public function testIfGetConnectedScenesReturnsConnectedScenes()
    {
        $scene1 = $this->getEntityManager()->getRepository(Scene::class)->find(1);
        $scene2 = $this->getEntityManager()->getRepository(Scene::class)->find(2);

        $this->assertCount(3, $scene1->getConnectedScenes());
        $this->assertCount(1, $scene2->getConnectedScenes());

        $this->assertTrue($scene1->getConnectedScenes()->contains($scene2));
        $this->assertTrue($scene2->getConnectedScenes()->contains($scene1));
        $this->assertFalse($scene1->getConnectedScenes()->contains($scene1));
        $this->assertFalse($scene2->getConnectedScenes()->contains($scene2));
    }

    public function testIfIsConnectedToReturnsExpectedReturnValue()
    {
        $scene1 = $this->getEntityManager()->getRepository(Scene::class)->find(1);
        $scene2 = $this->getEntityManager()->getRepository(Scene::class)->find(2);
        $scene5 = $this->getEntityManager()->getRepository(Scene::class)->find(5);

        $this->assertTrue($scene1->isConnectedTo($scene2));
        $this->assertTrue($scene2->isConnectedTo($scene1));
        $this->assertFalse($scene1->isConnectedTo($scene5));
        $this->assertFalse($scene2->isConnectedTo($scene5));
        $this->assertFalse($scene5->isConnectedTo($scene1));
        $this->assertFalse($scene5->isConnectedTo($scene2));
    }

    public function testIfTwoScenesCanGetConnected()
    {
        $scene1 = $this->getEntityManager()->getRepository(Scene::class)->find(2);
        $scene2 = $this->getEntityManager()->getRepository(Scene::class)->find(5);

        $scene1->connect($scene2);

        $this->assertTrue($scene1->getConnectedScenes()->contains($scene2));
        $this->assertTrue($scene2->getConnectedScenes()->contains($scene1));
        $this->assertFalse($scene1->getConnectedScenes()->contains($scene1));
        $this->assertFalse($scene2->getConnectedScenes()->contains($scene2));

        $this->getEntityManager()->flush();
    }

    public function testIfASceneConnectionGroupCanGetConnectedToAScene()
    {
        $scene1 = $this->getEntityManager()->getRepository(Scene::class)->find(1);
        $scene2 = $this->getEntityManager()->getRepository(Scene::class)->find(5);

        $scene1->getConnectionGroup("lotgd/tests/village/outside")->connect($scene2);

        $this->assertTrue($scene1->isConnectedTo($scene2));
        $this->assertTrue($scene2->isConnectedTo($scene1));
        $this->assertFalse($scene1->isConnectedTo($scene1));
        $this->assertFalse($scene2->isConnectedTo($scene2));

        $this->getEntityManager()->flush();
    }

    public function testIfASceneCanGetConnectedToASceneConnectionGroup()
    {
        $scene1 = $this->getEntityManager()->getRepository(Scene::class)->find(1);
        $scene2 = $this->getEntityManager()->getRepository(Scene::class)->find(5);

        $scene2->connect($scene1->getConnectionGroup("lotgd/tests/village/outside"));

        $this->assertTrue($scene1->isConnectedTo($scene2));
        $this->assertTrue($scene2->isConnectedTo($scene1));
        $this->assertFalse($scene1->isConnectedTo($scene1));
        $this->assertFalse($scene2->isConnectedTo($scene2));

        $this->getEntityManager()->flush();
    }

    public function testIfASceneConnectionGroupCanGetConnectedToAnotherSceneConnectionGroup()
    {
        $scene1 = $this->getEntityManager()->getRepository(Scene::class)->find(1);
        $scene2 = $this->getEntityManager()->getRepository(Scene::class)->find(5);
        $scene2->addConnectionGroup(new SceneConnectionGroup("test/orphaned", "Orphan group"));

        $scene1
            ->getConnectionGroup("lotgd/tests/village/outside")
            ->connect(
                $scene2->getConnectionGroup("test/orphaned")
            );

        $this->assertTrue($scene1->isConnectedTo($scene2));
        $this->assertTrue($scene2->isConnectedTo($scene1));
        $this->assertFalse($scene1->isConnectedTo($scene1));
        $this->assertFalse($scene2->isConnectedTo($scene2));

        $this->getEntityManager()->flush();
    }

    public function testIfConnectingASceneToItselfThrowsAnException()
    {
        $scene1 = $this->getEntityManager()->getRepository(Scene::class)->find(1);

        $this->expectException(ArgumentException::class);
        $scene1->connect($scene1);

        $this->expectException(ArgumentException::class);
        $scene1->connect($scene1->getConnectionGroup("lotgd/tests/village/outside"));

        $this->expectException(ArgumentException::class);
        $scene1->getConnectionGroup("lotgd/tests/village/outside")->connect($scene1);

        $this->expectException(ArgumentException::class);
        $scene1->getConnectionGroup("lotgd/tests/village/outside")->connect($scene1->getConnectionGroup("lotgd/tests/village/outside"));

        $this->assertFalse($scene1->isConnectedTo($scene1));
    }

    public function testIfConnectingASceneToAnotherAlreadyConnectedSceneThrowsAnException()
    {
        $scene1 = $this->getEntityManager()->getRepository(Scene::class)->find(1);
        $scene2 = $this->getEntityManager()->getRepository(Scene::class)->find(2);

        $this->expectException(ArgumentException::class);
        $scene1->connect($scene2);

        $this->expectException(ArgumentException::class);
        $scene1->getConnectionGroup("lotgd/tests/village/hidden")->connect($scene2);

        $this->expectException(ArgumentException::class);
        $scene1->connect($scene2->getConnectionGroup("lotgd/tests/forest/category"));

        $this->expectException(ArgumentException::class);
        $scene1->getConnectionGroup("lotgd/tests/village/hidden")->connect($scene2->getConnectionGroup("lotgd/tests/forest/category"));
    }
}
