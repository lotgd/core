<?php

namespace LotGD\Core;

use Composer\Package\PackageInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Yaml\Yaml;

use LotGD\Core\ComposerManager;

/**
 * BootConfiguration
 * @author sauterb
 */
class BootConfiguration
{
    /** @var ComposerManager */
    private $composerManager;
    /** @var PackageInterface */
    private $package;
    /** @var string */
    private $rootNamespace;
    /** @var array */
    private $rawConfig;
    private $models;
    private $daenerysCommands;
    private $cwd;
    
    public function __construct(ComposerManager $composerManager, PackageInterface $package, string $cwd)
    {
        $this->composerManager = $composerManager;
        $this->package = $package;
        $this->cwd = $cwd;
        
        $installationManager = $composerManager->getComposer()->getInstallationManager();
        
        // only lotgd-modules are installed in the vendor directory
        if ($package->getType() === "lotgd-module") {
            $confFile = $installationManager->getInstallPath($package)  . DIRECTORY_SEPARATOR . "lotgd.yml";
        }
        else {
            $confFile = $cwd . DIRECTORY_SEPARATOR . "lotgd.yml";
        }
        
        $this->rootNamespace = $this->findRootNamespace($package);
        if (file_exists($confFile)) {
            $this->rawConfig = Yaml::parse(file_get_contents($confFile));
        }
        else {
            $name = $package->getName();
            $type = $package->getType();
            throw new \Exception("Package {$name} of type {$type} does not have a lotgd.yml in it's root ($confFile).");
        }
        
        $this->findEntityDirectory();
        $this->findDaenerysCommands();
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
     * Returns a subkey if it exists or null.
     * @param array $arguments
     * @return type
     */
    protected function getSubKeyIfItExists(array $arguments)
    {
        $parent = $this->rawConfig;
        
        foreach ($arguments as $argument){
            if (isset($parent[$argument])) {
                $parent = $parent[$argument];
            }
            else {
                return null;
            }
        }
        
        return $parent;
    }
    
    /**
     * Tries to iterate an array element given by the arguments
     * @param scalar $argument1,... array keys, by increasing depth
     */
    protected function iterateKey(...$arguments)
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
     * internal function. Adds models to the boot configuration.
     */
    protected function findEntityDirectory()
    {
        $this->entityDirectory = null;
        
        $entityNamespace = $this->getConfig("bootstrap", "entityNamespace");
        $entityNamespace = $this->rootNamespace . $entityNamespace;
        
        if (is_null($entityNamespace) === false) {
            $entityDirectory = $this->composerManager->translateNamespaceToPath($entityNamespace, $this->cwd);
            
            if (is_dir($entityDirectory) === false) {
                throw new \Exception("{$entityDirectory}, generated from {$entityNamespace}, is not a valid directory.");
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
     * @return type
     */
    protected function findDaenerysCommands()
    {
        $list = $this->iterateKey("bootstrap", "daenerysCommands");
        $this->daenerysCommands = [];
        
        foreach ($list as $command) {
            $this->daenerysCommands = $this->rootNamespace . $command;
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
}
