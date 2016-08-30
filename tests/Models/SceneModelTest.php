<?php
declare(strict_types=1);

namespace LotGD\Core\Tests\Models;

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
        $this->assertInstanceOf(Scene::class, $scene->getParent());
        $this->assertEquals(true, $scene->hasParent());
        $this->assertEquals(false, $scene->hasChildren());

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

        $this->assertEquals($parentScene, $childScene->getParent());
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
        $this->assertEquals(false, $orphanScene->hasParent());
        $this->assertEquals(false, $orphanScene->hasChildren());

        // Assign orphanScene to parentScene1 and check relationships
        $orphanScene->setParent($parentScene1);

        $this->assertEquals(true, $orphanScene->hasParent());
        $this->assertCount(3, $parentScene1->getChildren());
        $this->assertEquals($parentScene1, $orphanScene->getParent());
        $this->assertContains($orphanScene, $parentScene1->getChildren());

        // Move the scene now to parentScene2 and check relationships
        $orphanScene->setParent($parentScene2);

        $this->assertCount(2, $parentScene1->getChildren());
        $this->assertCount(1, $parentScene2->getChildren());
        $this->assertEquals($parentScene2, $orphanScene->getParent());
        $this->assertContains($orphanScene, $parentScene2->getChildren());

        // Make an orphan out of it again
        $orphanScene->setParent(null);

        $this->assertCount(2, $parentScene1->getChildren());
        $this->assertCount(0, $parentScene2->getChildren());
        $this->assertEquals(false, $orphanScene->hasParent());
        $this->assertNotContains($orphanScene, $parentScene1->getChildren());
        $this->assertNotContains($orphanScene, $parentScene2->getChildren());

        $em->flush();
    }
}
