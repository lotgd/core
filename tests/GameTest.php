<?php
declare(strict_types=1);

namespace LotGD\Core\Tests;

use Doctrine\ORM\EntityManager;

use Monolog\Logger;
use Monolog\Handler\NullHandler;

use LotGD\Core\Action;
use LotGD\Core\ActionGroup;
use LotGD\Core\Bootstrap;
use LotGD\Core\Configuration;
use LotGD\Core\ComposerManager;
use LotGD\Core\DiceBag;
use LotGD\Core\EventHandler;
use LotGD\Core\EventManager;
use LotGD\Core\Game;
use LotGD\Core\TimeKeeper;
use LotGD\Core\ModuleManager;
use LotGD\Core\Models\Character;
use LotGD\Core\Models\CharacterViewpoint;
use LotGD\Core\Models\Scene;
use LotGD\Core\Exceptions\ {
    ActionNotFoundException,
    CharacterNotFoundException,
    InvalidConfigurationException
};
use LotGD\Core\Tests\CoreModelTestCase;

class DefaultSceneProvider implements EventHandler
{
    public static $actions;
    public static $attachments = ['actions'];
    public static $data = ['data'];

    public static function handleEvent(Game $g, string $event, array &$context)
    {
        switch ($event) {
            case 'h/lotgd/core/default-scene':
                if (!isset($context['character'])) {
                    throw new \Exception("Key 'character' was expected on event h/lotgd/core/default-scene.");
                }
                $context['scene'] = $g->getEntityManager()->getRepository(Scene::class)->find(1);
                break;
            case 'h/lotgd/core/navigate-to/lotgd/tests/village':
                $v = $context['viewpoint'];

                self::$actions = [new ActionGroup('default', 'Title', 0)];
                self::$actions[0]->setActions([
                    new Action(2), // This is a real sceneId in game.yml
                    new Action(101),
                ]);
                $v->setActions(self::$actions);

                $v->setAttachments(self::$attachments);
                $v->setData(self::$data);
                break;
        }
    }
}

class GameTest extends CoreModelTestCase
{
    /** @var string default data set */
    protected $dataset = "game";

    private $g;

    public function setUp()
    {
        parent::setUp();

        $logger  = new Logger('test');
        $logger->pushHandler(new NullHandler());

        $this->g = new Game(new Configuration(getenv('LOTGD_TESTS_CONFIG_PATH')), $logger, $this->getEntityManager(), implode(DIRECTORY_SEPARATOR, [__DIR__, '..']));
    }

    public function testBasicInjection()
    {
        $r = $this->g->getTimeKeeper();
        $this->assertInstanceOf(TimeKeeper::class, $r);

        $r = $this->g->getEventManager();
        $this->assertInstanceOf(EventManager::class, $r);

        $r = $this->g->getEntityManager();
        $this->assertInstanceOf(EntityManager::class, $r);

        $r = $this->g->getComposerManager();
        $this->assertInstanceOf(ComposerManager::class, $r);

        $r = $this->g->getModuleManager();
        $this->assertInstanceOf(ModuleManager::class, $r);

        $r = $this->g->getLogger();
        $this->assertInstanceOf(Logger::class, $r);

        $r = $this->g->getConfiguration();
        $this->assertInstanceOf(Configuration::class, $r);

        $r = $this->g->getDiceBag();
        $this->assertInstanceOf(DiceBag::class, $r);
    }

    public function testGetCharacterException()
    {
        $this->expectException(CharacterNotFoundException::class);
        $this->g->getCharacter();
    }

    public function testSetGetCharacter()
    {
        $c = $this->getEntityManager()->getRepository(Character::class)->find(1);

        $this->g->setCharacter($c);
        $this->assertEquals($c, $this->g->getCharacter());
    }

    public function testGetViewpointException()
    {
        $c = $this->getEntityManager()->getRepository(Character::class)->find(1);
        $this->g->setCharacter($c);

        // There shouldnt be any listeners to provide a default scene.
        $this->expectException(InvalidConfigurationException::class);
        $this->g->getViewpoint();
    }

    public function testGetViewpointStored()
    {
        $c = $this->getEntityManager()->getRepository(Character::class)->find(2);
        $this->g->setCharacter($c);

        $this->assertNotNull($this->g->getViewpoint());
    }

    public function testGetViewpointDefault()
    {
        $c = $this->getEntityManager()->getRepository(Character::class)->find(1);
        $this->g->setCharacter($c);

        $this->g->getEventManager()->subscribe('/h\/lotgd\/core\/default-scene/', DefaultSceneProvider::class, 'lotgd/core/tests');
        $this->g->getEventManager()->subscribe('/h\/lotgd\/core\/navigate-to\/.*/', DefaultSceneProvider::class, 'lotgd/core/tests');

        $v = $this->g->getViewpoint();
        // Run it twice to make sure no additional DB operations happen.
        $v = $this->g->getViewpoint();
        $this->assertEquals('lotgd/tests/village', $v->getTemplate());

        // Validate the changes made by the hook.
        $this->assertSame(DefaultSceneProvider::$actions, $v->getActions());
        $this->assertSame(DefaultSceneProvider::$attachments, $v->getAttachments());
        $this->assertSame(DefaultSceneProvider::$data, $v->getData());

        $this->g->getEventManager()->unsubscribe('/h\/lotgd\/core\/navigate-to\/.*/', DefaultSceneProvider::class, 'lotgd/core/tests');
    }

    public function testTakeActionNonExistant()
    {
        $c = $this->getEntityManager()->getRepository(Character::class)->find(1);
        $this->g->setCharacter($c);

        // For now, I cant seem to serialize a proper ActionGroup to store in
        // the yaml for this test suite, so build one naturally :)
        $v = $this->g->getViewpoint();

        $this->expectException(ActionNotFoundException::class);
        $this->g->takeAction('non-existent');
    }

    public function testTakeActionNavigate()
    {
        $c = $this->getEntityManager()->getRepository(Character::class)->find(3);
        $this->g->setCharacter($c);

        // For now, I cant seem to serialize a proper ActionGroup to store in
        // the yaml for this test suite, so build one naturally :)
        $v = $this->g->getViewpoint();
        $a = $v->getActions()[0]->getActions()[0];
        $this->assertNotNull($a);

        $s = $this->getEntityManager()->find(Scene::class, $a->getDestinationSceneId());
        $this->assertNotNull($s);

        $this->g->takeAction($a->getId());

        $v = $this->g->getViewpoint();
        $this->assertSame($s->getTemplate(), $v->getTemplate());
    }
}
