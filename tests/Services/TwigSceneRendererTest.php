<?php
declare(strict_types=1);

namespace LotGD\Core\Tests\Services;

use LotGD\Core\Exceptions\InsecureTwigTemplateError;
use LotGD\Core\Game;
use LotGD\Core\Models\Character;
use LotGD\Core\Models\Scene;
use LotGD\Core\PHPUnit\LotGDTestCase;
use LotGD\Core\Services\TwigSceneRenderer;

class TwigSceneRendererTest extends LotGDTestCase
{
    protected function getGameMock(): Game
    {
        $game = $this->getMockBuilder(Game::class)
            ->disableOriginalConstructor()
            ->getMock();

        return $game;
    }

    public function testIfSceneRendererCanBeConstructed()
    {
        $renderer = new TwigSceneRenderer($this->getGameMock());

        $this->assertInstanceOf(TwigSceneRenderer::class, $renderer);
    }

    public function testIfTwigSceneRendererReturnsANonTemplateStringUnmodified()
    {
        # Get renderer
        $renderer = new TwigSceneRenderer($this->getGameMock());

        # Get mock scene
        $scene = $this->getMockBuilder(Scene::class)
            ->disableOriginalConstructor()
            ->getMock();

        # Prepare the template string.
        $template = "You enter a new location.\n\nA new location.";

        # Create the result
        $renderResult = $renderer->render($template, $scene);

        # Assert result
        $this->assertSame($template, $renderResult);
    }

    public function testIfTwigSceneRendererParsesStringsWithCharacters()
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

        # Get mock game
        $game = $this->getMockBuilder(Game::class)
            ->disableOriginalConstructor()
            ->getMock();
        $game->method("getCharacter")->willReturn($character);

        # Get mock scene
        $scene = $this->getMockBuilder(Scene::class)
            ->disableOriginalConstructor()
            ->getMock();

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
        $renderResult = $renderer->render($template, $scene);

        # Assert result
        $this->assertSame($result, $renderResult);
    }

    public function testIfRawTemplateGetsReturnedIfTemplateContainsIllegalTokens()
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

        # Get mock game
        $game = $this->getMockBuilder(Game::class)
            ->disableOriginalConstructor()
            ->getMock();
        $game->method("getCharacter")->willReturn($character);

        # Get mock scene
        $scene = $this->getMockBuilder(Scene::class)
            ->disableOriginalConstructor()
            ->getMock();

        # Get renderer
        $renderer = new TwigSceneRenderer($game);

        # Prepare the template string.
        $template = "Viewpoint: {{ Character.viewpoint }}";

        # Try to parse the result
        $renderResult = $renderer->render($template, $scene, true);

        # If there was an error, it should have gotten ignored, giving back the raw template.
        $this->assertSame($template, $renderResult);
    }

    public function testIfExceptionGetsRaisedIfTemplateContainsIllegalTokens()
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

        # Get mock game
        $game = $this->getMockBuilder(Game::class)
            ->disableOriginalConstructor()
            ->getMock();
        $game->method("getCharacter")->willReturn($character);

        # Get mock scene
        $scene = $this->getMockBuilder(Scene::class)
            ->disableOriginalConstructor()
            ->getMock();

        # Get renderer
        $renderer = new TwigSceneRenderer($game);

        # Prepare the template string.
        $template = "Viewpoint: {{ Character.viewpoint }}";

        # Prepare the exception expectation
        $this->expectException(InsecureTwigTemplateError::class);

        # Try to parse the result
        $renderResult = $renderer->render($template, $scene, false);
    }
}