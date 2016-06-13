<?php
declare(strict_types=1);

namespace LotGD\Core\Console;


use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;

use LotGD\Core\Console\Command\ModuleValidateCommand;
use LotGD\Core\Console\Command\ModuleRegisterCommand;

class Main {
    /** @var array */
    protected static $commands = [];
            
    /** 
     * Creates a console application
     * @return Application
     */
    protected static function createApplication(): Application
    {
        $application = new Application();

        $application->setName("daenerys 🐲 ");
        $application->setVersion("0.0.1 (lotgd/core version " . \LotGD\Core\Game::getVersion() . ")");
        
        return $application;
    }
    
    /**
     * Creates a console application, registers commands and runs the console app
     */
    public static function main()
    {
        $application = self::createApplication();

        $application->add(new ModuleValidateCommand());
        $application->add(new ModuleRegisterCommand());
        
        foreach (self::$commands as $command) {
            $application->add($command);
        }
        
        $application->run();
    }
    
    /**
     * Registers an additional command for the console
     * @param Command $command The Command to register
     */
    public static function registerCommand(Command $command)
    {
        self::$commands[] = $command;
    }
}
