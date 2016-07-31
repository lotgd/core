<?php
declare (strict_types=1);

namespace LotGD\Core;

use Doctrine\ORM\EntityManagerInterface;
use Monolog\Logger;

use LotGD\Core\Models\Character;
use LotGD\Core\Exceptions\ {
    SceneNotFoundException,
    ActionNotException
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
     * Take the specified navigation action for the currently configured
     * user. This action must be present in the current user's viewpoint.
     * @param string $actionId The identifier of the action to take.
     * @param array $paramters
     */
    public function takeAction(string $actionId, array $parameters)
    {
        $this->getLogger()->debug("Taking action id={$actionId}");

        $v = $this->getViewpoint();

        // Verify $actionId is present in the current viewpoint.
        $action = $v->findActionById($actionId);
        if ($action === null) {
            throw new ActionNotException("Invalid action id={$actionId} for current viewpoint.");
        }

        while ($action != null) {
            $nextSceneId = $action->getDestinationSceneId();
            $nextScene = $this->getEntityManager()->getRepository(Scene::class)->find([
                'id' => $nextSceneId
            ]);
            if ($nextScene == null) {
                throw new SceneNotFoundException("Cannot find scene id={$nextSceneId} specified by action id={$actionId}.");
            }

            $nextViewpoint = $this->setupViewpoint($nextScene);

            // Let and installed listeners (ie modules) make modifications to the
            // $nextViewpoint, including the ability to redirect the user to
            // a different scene, by setting $context['redirect'] to a new action.
            $context = [
                'viewpoint' => $nextViewpoint,
                'redirect' => null
            ];
            $this->getEventManager()->publish('h/lotgd/core/navigate-to/' . $nextScene->getTemplate(), $context);
            $action = $context['redirect'];

            if ($action != null) {
                $s = $action->getDestinationSceneId();
                $this->getLogger()->debug("Redirecting to destinationSceneId={$s}");
            }
        }

        $nextViewpoint->save();
    }

    /**
     * Returns a viewpoint made from a Scene $s and the current user, complete
     * with actions built from the scene's children and those modified/added by
     * the hook 'h/lotgd/core/actions-for/[scene-template]'.
     * @param Scene $s
     */
    private function setupViewpoint(Scene $s): CharacterViewpoint
    {
        $v = new CharacterViewpoint([
            'owner' => $this->getCharacter()
        ]);
        $v->changeFromScene($s);
        $ag = new ActionGroup('lotgd/core/default', '', 'A');
        $as = array_map(function ($c) { return new Action($c->getId()); }, $s->getChildren());

        $context = [
            'viewpoint' => $v,
            'actions' => $as
        ];
        $this->getEventManager()->publish('h/lotgd/core/actions-for/' . $s->getTemplate(), $context);
        $as = $context['actions'];

        $ag->setActions($as);

        return $v;
    }
}
