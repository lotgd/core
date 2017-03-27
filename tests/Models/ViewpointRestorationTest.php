<?php
declare(strict_types=1);

namespace LotGD\Core\Tests\Models;

use LotGD\Core\Action;
use LotGD\Core\ActionGroup;
use LotGD\Core\Models\Scene;
use LotGD\Core\Models\Viewpoint;
use LotGD\Core\Tests\CoreModelTestCase;

class ViewpointRestorationTest extends CoreModelTestCase
{
    /** @var string default data set */
    protected $dataset = "viewpoints";

    protected function getActionGroup()
    {
        static $actionGroup = null;
        if ($actionGroup !== null) {
            $actionGroup = new ActionGroup("main", "Title", 0);
            $actionGroup->addAction(new Action(1));
            $actionGroup->addAction(new Action(2));
            $actionGroup->addAction(new Action(3));
        }

        return $actionGroup;
    }

    protected function getViewpoint()
    {
        $sceneMock = $this->createMock(Scene::class);
        $sceneMock->method("getTitle")->willReturn("Scene Mock Title");
        $sceneMock->method("getDescription")->willReturn("Scene Mock Description");
        $sceneMock->method("getTemplate")->willReturn("lotgd/scene-mock-template");

        $actionGroup = $this->getActionGroup();

        $viewpoint = new Viewpoint();
        $viewpoint->changeFromScene($sceneMock);
        $viewpoint->setActionGroups([$actionGroup]);

        return $viewpoint;
    }

    protected function getAlternativeViewpoint()
    {
        $sceneMock = $this->createMock(Scene::class);
        $sceneMock->method("getTitle")->willReturn("Another Scene Mock Title");
        $sceneMock->method("getDescription")->willReturn("Another Scene Mock Description");
        $sceneMock->method("getTemplate")->willReturn("lotgd/scene-mock-template/another");

        $viewpoint = new Viewpoint();
        $viewpoint->changeFromScene($sceneMock);

        return $viewpoint;
    }

    public function testIfViewpointAfterUnserializationIsEqualToBeforeItsSerialization()
    {
        $viewpoint = $this->getViewpoint();
        $viewpointRestoration = $this->getViewpoint()->getRestorationPoint();
        $serialized = serialize($viewpointRestoration);
        $viewpointRestored = unserialize($serialized);

        $newViewpoint = $this->getAlternativeViewpoint();
        $newViewpoint->changeFromRestorationPoint($viewpointRestored);

        $this->assertSame($viewpoint->getTitle(), $newViewpoint->getTitle());
        $this->assertSame($viewpoint->getDescription(), $newViewpoint->getDescription());
        $this->assertSame($viewpoint->getTemplate(), $newViewpoint->getTemplate());
        $this->assertEquals($viewpoint->getActionGroups(), $newViewpoint->getActionGroups());;
        $this->assertSame($viewpoint->getData(), $newViewpoint->getData());
        $this->assertSame($viewpoint->getAttachments(), $newViewpoint->getAttachments());
    }
}