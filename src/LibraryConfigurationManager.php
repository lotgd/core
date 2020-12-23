<?php
declare(strict_types=1);

namespace LotGD\Core;

/**
 * Handle the library configurations for the installed core, crate and modules.
 */
class LibraryConfigurationManager
{
    /** @var LibraryConfiguration[] */
    private array $configurations = [];

    /**
     * Construct a manager.
     * @param ComposerManager $composerManager
     * @param string $cwd
     */
    public function __construct(ComposerManager $composerManager, string $cwd)
    {
        $packages = $composerManager->getPackages();

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
     * @param string $library
     * @return LibraryConfiguration|null
     */
    public function getConfigurationForLibrary(string $library): ?LibraryConfiguration
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
    public function getConfigurations(): array
    {
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
