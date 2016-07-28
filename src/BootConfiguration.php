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
    
    public function __construct(ComposerManager $composerManager, PackageInterface $package)
    {
        $this->composerManager = $composerManager;
        $this->package = $package;
        
        $installationManager = $composerManager->getComposer()->getInstallationManager();
        $confFile = $installationManager->getInstallPath($package)  . "/lotgd.yml";
        
        $this->rootNamespace = $this->findRootNamespace($package);
        $this->rawConfig = Yaml::parse(file_get_contents($confFile));
        
        $this->findEntityDirectory();
        $this->findDenerysCommands();
    }
    
    /**
     * Searches for a root namespace
     * 
     * This function searches the package's configuration to find it's root namespace.
     * For this, it uses the following order:
     *  - look in ["extra"]["lotgd-namespace"]
     *  - check psr-4 autoload configuration. If used, it takes the first element
     *  - check psr-0 autoload configuration. If used, it takes the first element
     * @param PackageInterface $package
     * @return string
     * @throws \Exception if no namespace has been found
     */
    protected function findRootNamespace(PackageInterface $package): string
    {
        // if one is defined, we use that.
        if (isset($package->getExtra()["lotgd-namespace"])) {
            return $package->getExtra()["lotgd-namespace"];
        }
        
        $autoload = $package->getAutoload();
        if (isset($autoload["psr-4"]) && count($autoload["psr-4"]) > 0) {
            return $autoload["psr-4"][0];
        }
        
        if (isset($autoload["psr-0"]) && count($autoload["psr-0"]) > 0) {
            return $autoload["psr-0"][0];
        }
        
        $name = $package->getName();
        throw new \Exception("{$name} has no valid namespace.");
    }
    
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
            $entityDirectory = $this->composerManager->getComposer()->translateNamespaceToPath($entityNamespace);
            
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
    
    protected function findDenerysCommands()
    {
        $list = $this->iterateKey("bootstrap", "daenerysCommands");
        $this->daenerysCommands = [];
        
        if (is_array($list) === false) {
            return;
        }
        
        foreach ($list as $command) {
            $this->daenerysCommands = $this->rootNamespace . $command;
        }
    }
    
    public function addDaenerysCommands(Game $game, Application $application)
    {
        foreach ($this->daenerysCommands as $command) {
            $application->addCommands(new $command($game));
        }
    }
}
