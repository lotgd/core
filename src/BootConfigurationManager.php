<?php

namespace LotGD\Core;

use Symfony\Component\Console\Application;

use LotGD\Core\ComposerManager;

class BootConfigurationManager
{
    private $cwd;
    /** @var array<BootConfiguration> */
    private $configurations = null;
    
    public function __construct(ComposerManager $composerManager, string $cwd)
    {
        $this->cwd = $cwd;
        
        $packages = $composerManager->getPackages();      
        $this->configurations = [];
        
        foreach($packages as $package)
        {            
            if ($package->getType() === "lotgd-crate" || $package->getType() === "lotgd-module") {
                $this->configurations[] = new BootConfiguration($composerManager, $package);
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
    
    public function addDaenerysCommands(Game $game, Application $application)
    {
        foreach ($this->configurations as $config) {
            if ($config->hasDaenerysCommands()) {
                $this->addDaenerysCommands($game, $application);
            }
        }
    }
}