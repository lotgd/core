<?php
declare(strict_types=1);

namespace LotGD\Core;

use Doctrine\ORM\EntityManagerInterface;
use LotGD\Core\Exceptions\BuilderException;

use LotGD\Core\Services\TwigSceneRenderer;
use Monolog\Logger;

/**
 * The GameBuilder class is used to build a Game object with all dependencies that are needed.
 *
 * You must provide $cwd, $configuration, $entityManager and a logger instance using the with* methods.
 * You can provide additional class *names* for additional dependency injections using the use* methods.
 */
class GameBuilder
{
    private string $cwd;
    private Configuration $configuration;
    private EntityManagerInterface $entityManager;
    private Logger $logger;

    private string $moduleManagerClass;
    private string $composerManagerClass;
    private string$eventManagerClass;
    private string $diceBagClass;
    private string $messageManagerClass;
    private string $sceneRendererClass;

    /**
     * Creates the game instance with the prepared parameters.
     * @throws BuilderException if at least one of cwd, configuration, entityManager or logger as not been set.
     * @return Game
     */
    public function create(): Game
    {
        if (isset($this->cwd, $this->configuration, $this->entityManager, $this->logger) === false) {
            throw new BuilderException(
                "For creating a game, you must set at least: cwd, configuration, entityManager and logger."
            );
        }

        // construct the game
        $game = new Game(
            $this->configuration,
            $this->logger,
            $this->entityManager,
            $this->cwd
        );

        // add additional managers to it
        $moduleManager = $this->moduleManagerClass ?? ModuleManager::class;
        $game->setModuleManager(new $moduleManager($game));

        $composerManager = $this->composerManagerClass ?? ComposerManager::class;
        $game->setComposerManager(new $composerManager($this->cwd));

        $eventManager = $this->eventManagerClass ?? EventManager::class;
        $game->setEventManager(new $eventManager($game));

        $diceBag = $this->diceBagClass ?? DiceBag::class;
        $game->setDiceBag(new $diceBag());

        $messageManager = $this->messageManagerClass ?? MessageManager::class;
        $game->setMessageManager(new $messageManager());

        $sceneRenderer = $this->sceneRendererClass ?? TwigSceneRenderer::class;
        $game->setSceneRenderer(new $sceneRenderer($game));

        return $game;
    }

    /**
     * Adds current working directory argument.
     * @param string $cwd
     * @return self
     */
    public function withCwd(string $cwd): self
    {
        $this->cwd = $cwd;
        return $this;
    }

    /**
     * Configuration.
     * @param Configuration $conf
     * @return self
     */
    public function withConfiguration(Configuration $conf): self
    {
        $this->configuration = $conf;
        return $this;
    }

    /**
     * Sets the logger for the game instance.
     * @param EntityManagerInterface $em
     * @return self
     */
    public function withEntityManager(EntityManagerInterface $em): self
    {
        $this->entityManager = $em;
        return $this;
    }

    /**
     * Sets the logger for the game instance.
     * @param Logger $logger
     * @return self
     */
    public function withLogger(Logger $logger): self
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * Sets the fqcn for the message manager to be used.
     * @param string $messageManagerFqcn
     * @return self
     */
    public function withMessageManager(string $messageManagerFqcn): self
    {
        $this->messageManagerClass = $messageManagerFqcn;
        return $this;
    }

    /**
     * Sets the fqcn for the module manager to be used.
     * @param string $moduleManagerFqcn
     * @return self
     */
    public function useModuleManager(string $moduleManagerFqcn): self
    {
        $this->moduleManagerClass = $moduleManagerFqcn;
        return $this;
    }

    /**
     * Sets the fqcn for the composer manager to be used.
     * @param string $composerManagerFqcn
     * @return self
     */
    public function useComposerManager(string $composerManagerFqcn): self
    {
        $this->composerManagerClass = $composerManagerFqcn;
        return $this;
    }

    /**
     * Sets the fqcn for the event manager to be used.
     * @param string $eventManagerFqcn
     * @return GameBuilder
     */
    public function useEventManager(string $eventManagerFqcn): self
    {
        $this->eventManagerClass = $eventManagerFqcn;
        return $this;
    }

    /**
     * Sets the fqcn for the dice bag to be used.
     * @param string $diceBagFqcn
     * @return GameBuilder
     */
    public function useDiceBag(string $diceBagFqcn): self
    {
        $this->diceBagClass = $diceBagFqcn;
        return $this;
    }

    /**
     * Sets the class name for the scene renderer to be used.
     * @param string $sceneRendererFqcn
     * @return $this
     */
    public function useSceneRenderer(string $sceneRendererFqcn): self
    {
        $this->sceneRendererClass = $sceneRendererFqcn;
        return $this;
    }
}
