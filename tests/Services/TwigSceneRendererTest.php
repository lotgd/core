<?php
declare(strict_types=1);

namespace LotGD\Core\Tests\Services;

use LotGD\Core\EventManager;
use LotGD\Core\Events\EventContextData;
use LotGD\Core\Exceptions\InsecureTwigTemplateError;
use LotGD\Core\Game;
use LotGD\Core\Models\Character;
use LotGD\Core\Models\Scene;
use LotGD\Core\Models\Viewpoint;
use LotGD\Core\PHPUnit\LotGDTestCase;
use LotGD\Core\Services\TwigSceneRenderer;

class TwigSceneRendererTest extends LotGDTestCase
{
    protected function getMockeries(): array
    {
        # Get mock character
        $character = $this->getMockBuilder(Character::class)
            ->disableOriginalConstructor()
            ->getMock();
        $character->method("getDisplayName")->willReturn("Frodo");
        $character->method("getLevel")->willReturn(5);
        $character->method("getHealth")->willReturn(10);
        $character->method("getMaxHealth")->willReturn(100);
        $character->method("isAlive")->willReturn(true);
        $character->method("getProperty")->willReturnCallback(function ($name, $default=null) {
            return $default;
        });

        # Get mock game
        $game = $this->getMockBuilder(Game::class)
            ->disableOriginalConstructor()
            ->getMock();
        $game->method("getCharacter")->willReturn($character);

        # Get event manager mock
        $eventManager = $this->getMockBuilder(EventManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $game->method("getEventManager")->willReturn($eventManager);

        # Get mock scene
        $scene = $this->getMockBuilder(Scene::class)
            ->disableOriginalConstructor()
            ->getMock();

        $viewpoint = $this->getMockBuilder(Viewpoint::class)
            ->disableOriginalConstructor()
            ->getMock();
        $viewpoint->method("getScene")->willReturn($scene);

        return [$game, $viewpoint, $character, $eventManager];
    }

    public function testIfSceneRendererCanBeConstructed()
    {
        [$game, $viewpoint, $character, $eventManager] = $this->getMockeries();
        $eventManager->method("publish")->willReturnArgument(1);

        $renderer = new TwigSceneRenderer($game);

        $this->assertInstanceOf(TwigSceneRenderer::class, $renderer);
    }

    public function testIfTwigSceneRendererReturnsANonTemplateStringUnmodified()
    {
        [$game, $viewpoint, $character, $eventManager] = $this->getMockeries();
        $eventManager->method("publish")->willReturnArgument(1);

        # Get renderer
        $renderer = new TwigSceneRenderer($game);

        # Prepare the template string.
        $template = "You enter a new location.\n\nA new location.";

        # Create the result
        $renderResult = $renderer->render($template, $viewpoint);

        # Assert result
        $this->assertSame($template, $renderResult);
    }

    public function testIfTwigSceneRendererParsesStringsWithCharacters()
    {
        [$game, $viewpoint, $character, $eventManager] = $this->getMockeries();
        $eventManager->method("publish")->willReturnArgument(1);

        # Get renderer
        $renderer = new TwigSceneRenderer($game);

        # Prepare the template string.
        $template = "Hi {{ Character.getDisplayName }}! How are you today? Your level is {{ Character.level}}, and you have "
            ."{{ Character.health }} out of {{ Character.maxHealth }} health points."
            ."{% if Character.isAlive %} You are alive.{% endif %}";

        $result = "Hi Frodo! How are you today? Your level is 5, and you have "
            ."10 out of 100 health points. "
            ."You are alive.";

        # Create the result
        $renderResult = $renderer->render($template, $viewpoint);

        # Assert result
        $this->assertSame($result, $renderResult);
    }

    public function testIfTwigSceneRendererParsesStringsWithCharacterProperty()
    {
        [$game, $viewpoint, $character, $eventManager] = $this->getMockeries();
        $eventManager->method("publish")->willReturnArgument(1);

        # Get renderer
        $renderer = new TwigSceneRenderer($game);

        # Prepare the template string.
        $template = "Character has {{ Character.getProperty('a/goldiNBank', -10) }} gold in bank.";

        $result = "Character has -10 gold in bank.";

        # Create the result
        $renderResult = $renderer->render($template, $viewpoint);

        # Assert result
        $this->assertSame($result, $renderResult);
    }

    public function testIfTwigSceneRendererAccepsAbsFIlter()
    {
        [$game, $viewpoint, $character, $eventManager] = $this->getMockeries();
        $eventManager->method("publish")->willReturnArgument(1);

        # Get renderer
        $renderer = new TwigSceneRenderer($game);

        # Prepare the template string.
        $template = "Character has a debt of {{ Character.getProperty('a/goldiNBank', -10)|abs }} gold.";

        $result = "Character has a debt of 10 gold.";

        # Create the result
        $renderResult = $renderer->render($template, $viewpoint);

        # Assert result
        $this->assertSame($result, $renderResult);
    }

    public function testIfViewpointDataCanBeAccessed()
    {
        [$game, $viewpoint, $character, $eventManager] = $this->getMockeries();
        $eventManager->method("publish")->willReturnArgument(1);
        $viewpoint->method("getData")->willReturn(["test" => 7, "other" => "Hi"]);

        # Get renderer
        $renderer = new TwigSceneRenderer($game);

        # Prepare the template string.
        $template = "Hi {{ Viewpoint.data.test }}! {{ Viewpoint.data.other }}";
        $result = "Hi 7! Hi";

        # Create the result
        $renderResult = $renderer->render($template, $viewpoint);

        # Assert result
        $this->assertSame($result, $renderResult);
    }

    public function testIfRawTemplateGetsReturnedIfTemplateContainsIllegalTokens()
    {
        [$game, $viewpoint, $character, $eventManager] = $this->getMockeries();
        $eventManager->method("publish")->willReturnArgument(1);

        # Get renderer
        $renderer = new TwigSceneRenderer($game);

        # Prepare the template string.
        $template = "Viewpoint: {{ Character.viewpoint }}";

        # Try to parse the result
        $renderResult = $renderer->render($template, $viewpoint, true);

        # If there was an error, it should have gotten ignored, giving back the raw template.
        $this->assertSame($template, $renderResult);
    }

    public function testIfExceptionGetsRaisedIfTemplateContainsIllegalTokens()
    {
        [$game, $viewpoint, $character, $eventManager] = $this->getMockeries();
        $eventManager->method("publish")->willReturnArgument(1);

        # Get renderer
        $renderer = new TwigSceneRenderer($game);

        # Prepare the template string.
        $template = "Viewpoint: {{ Character.viewpoint }}";

        # Prepare the exception expectation
        $this->expectException(InsecureTwigTemplateError::class);

        # Try to parse the result
        $renderResult = $renderer->render($template, $viewpoint, false);
    }

    public function testIfPublishedEventCanModifySecurityPolicy()
    {
        [$game, $viewpoint, $character, $eventManager] = $this->getMockeries();

        # Set up a more complex "publish" method to emulate a real event.
        $eventManager->method("publish")->willReturnCallback(function($event, EventContextData $context) {
            if ($event !== "h/lotgd/core/scene-renderer/securityPolicy") {
                return $context;
            }

            $tags = [];
            $filters = ["escape"];
            $functions = [];
            $methods = [];
            $properties = [];

            return EventContextData::create([
                "tags" => $tags,
                "filters" => $filters,
                "functions" => $functions,
                "methods" => $methods,
                "properties" => $properties,
            ]);
        });

        # Get renderer
        $renderer = new TwigSceneRenderer($game);

        # Assert that if does not work anymore
        $this->expectException(InsecureTwigTemplateError::class);
        $renderer->render("{% if 5*1 %}Hallo{%endif%}", $viewpoint, false);

        $this->expectException(InsecureTwigTemplateError::class);
        $renderer->render("{{ Character.name }}", $viewpoint, false);
    }

    public function testIfPublishedEventCanModifyValueScope()
    {
        [$game, $viewpoint, $character, $eventManager] = $this->getMockeries();

        # Set up a more complex "publish" method to emulate a real event.
        $eventManager->method("publish")->willReturnCallback(function($event, EventContextData $context) {
            if ($event !== "h/lotgd/core/scene-renderer/templateValues") {
                return $context;
            }

            $templateValues = $context->get("templateValues");
            $templateValues["test"] = "A test";

            $context = $context->set("templateValues", $templateValues);

            return $context;
        });

        # Get renderer
        $renderer = new TwigSceneRenderer($game);

        # Assert result
        $result = $renderer->render("{{ test }}", $viewpoint, false);
        $this->assertSame("A test", $result);
    }
}