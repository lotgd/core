<?php
declare(strict_types=1);

namespace LotGD\Core\Tests\Models;

use Composer\Repository\RepositoryInterface;
use Doctrine\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManager;
use LotGD\Core\Action;
use LotGD\Core\ActionGroup;
use LotGD\Core\Models\Scene;
use LotGD\Core\Models\SceneTemplate;
use LotGD\Core\Models\Viewpoint;
use LotGD\Core\Services\TwigSceneRenderer;
use LotGD\Core\Tests\CoreModelTestCase;

class ViewpointRestorationTest extends CoreModelTestCase
{
    /** @var string default data set */
    protected $dataset = "viewpoints";

    protected function getActionGroup()
    {
        static $actionGroup = null;
        if ($actionGroup === null) {
            $actionGroup = new ActionGroup("main", "Title", 0);
            $actionGroup->addAction(new Action("30000000-0000-0000-0000-000000000001"));
            $actionGroup->addAction(new Action("30000000-0000-0000-0000-000000000002"));
            $actionGroup->addAction(new Action("30000000-0000-0000-0000-000000000003"));
        }

        return $actionGroup;
    }

    protected function getViewpoint($useNullTemplate = false)
    {
        $sceneTemplateMock = null;
        if ($useNullTemplate === false) {
            $sceneTemplateMock = $this->createMock(SceneTemplate::class);
        }

        $sceneMock = $this->createMock(Scene::class);
        $sceneMock->method("getTitle")->willReturn("Scene Mock Title");
        $sceneMock->method("getDescription")->willReturn("Scene Mock Description");
        $sceneMock->method("getTemplate")->willReturn($sceneTemplateMock);

        $rendererMock = $this->createMock(TwigSceneRenderer::class);
        $rendererMock->method("render")->willReturnArgument(0);

        $repository = $this->createMock(ObjectRepository::class);
        $repository->method("find")->willReturn($sceneTemplateMock);

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->method("getRepository")->willReturn($repository);

        $actionGroup = $this->getActionGroup();

        $viewpoint = new Viewpoint();
        $viewpoint->changeFromScene($sceneMock, $rendererMock);
        $viewpoint->setActionGroups([$actionGroup]);

        return [$entityManager, $viewpoint];
    }

    protected function getAlternativeViewpoint($useNullTemplate = false)
    {
        $sceneTemplateMock = null;
        if ($useNullTemplate === false) {
            $sceneTemplateMock = $this->createMock(SceneTemplate::class);
        }

        $sceneMock = $this->createMock(Scene::class);
        $sceneMock->method("getTitle")->willReturn("Another Scene Mock Title");
        $sceneMock->method("getDescription")->willReturn("Another Scene Mock Description");
        $sceneMock->method("getTemplate")->willReturn($sceneTemplateMock);

        $rendererMock = $this->createMock(TwigSceneRenderer::class);
        $rendererMock->method("render")->willReturnArgument(0);

        $repository = $this->createMock(ObjectRepository::class);
        $repository->method("find")->willReturn($sceneTemplateMock);

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->method("getRepository")->willReturn($repository);

        $viewpoint = new Viewpoint();
        $viewpoint->changeFromScene($sceneMock, $rendererMock);

        return [$entityManager, $viewpoint];
    }

    public function testIfViewpointAfterUnserializationIsEqualToBeforeItsSerialization()
    {
        [$entityManager, $viewpoint] = $this->getViewpoint();
        $viewpointRestoration = $viewpoint->getSnapshot();
        $serialized = serialize($viewpointRestoration);
        $viewpointRestored = unserialize($serialized);

        [$entityManager2, $newViewpoint] = $this->getAlternativeViewpoint();
        $newViewpoint->changeFromSnapshot($entityManager, $viewpointRestored);

        $this->assertSame($viewpoint->getTitle(), $newViewpoint->getTitle());
        $this->assertSame($viewpoint->getDescription(), $newViewpoint->getDescription());
        $this->assertSame($viewpoint->getTemplate(), $newViewpoint->getTemplate());

        for ($i=0; $i < count($viewpoint->getActionGroups()); $i++) {
            $should = $viewpoint->getActionGroups()[$i];
            $is = $newViewpoint->getActionGroups()[$i];

            $this->assertSame($should->getId(), $is->getId());
            $this->assertSame($should->getTitle(), $is->getTitle());
            $this->assertSame($should->getSortKey(), $is->getSortKey());
            $this->assertSame(count($should->getActions()), count($is->getActions()));
        }

        $this->assertSame($viewpoint->getData(), $newViewpoint->getData());
        $this->assertSame($viewpoint->getAttachments(), $newViewpoint->getAttachments());
    }

    public function testIfViewpointAfterUnserializationIsEqualToBeforeItsSerializationWhenTemplateIsNull()
    {
        [$entityManager, $viewpoint] = $this->getViewpoint(useNullTemplate: true);
        $viewpointRestoration = $viewpoint->getSnapshot();
        $serialized = serialize($viewpointRestoration);
        $viewpointRestored = unserialize($serialized);

        [$entityManager2, $newViewpoint] = $this->getAlternativeViewpoint(useNullTemplate: true);
        $newViewpoint->changeFromSnapshot($entityManager, $viewpointRestored);

        $this->assertSame($viewpoint->getTitle(), $newViewpoint->getTitle());
        $this->assertSame($viewpoint->getDescription(), $newViewpoint->getDescription());
        $this->assertSame($viewpoint->getTemplate(), $newViewpoint->getTemplate());

        for ($i=0; $i < count($viewpoint->getActionGroups()); $i++) {
            $should = $viewpoint->getActionGroups()[$i];
            $is = $newViewpoint->getActionGroups()[$i];

            $this->assertSame($should->getId(), $is->getId());
            $this->assertSame($should->getTitle(), $is->getTitle());
            $this->assertSame($should->getSortKey(), $is->getSortKey());
            $this->assertSame(count($should->getActions()), count($is->getActions()));
        }

        $this->assertSame($viewpoint->getData(), $newViewpoint->getData());
        $this->assertSame($viewpoint->getAttachments(), $newViewpoint->getAttachments());
    }
}