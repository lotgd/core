<?php
declare (strict_types=1);

namespace LotGD\Core;

use Doctrine\ORM\EntityManagerInterface;
use Monolog\Logger;

use LotGD\Core\Models\ {
    Character,
    Viewpoint,
    Scene,
    SceneConnection
};
use LotGD\Core\Exceptions\ {
    ActionNotFoundException,
    CharacterNotFoundException,
    InvalidConfigurationException,
    SceneNotFoundException
};

/**
 * The main game class.
 */
class Game
{
    private $entityManager;
    private $eventManager;
    private $composerManager;
    private $moduleManager;
    private $logger;
    private $configuration;
    private $character;
    private $diceBag;
    private $cwd;
    private $timeKeeper;

    /**
     * Construct a game. You probably want to use Bootstrap to do this.
     * @param Configuration $configuration
     * @param Logger $logger
     * @param EntityManagerInterface $entityManager
     * @param string $cwd
     */
    public function __construct(
        Configuration $configuration,
        Logger $logger,
        EntityManagerInterface $entityManager,
        string $cwd
    ) {
        $this->configuration = $configuration;
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->cwd = $cwd;
    }

    /**
     * Return the current version of the core, conforming to Semantic Versioning.
     * @return string The current version, in x.y.z format.
     */
    public static function getVersion(): string
    {
        return '0.1.0';
    }

    /**
     * Returns the game's configuration.
     * @return Configuration The game's configuration.
     */
    public function getConfiguration(): Configuration
    {
        return $this->configuration;
    }

    /**
     * Returns the current working directory, or root directory where the
     * Composer configuration is based.
     * @return string
     */
    public function getCWD(): string
    {
        return $this->cwd;
    }

    /**
     * Returns the game's module manager.
     * @return ModuleManager The game's module manager.
     */
    public function getModuleManager(): ModuleManager
    {
        if ($this->moduleManager === null) {
            $this->moduleManager = new ModuleManager($this);
        }
        return $this->moduleManager;
    }

    /**
     * Returns the game's composer manager.
     * @return ComposerManager The game's composer manager.
     */
    public function getComposerManager(): ComposerManager
    {
        if ($this->composerManager === null) {
            $this->composerManager = new ComposerManager($this->cwd);
        }
        return $this->composerManager;
    }

    /**
     * Returns the game's entity manager.
     * @return EntityManagerInterface The game's database entity manager.
     */
    public function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    /**
     * Returns the game's event manager.
     * @return EventManager The game's event manager.
     */
    public function getEventManager(): EventManager
    {
        if ($this->eventManager === null) {
            $this->eventManager = new EventManager($this);
        }
        return $this->eventManager;
    }

    /**
     * Returns the game's dice bag.
     * @return DiceBag
     */
    public function getDiceBag(): DiceBag
    {
        if ($this->diceBag === null) {
            $this->diceBag = new DiceBag();
        }
        return $this->diceBag;
    }

    /**
     * Returns the logger instance to write logs.
     * @return Logger
     */
    public function getLogger(): Logger
    {
        return $this->logger;
    }

    /**
     * Returns the time keeper.
     * @return TimeKeeper
     */
    public function getTimeKeeper(): TimeKeeper
    {
        if ($this->timeKeeper === null) {
            $gameEpoch = $this->getConfiguration()->getGameEpoch();
            $gameOffsetSeconds = $this->getConfiguration()->getGameOffsetSeconds();
            $gameDaysPerDay = $this->getConfiguration()->getGameDaysPerDay();
            $this->timeKeeper = new TimeKeeper($gameEpoch, $gameOffsetSeconds, $gameDaysPerDay);
        }
        return $this->timeKeeper;
    }

    /**
     * Returns the currently configured user character.
     * @return Character
     * @throws CharacterNotFoundException
     */
    public function getCharacter(): Character
    {
        if ($this->character === null) {
            throw new CharacterNotFoundException("No current character selected.");
        }
        return $this->character;
    }

    /**
     * Sets the currently configured user character.
     * @param Character $c
     */
    public function setCharacter(Character $c)
    {
        $this->character = $c;
    }

    /**
     * Return the viewpoint for the current user.
     * @return Viewpoint
     * @throws InvalidConfigurationException
     */
    public function getViewpoint(): Viewpoint
    {
        $v = $this->getCharacter()->getViewpoint();

        if ($v === null) {
            // No viewpoint set up for this user. Run the hook to find the default
            // scene.
            $context = [
                'character' => $this->getCharacter(),
                'scene' => null
            ];
            $this->getEventManager()->publish('h/lotgd/core/default-scene', $context);

            $s = $context['scene'];
            if ($s === null) {
                throw new InvalidConfigurationException("No subscriber to h/lotgd/core/default-scene returned a scene.");
            }

            $v = new Viewpoint();
            $v->setOwner($this->getCharacter());
            $this->getCharacter()->setViewpoint($v);

            $this->navigateToScene($s, []);
            $v->save($this->getEntityManager());
        }

        return $v;
    }

    /**
     * Starting with the current viewpoint, navigate to the specified scene,
     * calling the hook `h/lotgd/core/navigate-to/[scene template]` to
     * set up the proper viewpoint values, and following any redirects specified
     * by the hook.
     * @param Scene $scene
     * @param array $parameters
     */
    private function navigateToScene(Scene $scene, array $parameters)
    {
        $viewpoint = $this->getCharacter()->getViewpoint();
        do {
            $referrer = $viewpoint->getScene();

            $id = $scene->getId();
            $referrerId = $referrer ? $referrer->getId() : 'null';
            $this->getLogger()->addDebug("Navigating to sceneId={$id} from referrer sceneId={$referrerId}");

            // Copy over the basic structure from the scene database.
            $viewpoint->changeFromScene($scene);

            // Generate the default set of actions: the default group with
            // all children.
            $this->getLogger()->addDebug("Building default action group...");
            $actionGroups = [
                ActionGroup::DefaultGroup => new ActionGroup(ActionGroup::DefaultGroup, '', 0),
            ];

            $scene->getConnections()->map(function(SceneConnection $connection) use ($scene, $actionGroups) {
                if ($connection->getOutgoingScene() === $scene) {
                    // current scene is outgoing, use incoming.
                    $connectedScene = $connection->getIncomingScene();
                    $connectionGroupName = $connection->getOutgoingConnectionGroupName();
                } else {
                    // current scene is not outgoing, thus incoming, use outgoing.
                    $connectedScene = $connection->getOutgoingScene();
                    $connectionGroupName = $connection->getIncomingConnectionGroupName();
                }

                $this->getLogger()->addDebug("  Adding navigation action for child sceneId={$connectedScene->getId()}");
                $action = new Action($connectedScene->getId());

                if ($connectionGroupName === null) {
                    $actionGroups[ActionGroup::DefaultGroup]->addAction($action);
                } else {
                    if (isset($actionGroups[$connectionGroupName])) {
                        $actionGroups[$connectionGroupName]->addAction($action);
                    } else {
                        $connectionGroup = $scene->getConnectionGroup($connectionGroupName);
                        $actionGroup = new ActionGroup($connectionGroupName->getName(), $connectionGroupName->getTitle(), 0);
                        $actionGroup->addAction($action);

                        $actionGroups[$connectionGroupName] = $actionGroup;
                    }
                }
            });
            /*$as = array_map(function ($c) {
                $id = $c->getId();
                $this->getLogger()->addDebug("  Adding navigation action for child sceneId={$id}");
                return new Action($c->getId());
            }, $scene->getChildren()->toArray());*/
            //$defaultGroup->setActions($as);
            //$count = count($as);
            $counts = implode(", ", array_map(function($k, $v) {
                return $k .count($v);
            }, array_keys($actionGroups), array_values($actionGroups)));
            $this->getLogger()->addDebug("Total actions: {$counts}");

            $actionGroups[ActionGroup::HiddenGroup] = new ActionGroup(ActionGroup::HiddenGroup, '', 100);

            $viewpoint->setActionGroups(array_values($actionGroups));

            // Let and installed listeners (ie modules) make modifications to the
            // new viewpoint, including the ability to redirect the user to
            // a different scene, by setting $context['redirect'] to a new scene.
            $context = [
                'referrer' => $referrer,
                'viewpoint' => $viewpoint,
                'scene' => $scene,
                'parameters' => $parameters,
                'redirect' => null
            ];
            $hook = 'h/lotgd/core/navigate-to/' . $scene->getTemplate();
            $this->getEventManager()->publish($hook, $context);

            $scene = $context['redirect'];
            if ($scene !== null) {
                $id = $scene->getId();
                $this->getLogger()->debug("Redirecting to sceneId={$id}");
            }
        } while($scene !== null);
    }

    /**
     * Take the specified navigation action for the currently configured
     * user. This action must be present in the current user's viewpoint.
     * @param string $actionId The identifier of the action to take.
     * @param array $parameters
     * @throws ActionNotFoundException
     * @throws SceneNotFoundException
     */
    public function takeAction(string $actionId, array $parameters = [])
    {
        $this->getLogger()->debug("Taking actionId={$actionId}");

        $v = $this->getViewpoint();

        // Verify $actionId is present in the current viewpoint.
        $action = $v->findActionById($actionId);
        if ($action === null) {
            throw new ActionNotFoundException("Invalid actionId={$actionId} for current viewpoint.");
        }

        $sceneId = $action->getDestinationSceneId();
        $scene = $this->getEntityManager()->getRepository(Scene::class)->find([
            'id' => $sceneId
        ]);
        if ($scene == null) {
            throw new SceneNotFoundException("Cannot find sceneId={$sceneId} specified by actionId={$actionId}.");
        }
        $this->navigateToScene($scene, $parameters);
        $v->save($this->getEntityManager());
    }
}
