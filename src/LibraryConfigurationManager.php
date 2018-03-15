<?php

namespace LotGD\Core;

use Composer\Package\PackageInterface;
use Symfony\Component\Console\Application;

use LotGD\Core\ComposerManager;

/**
 * Handle the library configurations for the installed core, crate and modules.
 */
class LibraryConfigurationManager
{
    /** @var array<LibraryConfiguration> */
    private $configurations = null;

    /**
     * Construct a manager.
     * @param ComposerManager $composerManager
     * @param string $cwd
     */
    public function __construct(ComposerManager $composerManager, string $cwd)
    {
        $packages = $composerManager->getPackages();
        $this->configurations = [];

        foreach ($packages as $package) {
            if ($package->getType() === "lotgd-crate" || $package->getType() === "lotgd-module") {
                $config = new LibraryConfiguration($composerManager, $package, $cwd);
                $this->configurations[] = $config;
            }
        }
    }

    /**
     * Return a library configuration for the specified library, in 'vendor/library'
     * format.
     * @return LibraryConfiguration|null
     */
    public function getConfigurationForLibrary(string $library)
    {
        $configs = $this->getConfigurations();

        foreach ($configs as $c) {
            if ($c->getName() === $library) {
                return $c;
            }
        }
        return null;
    }

    /**
     * Return an array of the library configurations.
     * @return LibraryConfiguration[]
     */
    public function getConfigurations(): array {
        return $this->configurations;
    }

    /**
     * Returns a list of all entity directories from LotGD libraries.
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
}
