<?php
declare(strict_types=1);

namespace LotGD\Core;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use LotGD\Core\Events\NavigateToSceneData;
use LotGD\Core\Events\NewViewpointData;
use LotGD\Core\Exceptions\ActionNotFoundException;
use LotGD\Core\Exceptions\CharacterNotFoundException;
use LotGD\Core\Exceptions\InvalidConfigurationException;
use LotGD\Core\Exceptions\SceneNotFoundException;
use LotGD\Core\Models\Character;
use LotGD\Core\Models\Scene;
use LotGD\Core\Models\SceneConnectable;
use LotGD\Core\Models\SceneConnection;
use LotGD\Core\Models\Viewpoint;
use LotGD\Core\SceneTemplates\BasicSceneTemplate;
use LotGD\Core\SceneTemplates\SceneTemplateInterface;
use Monolog\Logger;

/**
 * The main game class.
 */
class Game
{
    private EventManager $eventManager;
    private ComposerManager $composerManager;
    private ModuleManager $moduleManager;
    private MessageManager $messageManager;
    private ?Character $character = null;
    private DiceBag $diceBag;
    private ?TimeKeeper $timeKeeper = null;

    /**
     * Construct a game. You probably want to use Bootstrap to do this.
     * @param Configuration $configuration
     * @param Logger $logger
     * @param EntityManagerInterface $entityManager
     * @param string $cwd
     */
    public function __construct(
        private Configuration $configuration,
        private Logger $logger,
        private EntityManagerInterface $entityManager,
        private string $cwd
    ) {
    }

    /**
     * Return the current version of the core, conforming to Semantic Versioning.
     * @return string The current version, in x.y.z format.
     */
    public static function getVersion(): string
    {
        return '0.5.0';
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
        return $this->moduleManager;
    }

    /**
     * Sets the game's module manager.
     * @param ModuleManager $moduleManager
     */
    public function setModuleManager(ModuleManager $moduleManager): void
    {
        $this->moduleManager = $moduleManager;
    }

    /**
     * Returns the game's composer manager.
     * @return ComposerManager The game's composer manager.
     */
    public function getComposerManager(): ComposerManager
    {
        return $this->composerManager;
    }

    /**
     * Sets the game's composer manager.
     * @param ComposerManager $composerManager
     */
    public function setComposerManager(ComposerManager $composerManager): void
    {
        $this->composerManager = $composerManager;
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
        return $this->eventManager;
    }

    /**
     * Sets the game's event manager.
     * @param EventManager $eventManager
     */
    public function setEventManager(EventManager $eventManager): void
    {
        $this->eventManager = $eventManager;
    }

    /**
     * Returns the game's dice bag.
     * @return DiceBag
     */
    public function getDiceBag(): DiceBag
    {
        return $this->diceBag;
    }

    /**
     * Sets the game's dice bag.
     * @param DiceBag $diceBag
     */
    public function setDiceBag(DiceBag $diceBag): void
    {
        $this->diceBag = $diceBag;
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
            $this->timeKeeper = new TimeKeeper($gameEpoch, new DateTime(), $gameOffsetSeconds, $gameDaysPerDay);
        }
        return $this->timeKeeper;
    }

    /**
     * Returns the Message manager.
     * @return MessageManager
     */
    public function getMessageManager(): MessageManager
    {
        return $this->messageManager;
    }

    /**
     * Sets the Message Manager.
     * @param MessageManager $messageManager
     */
    public function setMessageManager(MessageManager $messageManager): void
    {
        $this->messageManager = $messageManager;
    }

    /**
     * Returns the currently configured user character.
     * @throws CharacterNotFoundException
     * @return Character
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
     * @throws InvalidConfigurationException
     * @return Viewpoint
     */
    public function getViewpoint(): Viewpoint
    {
        $v = $this->getCharacter()->getViewpoint();

        if ($v === null) {
            // No viewpoint set up for this user. Run the hook to find the default
            // scene.
            $contextData = NewViewpointData::create([
                'character' => $this->getCharacter(),
                'scene' => null,
            ]);

            $contextData = $this->getEventManager()->publish('h/lotgd/core/default-scene', $contextData);

            $s = $contextData->get("scene");
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
            $this->getLogger()->debug("Navigating to sceneId={$id} from referrer sceneId={$referrerId}");

            // Copy over the basic structure from the scene database.
            $viewpoint->changeFromScene($scene);

            // Generate the default set of actions: the default group with
            // all children.
            $this->getLogger()->debug("Building default action group...");
            $actionGroups = [
                ActionGroup::DefaultGroup => new ActionGroup(ActionGroup::DefaultGroup, '', 0),
            ];

            // Iterates through all connections and adds an action to the connected scene to the action group. If the connection
            // belongs to a new connection Group, it creates a new ActionGroup.
            $scene->getConnections()->map(function (SceneConnection $connection) use ($scene, &$actionGroups) {
                if ($connection->getOutgoingScene() === $scene) {
                    // current scene is outgoing, use incoming.
                    $connectedScene = $connection->getIncomingScene();
                    $connectionGroupName = $connection->getOutgoingConnectionGroupName();
                } else {
                    // current scene is not outgoing, thus incoming, use outgoing.
                    $connectedScene = $connection->getOutgoingScene();
                    $connectionGroupName = $connection->getIncomingConnectionGroupName();

                    // Check if the connection is unidirectional - if yes, the current scene (incoming in this branch) cannot
                    // connect to the outgoing scene.
                    if ($connection->isDirectionality(SceneConnectable::Unidirectional)) {
                        return;
                    }
                }

                $this->getLogger()->debug("  Adding navigation action for child sceneId={$connectedScene->getId()}");
                $action = new Action($connectedScene->getId(), $connectedScene->getTitle());

                if ($connectionGroupName === null) {
                    $actionGroups[ActionGroup::DefaultGroup]->addAction($action);
                } else {
                    if (isset($actionGroups[$connectionGroupName])) {
                        $actionGroups[$connectionGroupName]->addAction($action);
                    } else {
                        $connectionGroup = $scene->getConnectionGroup($connectionGroupName);
                        $actionGroup = new ActionGroup($connectionGroupName, $connectionGroup->getTitle(), 0);
                        $actionGroup->addAction($action);

                        $actionGroups[$connectionGroupName] = $actionGroup;
                    }
                }
            });

            // Logging
            $counts = \implode(", ", \array_map(function ($k, $v) {
                return $k .\count($v);
            }, \array_keys($actionGroups), \array_values($actionGroups)));
            $this->getLogger()->debug("Total actions: {$counts}");

            $actionGroups[ActionGroup::HiddenGroup] = new ActionGroup(ActionGroup::HiddenGroup, '', 100);

            $viewpoint->setActionGroups(\array_values($actionGroups));

            $sceneTemplate = $scene->getTemplate();
            $templateClass = $sceneTemplate ? $sceneTemplate->getClass() : BasicSceneTemplate::class;

            if (!\is_a($templateClass, SceneTemplateInterface::class, true)) {
                throw new \Exception("Scene template must implement ".SceneTemplateInterface::class);
            }

            // Let and installed listeners (ie modules) make modifications to the
            // new viewpoint, including the ability to redirect the user to
            // a different scene, by setting $context['redirect'] to a new scene.
            $contextData = NavigateToSceneData::create([
                'referrer' => $referrer,
                'viewpoint' => $viewpoint,
                'scene' => $scene,
                'parameters' => $parameters,
                'redirect' => null,
            ]);

            $hook = "h/lotgd/core/navigate-to/".$templateClass::getNavigationEvent();
            $contextData = $this->getEventManager()->publish($hook, $contextData);

            $scene = $contextData->get('redirect');
            if ($scene !== null) {
                $id = $scene->getId();
                $this->getLogger()->debug("Redirecting to sceneId={$id}");
            }
        } while ($scene !== null);
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

        $actionParameters = $action->getParameters();
        $sceneId = $action->getDestinationSceneId();

        /** @var Scene $scene */
        $scene = $this->getEntityManager()->getRepository(Scene::class)->find([
            'id' => $sceneId,
        ]);
        if ($scene === null) {
            throw new SceneNotFoundException("Cannot find sceneId={$sceneId} specified by actionId={$actionId}.");
        }

        // action parameters overwrite other parameters since the former cannot be changed by the user
        $parameters = \array_merge($parameters, $actionParameters);

        $this->navigateToScene($scene, $parameters);

        $v->save($this->getEntityManager());
    }
}
