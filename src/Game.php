<?php
declare (strict_types=1);

namespace LotGD\Core;

use Doctrine\ORM\EntityManagerInterface;
use Monolog\Logger;

use LotGD\Core\Models\Character;

class Game
{
    private $entityManager;
    private $eventManager;
    private $composerManager;
    private $moduleManager;
    private $logger;
    private $configuration;

    public function __construct(
        Configuration $configuration,
        EntityManagerInterface $entityManager,
        EventManager $eventManager,
        Logger $logger)
    {
        $this->configuration = $configuration;
        $this->entityManager = $entityManager;
        $this->eventManager = $eventManager;
        $this->logger = $logger;
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
        return $this->diceBag;
    }

    /**
     * Returns the active character for this game run
     * @return Character
     */
    public function getCharacter(): Character
    {
        return $this->character;
    }

    /**
     * Returns the logger instance to write logs.
     * @return \Monolog\Logger
     */
    public function getLogger(): \Monolog\Logger
    {
        return $this->logger;
    }
}
