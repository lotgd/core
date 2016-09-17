<?php
declare(strict_types=1);

namespace LotGD\Core\Tests\Models;

use Doctrine\Common\Collections\ArrayCollection;

use LotGD\Core\Models\Scene;
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
        $this->assertInstanceOf(Scene::class, $scene->getParents()[0]);
        $this->assertCount(1, $scene->getParents());
        $this->assertCount(0, $scene->getChildren());

        $em->flush();
    }

    /**
     * Test if parent<=>child relationship is working.
     */
    public function testChildParentRelationships()
    {
        $em = $this->getEntityManager();

        $parentScene = $em->getRepository(Scene::class)->find(1);
        $childScene = $em->getRepository(Scene::class)->find(2);

        $this->assertContains($parentScene, $childScene->getParents());
        $this->assertContains($childScene, $parentScene->getChildren());

        $em->flush();
    }

    /**
     * Test if the scene can be removed.
     */
    public function testMoveScene()
    {
        $em = $this->getEntityManager();

        $parentScene1 = $em->getRepository(Scene::class)->find(1);
        $parentScene2 = $em->getRepository(Scene::class)->find(4);

        $orphanScene = $em->getRepository(scene::class)->find(5);
        $this->assertCount(0, $orphanScene->getParents());
        $this->assertCount(0, $orphanScene->getChildren());

        // Assign orphanScene to parentScene1 and check relationships
        $orphanScene->addParent($parentScene1);

        $this->assertCount(1, $orphanScene->getParents());
        $this->assertCount(3, $parentScene1->getChildren());
        $this->assertContains($parentScene1, $orphanScene->getParents());
        $this->assertContains($orphanScene, $parentScene1->getChildren());

        // Add the scene now to parentScene2 and check relationships
        $orphanScene->addParent($parentScene2);

        $this->assertCount(3, $parentScene1->getChildren());
        $this->assertCount(1, $parentScene2->getChildren());
        $this->assertContains($parentScene2, $orphanScene->getParents());
        $this->assertContains($orphanScene, $parentScene2->getChildren());

        // Make an orphan out of it again
        $orphanScene->setParents(new ArrayCollection());

        $this->assertCount(2, $parentScene1->getChildren());
        $this->assertCount(0, $parentScene2->getChildren());
        $this->assertCount(0, $orphanScene->getParents());
        $this->assertNotContains($orphanScene, $parentScene1->getChildren());
        $this->assertNotContains($orphanScene, $parentScene2->getChildren());

        $em->flush();
    }
}
