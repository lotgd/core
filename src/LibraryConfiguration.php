<?php

namespace LotGD\Core;

use Composer\Package\PackageInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Yaml\Yaml;

use LotGD\Core\ComposerManager;

/**
 * Represents the configuration of a LotGD library (like the core, crate or module),
 * with its configuration parameters.
 * @author sauterb
 */
class LibraryConfiguration
{
    /** @var ComposerManager */
    private $composerManager;
    /** @var PackageInterface */
    private $package;
    /** @var string */
    private $rootNamespace;
    /** @var array */
    private $subscriptionPatterns;
    /** @var array */
    private $rawConfig;
    private $daenerysCommands;

    /**
     * Construct a configuration.
     * @param ComposerManager $composerManager
     * @param PackageInterface $package
     * @param string $cwd
     */
    public function __construct(ComposerManager $composerManager, PackageInterface $package, string $cwd)
    {
        $this->composerManager = $composerManager;
        $this->package = $package;

        $path = '';
        $basePackage = $composerManager->getComposer()->getPackage();
        if ($basePackage && $basePackage->getName() === $package->getName()) {
            // Whatever the base package is in this repo is at $cwd.
            $path = $cwd;
        } else if ($package->getType() === "lotgd-module") {
            // lotgd-modules are installed in the vendor directory.
            $installationManager = $composerManager->getComposer()->getInstallationManager();
            $path = $installationManager->getInstallPath($package);
        } else {
            // Not sure what it is honestly, just use $cwd.
            $path = $cwd;
        }

        $confFile = $path . DIRECTORY_SEPARATOR . 'lotgd.yml';

        $this->rootNamespace = $this->findRootNamespace($package);
        if (file_exists($confFile)) {
            $this->rawConfig = Yaml::parse(file_get_contents($confFile));
        } else {
            $name = $package->getName();
            $type = $package->getType();
            throw new \Exception("Library {$name} of type {$type} does not have a lotgd.yml in it's root ($confFile).");
        }

        $this->findEntityDirectory();
        $this->findDaenerysCommands();
        $this->findSubscriptionPatterns();
    }

    /**
     * Return the underlying Composer package.
     * @return PackageInterface
     */
    public function getComposerPackage(): PackageInterface
    {
        return $this->package;
    }

    /**
     * Return the name, in vendor/library format, of this library.
     * @return string
     */
    public function getName(): string
    {
        return $this->package->getName();
    }

    /**
     * Searches for a root namespace
     *
     * This function searches the package's configuration to find it's root namespace.
     * For this, it uses the following order:
     *  - check psr-4 autoload configuration. If used, it takes the first element
     *  - check psr-0 autoload configuration. If used, it takes the first element
     * @param PackageInterface $package
     * @return string
     * @throws \Exception if no namespace has been found
     */
    protected function findRootNamespace(PackageInterface $package): string
    {
        $autoload = $package->getAutoload();
        if (isset($autoload["psr-4"]) && count($autoload["psr-4"]) > 0) {
            return key($autoload["psr-4"]);
        }

        if (isset($autoload["psr-0"]) && count($autoload["psr-0"]) > 0) {
            return key($autoload["psr-0"]);
        }

        $name = $package->getName();
        throw new \Exception("{$name} has no valid namespace.");
    }

    /**
     * Returns the root namespace.
     * @return string
     */
    public function getRootNamespace(): string
    {
        return $this->rootNamespace;
    }

    /**
     * Returns a subkey if it exists or null.
     * @param array $arguments
     * @return mixed
     */
    public function getSubKeyIfItExists(array $arguments)
    {
        $parent = $this->rawConfig;

        foreach ($arguments as $argument) {
            if (isset($parent[$argument])) {
                $parent = $parent[$argument];
            } else {
                return null;
            }
        }

        return $parent;
    }

    /**
     * Tries to iterate an array element given by the arguments
     * @param scalar $argument1,... array keys, by increasing depth
     */
    public function iterateKey(...$arguments)
    {
        $result = $this->getSubKeyIfItExists($arguments);

        if (is_array($result)) {
            foreach ($result as $key => $val) {
                yield $key => $val;
            }
        }
    }

    /**
     * Returns a subkey of an array if it exists or null
     * @param scalar $argument1,... array keys, by increasing depth
     * @return type
     */
    protected function getConfig(...$arguments)
    {
        $result = $this->getSubKeyIfItExists($arguments);
        return $result;
    }

    /**
     * Derives the path where any entity classes might reside from the entityNamespace
     * entry in the config file.
     */
    protected function findEntityDirectory()
    {
        $this->entityDirectory = null;

        $entityNamespace = $this->getConfig("entityNamespace");

        if (is_null($entityNamespace) === false) {
            $entityDirectory = $this->composerManager->translateNamespaceToPath($entityNamespace);

            if ($entityDirectory === null) {
                throw new \Exception("Could not translate namespace {$entityNamespace} into a directory.");
            } else if (is_dir($entityDirectory) === false) {
                throw new \Exception("Path {$entityDirectory}, translated from namespace {$entityNamespace}, is not a valid directory.");
            }

            $this->entityDirectory = $entityDirectory;
        }
    }

    /**
     * Returns true if there are any models to add.
     * @return type
     */
    public function hasEntityDirectory(): bool
    {
        return $this->entityDirectory === null ? false : true;
    }

    /**
     * Returns a list of fqcn for all models added by packages.
     * @return array<string>
     */
    public function getEntityDirectory(): string
    {
        return $this->entityDirectory;
    }

    /**
     * Searches the config file for daenerys commands and, if found, adds the class name to a list
     */
    protected function findDaenerysCommands()
    {
        $list = $this->iterateKey("daenerysCommands");
        $this->daenerysCommands = [];

        foreach ($list as $command) {
            $this->daenerysCommands[] = $this->rootNamespace . $command;
        }
    }

    /**
     * Returns true if this configuration has daenerys commands
     * @return bool
     */
    public function hasDaenerysCommands(): bool
    {
        return count($this->daenerysCommands) > 0 ? true : false;
    }

    /**
     * Returns a list of daenerys commands
     */
    public function getDaenerysCommands(): array
    {
        return $this->daenerysCommands;
    }

    /**
     * Extract from $rawConfig any event subscriptions.
     */
    protected function findSubscriptionPatterns()
    {
        $list = $this->iterateKey("subscriptionPatterns");
        $this->subscriptionPatterns = [];

        foreach ($list as $s) {
            $this->subscriptionPatterns[] = $s;
        }
    }

    /**
     * Returns a list of event subscription patterns and only the patterns.
     */
    public function getSubscriptionPatterns(): array
    {
        return $this->subscriptionPatterns;
    }
}
