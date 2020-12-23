<?php
declare(strict_types=1);

namespace LotGD\Core\Tests;

use Doctrine\ORM\EntityManager;

use Monolog\Logger;
use Monolog\Handler\NullHandler;

use LotGD\Core\{Action,
    ActionGroup,
    Bootstrap,
    Configuration,
    ComposerManager,
    DiceBag,
    EventHandler,
    EventManager,
    Events\NewViewpointData,
    Game,
    GameBuilder,
    Tests\SceneTemplates\ParameterTestSceneTemplate,
    Tests\SceneTemplates\VillageSceneTemplate,
    TimeKeeper,
    ModuleManager};
use LotGD\Core\Models\{
    Character, Viewpoint, Scene
};
use LotGD\Core\Exceptions\ {
    ActionNotFoundException, CharacterNotFoundException, InvalidConfigurationException
};
use LotGD\Core\Events\EventContext;

class DefaultSceneProvider implements EventHandler
{
    public static $actionGroups;
    public static $attachments = ['actions'];
    public static $data = ['data'];

    public static function handleEvent(Game $g, EventContext $context): EventContext
    {
        switch ($context->getEvent()) {
            case 'h/lotgd/core/default-scene':
                if (!$context->hasDataType(NewViewpointData::class)) {
                    throw new \Exception(sprintf(
                        "Context was expected to be %s, %s instead.",
                        NewViewpointData::class,
                        get_class($context->getData())
                    ));
                }

                $context->setDataField("scene", $g->getEntityManager()->getRepository(Scene::class)
                    ->find("30000000-0000-0000-0000-000000000001"));
                break;
            case "h/lotgd/core/navigate-to/".VillageSceneTemplate::getNavigationEvent();
                $v = $context->getDataField('viewpoint');

                self::$actionGroups = [new ActionGroup('default', 'Title', 0)];
                self::$actionGroups[0]->setActions([
                    new Action("30000000-0000-0000-0000-000000000002"), // This is a real sceneId in game.yml
                    new Action("30000000-0000-0000-0000-000000000101"),
                ]);
                $v->setActionGroups(self::$actionGroups);

                $v->setAttachments(self::$attachments);
                $v->setData(self::$data);
                break;

            case "h/lotgd/core/navigate-to/".ParameterTestSceneTemplate::getNavigationEvent():
                /* @var Viewpoint $v //*/
                $v = $context->getDataField('viewpoint');
                /* @var array //*/
                $p = $context->getDataField('parameters');

                if ($p["foo"] === "baz") {
                    $v->setDescription("Parameter is baz.");
                } else {
                    $v->setDescription("Parameter is NOT baz.");
                }
                break;
        }

        return $context;
    }
}

class GameTest extends CoreModelTestCase
{
    /** @var string default data set */
    protected $dataset = "game";

    public $g;

    public function setUp(): void
    {
        parent::setUp();

        $logger  = new Logger('test');
        $logger->pushHandler(new NullHandler());

        $this->g = (new GameBuilder())
            ->withConfiguration(new Configuration(getenv('LOTGD_TESTS_CONFIG_PATH')))
            ->withLogger($logger)
            ->withEntityManager($this->getEntityManager())
            ->withCwd(implode(DIRECTORY_SEPARATOR, [__DIR__, '..']))
            ->create();
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
        $c = $this->getEntityManager()->getRepository(Character::class)->find("10000000-0000-0000-0000-000000000001");

        $this->g->setCharacter($c);
        $this->assertEquals($c, $this->g->getCharacter());
    }

    public function testGetViewpointException()
    {
        $c = $this->getEntityManager()->getRepository(Character::class)->find("10000000-0000-0000-0000-000000000001");
        $this->g->setCharacter($c);

        // There should'nt be any listeners to provide a default scene.
        $this->expectException(InvalidConfigurationException::class);
        $this->g->getViewpoint();
    }

    public function testGetViewpointStored()
    {
        $c = $this->getEntityManager()->getRepository(Character::class)->find("10000000-0000-0000-0000-000000000002");
        $this->g->setCharacter($c);

        $this->assertNotNull($this->g->getViewpoint());
    }

    public function testGetViewpointDefault()
    {
        $c = $this->getEntityManager()->getRepository(Character::class)->find("10000000-0000-0000-0000-000000000001");
        $this->g->setCharacter($c);

        $this->g->getEventManager()->subscribe('/h\/lotgd\/core\/default-scene/', DefaultSceneProvider::class, 'lotgd/core/tests');
        $this->g->getEventManager()->subscribe('/h\/lotgd\/core\/navigate-to\/.*/', DefaultSceneProvider::class, 'lotgd/core/tests');
        $this->getEntityManager()->flush();

        $v = $this->g->getViewpoint();
        // Run it twice to make sure no additional DB operations happen.
        /** @var Viewpoint $v */
        $v = $this->g->getViewpoint();
        $this->assertSame(VillageSceneTemplate::class, $v->getTemplate()->getClass());

        // Validate the changes made by the hook.
        $this->assertSame(DefaultSceneProvider::$actionGroups, $v->getActionGroups());
        $this->assertSame(DefaultSceneProvider::$attachments, $v->getAttachments());
        $this->assertSame(DefaultSceneProvider::$data, $v->getData());

        $this->g->getEventManager()->unsubscribe('/h\/lotgd\/core\/navigate-to\/.*/', DefaultSceneProvider::class, 'lotgd/core/tests');
    }

    public function testTakeActionNonExistant()
    {
        $c = $this->getEntityManager()->getRepository(Character::class)->find("10000000-0000-0000-0000-000000000001");
        $this->g->setCharacter($c);

        // For now, I cant seem to serialize a proper ActionGroup to store in
        // the yaml for this test suite, so build one naturally :)
        $v = $this->g->getViewpoint();

        $this->expectException(ActionNotFoundException::class);
        $this->g->takeAction('non-existent');
    }

    public function testTakeActionNavigate()
    {
        $c = $this->getEntityManager()->getRepository(Character::class)->find("10000000-0000-0000-0000-000000000003");
        $this->g->setCharacter($c);

        // For now, I cant seem to serialize a proper ActionGroup to store in
        // the yaml for this test suite, so build one naturally :)
        $v = $this->g->getViewpoint();
        $a = $v->getActionGroups()[0]->getActions()[0];
        $this->assertNotNull($a);

        $s = $this->getEntityManager()->find(Scene::class, $a->getDestinationSceneId());
        $this->assertNotNull($s);

        $this->g->takeAction($a->getId());

        $v = $this->g->getViewpoint();
        $this->assertSame($s->getTemplate(), $v->getTemplate());
    }

    public function testIfActionParametersAreRelayedToEvent()
    {
        /* @var $c Character */
        $c = $this->getEntityManager()->getRepository(Character::class)->find("10000000-0000-0000-0000-000000000002");
        $this->g->setCharacter($c);

        // subscribe event
        $this->g->getEventManager()->subscribe('#'.ParameterTestSceneTemplate::getNavigationEvent().'#', DefaultSceneProvider::class, 'lotgd/core/tests');
        $this->getEntityManager()->flush();

        $action = new Action("30000000-0000-0000-0000-000000000007", null, ["foo" => "baz"]);
        $actionId = $action->getId();

        $ag = new ActionGroup("group1", "Group 1", 5);
        $ag->addAction($action);

        $v = $c->getViewpoint();
        $v->setDescription("Test");
        $v->setActionGroups([$ag]);
        $c->setViewpoint($v);

        $this->g->takeAction($actionId);

        $v = $c->getViewpoint();
        $this->assertSame("Parameter is baz.", $v->getDescription());

        // unsubscribe event
        $this->g->getEventManager()->unsubscribe('#'.ParameterTestSceneTemplate::getNavigationEvent().'#', DefaultSceneProvider::class, 'lotgd/core/tests');
    }

    public function testIfActionParametersTakePriorityToOtherParameters()
    {
        /* @var $c Character */
        $c = $this->getEntityManager()->getRepository(Character::class)->find("10000000-0000-0000-0000-000000000002");
        $this->g->setCharacter($c);

        // subscribe event
        $this->g->getEventManager()->subscribe('#'.ParameterTestSceneTemplate::getNavigationEvent().'#', DefaultSceneProvider::class, 'lotgd/core/tests');
        $this->getEntityManager()->flush();

        $action = new Action("30000000-0000-0000-0000-000000000007", null, ["foo" => "baz"]);
        $actionId = $action->getId();

        $ag = new ActionGroup("group1", "Group 1", 5);
        $ag->addAction($action);

        $v = $c->getViewpoint();
        $v->setDescription("Test");
        $v->setActionGroups([$ag]);
        $c->setViewpoint($v);

        $this->g->takeAction($actionId, ["foo" => "nobaz"]);

        $v = $c->getViewpoint();
        $this->assertSame("Parameter is baz.", $v->getDescription());

        // unsubscribe event
        $this->g->getEventManager()->unsubscribe('#'.ParameterTestSceneTemplate::getNavigationEvent().'#', DefaultSceneProvider::class, 'lotgd/core/tests');
    }

    public function testIfActionsAreAddedAsExpected()
    {
        $viewpointToArray = function(Viewpoint $v) {
            $returnTree = [];
            foreach ($v->getActionGroups() as $actionGroup) {
                $returnTree[$actionGroup->getId()] = [];

                foreach ($actionGroup->getActions() as $action) {
                    $returnTree[$actionGroup->getId()][] = $action->getDestinationSceneId();
                }
            }

            return [$v->getTitle(), $returnTree];
        };

        $sortedValues = function(array $array) {
            $values = array_values($array);
            sort($values);
            return $values;
        };

        $c = $this->getEntityManager()->getRepository(Character::class)->find("10000000-0000-0000-0000-000000000003");
        $this->g->setCharacter($c);

        $v0 = $this->g->getViewpoint();
        $this->g->takeAction($v0->getActionGroups()[0]->getActions()[2]->getId());

        $v1 = $this->g->getViewpoint();
        $this->assertSame([
            "Parent Scene",
            [
                ActionGroup::DefaultGroup => ["30000000-0000-0000-0000-000000000001"],
                "lotgd/tests/none/child1" => ["30000000-0000-0000-0000-000000000005"],
                "lotgd/tests/none/child2" => ["30000000-0000-0000-0000-000000000006"],
                ActionGroup::HiddenGroup => [],
            ]
        ], $viewpointToArray($v1));

        $this->g->takeAction($v1->getActionGroups()[1]->getActions()[0]->getId());
        $v2 = $this->g->getviewpoint();
        $this->assertSame([
            "Child Scene 1",
            [
                ActionGroup::DefaultGroup => [
                    "30000000-0000-0000-0000-000000000006",
                    "30000000-0000-0000-0000-000000000004"
                ],
                ActionGroup::HiddenGroup => [],
            ]
        ], $viewpointToArray($v2));

        $this->g->takeAction($v1->getActionGroups()[0]->getActions()[0]->getId());
        $v3 = $this->g->getviewpoint();
        $this->assertSame([
            "Child Scene 2",
            [
                ActionGroup::DefaultGroup => ["30000000-0000-0000-0000-000000000004"],
                ActionGroup::HiddenGroup => [],
            ]
        ], $viewpointToArray($v3));
    }
}
