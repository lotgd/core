<?php
declare(strict_types=1);

namespace LotGD\Core\Console;

use Symfony\Component\Console\Application;

use LotGD\Core\Bootstrap;
use LotGD\Core\Game;
use LotGD\Core\Console\Command\{
    DatabaseInitCommand,
    ModuleValidateCommand,
    ModuleRegisterCommand
};

class Main {
    protected static $loader = null;
    
    /**
     * Saves a closure used as bootstrap loader
     * @param \Closure $loader
     */
    public static function setBootstrapLoader(\Closure $loader)
    {
        self::$loader = $loader;
    }
    
    /**
     * Creates the game using the previously stored bootstrap loader or
     * uses the default one
     * @return Game
     */
    public static function createGame(): Game
    {
        if (is_null(self::$loader)) {
            return Bootstrap::createGame();
        }
        
        return $loader();
    }
            
    public static function main()
    {
        $application = new Application();

        $application->setName("daenerys 🐲 ");
        $application->setVersion("0.0.1 (lotgd/core version " . \LotGD\Core\Game::getVersion() . ")");

        $application->add(new ModuleValidateCommand());
        $application->add(new ModuleRegisterCommand());
        $application->add(new DatabaseInitCommand());
        
        $application->run();
    }
}
