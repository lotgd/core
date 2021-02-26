<?php
declare(strict_types=1);

namespace LotGD\Core\Tests\Models;

use LotGD\Core\Models\Scene;
use LotGD\Core\Models\SceneProperty;
use LotGD\Core\Tests\CoreModelTestCase;

class ScenePropertyTest extends CoreModelTestCase
{
    /** @var string default data set */
    protected $dataset = "scene-property";

    public function testIfScenePropertyCanBeRetrieved()
    {
        $em = $this->getEntityManager();

        # Retrieve scene
        $scene = $em->getRepository(Scene::class)->find("30000000-0000-0000-0000-000000000001");

        # Fetch property, with default "false".
        # Must return true, as this is whats in our dataset
        $value = $scene->getProperty("lotgd/core/tests/property/dataset", false);

        # Assert the value
        $this->assertTrue($value);
    }

    public function testIfUnknownScenePropertyCanBeRetrieved()
    {
        $em = $this->getEntityManager();

        # Retrieve scene
        $scene = $em->getRepository(Scene::class)->find("30000000-0000-0000-0000-000000000002");

        # Fetch property, with default "false".
        # Must return true, as this is whats in our dataset
        $value = $scene->getProperty("lotgd/core/tests/property/dataset");

        # Assert the value
        $this->assertNull($value);
    }

    public function testIfFreshlyCreatedSceneCanGetAccessedProperties()
    {
        $em = $this->getEntityManager();

        # Retrieve scene
        $scene = new Scene(title: "A new scene", description: "Hallo Welt", template: null);

        # Fetch property, with default "false".
        # Must return true, as this is whats in our dataset
        $value = $scene->getProperty("lotgd/core/tests/property/dataset");

        # Assert the value
        $this->assertNull($value);
    }

    public function testIfPropertyCanBeSaved()
    {
        $em = $this->getEntityManager();

        # Retrieve scene
        $scene = $em->getRepository(Scene::class)->find("30000000-0000-0000-0000-000000000001");

        # Set a property
        $scene->setProperty("lotgd/core/tests/newProperty", "test-value");

        # Get the property value back
        $value = $scene->getProperty("lotgd/core/tests/newProperty", null);

        # Assert the value
        $this->assertSame("test-value", $value);

        # Save and clear entity manager, the fetch again.
        # Persisting the property should not be necessary
        unset($value);
        $em->flush();
        $em->clear();

        # Retrieve scene (again)
        $scene = $em->getRepository(Scene::class)->find("30000000-0000-0000-0000-000000000001");

        # Get a property
        $value = $scene->getProperty("lotgd/core/tests/newProperty", null);

        # Assert the value
        $this->assertSame("test-value", $value);
    }

    public function testIfSceneRemovalRemovesPropertyToo()
    {
        $em = $this->getEntityManager();

        # Retrieve scene
        $scene = $em->getRepository(Scene::class)->find("30000000-0000-0000-0000-000000000001");

        # Delete scene
        $em->remove($scene);
        $em->flush();
        $em->clear();

        # Retrieve properties
        $sceneProperties = $em->getRepository(SceneProperty::class)->findAll();

        # Get all property names
        $listOfPropertyNames = [];
        foreach ($sceneProperties as $property) {
            $listOfPropertyNames[$property->getName()] = $property->getValue();
        }

        $this->assertArrayNotHasKey("lotgd/core/tests/property/dataset", $listOfPropertyNames);
    }
}