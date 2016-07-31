<?php

namespace LotGD\Core;

use Symfony\Component\Console\Application;

use LotGD\Core\ComposerManager;

/**
 * Handle the boot configurations for the installed core, crate and modules.
 */
class BootConfigurationManager
{
    private $cwd;
    /** @var array<BootConfiguration> */
    private $configurations = null;

    /**
     * Construct a manager.
     * @param ComposerManager $composerManager
     * @param string $cwd
     */
    public function __construct(ComposerManager $composerManager, string $cwd)
    {
        $this->cwd = $cwd;

        $packages = $composerManager->getPackages();
        $this->configurations = [];

        foreach($packages as $package)
        {
            if ($package->getType() === "lotgd-crate" || $package->getType() === "lotgd-module") {
                $this->configurations[] = new BootConfiguration($composerManager, $package, $cwd);
            }
        }
    }

    /**
     * Returns a list of all entity directories from lotgd packages
     * @return array
     */
    public function getEntityDirectories(): array
    {
        $entityDirectories = [];

        foreach ($this->configurations as $config) {
            if ($config->hasEntityDirectory()) {
                $entityDirectories[] = $config->getEntityDirectory();
            }
        }

        return $entityDirectories;
    }

    /**
     * Adds commands from packages to daenerys
     * @param \LotGD\Core\Game $game
     * @param Application $application
     */
    public function addDaenerysCommands(Game $game, Application $application)
    {
        foreach ($this->configurations as $config) {
            $commands = $config->getDaenerysCommands();
            foreach ($commands as $command) {
                $application->add(new $command($game));
            }
        }
    }
}
