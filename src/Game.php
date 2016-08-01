<?php
declare (strict_types=1);

namespace LotGD\Core;

use Doctrine\ORM\EntityManagerInterface;
use Monolog\Logger;

use LotGD\Core\Models\ {
    Character,
    CharacterViewpoint,
    Scene
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

    /**
     * Construct a game. You probably want to use Bootstrap to do this.
     */
    public function __construct(
        Configuration $configuration,
        Logger $logger,
        EntityManagerInterface $entityManager,
        EventManager $eventManager
    ) {
        $this->configuration = $configuration;
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->eventManager = $eventManager;
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
            $this->composerManager = new ComposerManager($this->getLogger());
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
     * @return \Monolog\Logger
     */
    public function getLogger(): \Monolog\Logger
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
     * @return CharacterViewpoint
     */
    public function getViewpoint(): CharacterViewpoint
    {
        $v = $this->getCharacter()->getCharacterViewpoint();

        if ($v === null) {
            // No viewpoint set up for this user. Run the hook to find the default
            // scene.
            $context = [
                'g' => $this,
                'character' => $this->getCharacter(),
                'scene' => null
            ];
            $this->getEventManager()->publish('h/lotgd/core/default-scene', $context);

            $s = $context['scene'];
            if ($s === null) {
                throw new InvalidConfigurationException("No subscriber to h/lotgd/core/default-scene returned a scene.");
            }
            $v = new CharacterViewpoint();
            $this->setupViewpoint($v, $s);
            $this->getCharacter()->setCharacterViewpoint($v);
            $v->save($this->getEntityManager());
        }

        return $v;
    }

    /**
     * Take the specified navigation action for the currently configured
     * user. This action must be present in the current user's viewpoint.
     * @param string $actionId The identifier of the action to take.
     * @param array $paramters
     */
    public function takeAction(string $actionId, array $parameters = [])
    {
        $this->getLogger()->debug("Taking action id={$actionId}");

        $v = $this->getViewpoint();

        // Verify $actionId is present in the current viewpoint.
        $action = $v->findActionById($actionId);
        if ($action === null) {
            throw new ActionNotFoundException("Invalid action id={$actionId} for current viewpoint.");
        }

        while ($action != null) {
            $nextSceneId = $action->getDestinationSceneId();
            $nextScene = $this->getEntityManager()->getRepository(Scene::class)->find([
                'id' => $nextSceneId
            ]);
            if ($nextScene == null) {
                throw new SceneNotFoundException("Cannot find scene id={$nextSceneId} specified by action id={$actionId}.");
            }

            $this->setupViewpoint($v, $nextScene);

            // Let and installed listeners (ie modules) make modifications to the
            // $nextViewpoint, including the ability to redirect the user to
            // a different scene, by setting $context['redirect'] to a new action.
            $context = [
                'g' => $this,
                'viewpoint' => $v,
                'redirect' => null
            ];
            $this->getEventManager()->publish('h/lotgd/core/navigate-to/' . $nextScene->getTemplate(), $context);
            $action = $context['redirect'];

            if ($action != null) {
                $s = $action->getDestinationSceneId();
                $this->getLogger()->debug("Redirecting to destinationSceneId={$s}");
            }
        }

        $this->getCharacter()->setCharacterViewpoint($v);
        $v->save($this->getEntityManager());
    }

    /**
     * Returns a viewpoint made from a Scene $s and the current user, complete
     * with actions built from the scene's children and those modified/added by
     * the hook 'h/lotgd/core/actions-for/[scene-template]'.
     */
    private function setupViewpoint(CharacterViewpoint $v, Scene $s)
    {
        $v->setOwner($this->getCharacter());
        $v->changeFromScene($s);
        $ag = new ActionGroup('lotgd/core/default', '', 'A');
        $as = array_map(function ($c) { return new Action($c->getId()); }, $s->getChildren()->toArray());
        $ag->setActions($as);

        $context = [
            'g' => $this,
            'viewpoint' => $v,
            'actions' => [$ag]
        ];
        $this->getEventManager()->publish('h/lotgd/core/actions-for/' . $s->getTemplate(), $context);
        $v->setActions($context['actions']);
    }
}
