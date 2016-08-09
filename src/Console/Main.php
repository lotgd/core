<?php
declare(strict_types=1);

namespace LotGD\Core\Console;

use Symfony\Component\Console\Application;

use LotGD\Core\Bootstrap;
use LotGD\Core\Game;
use LotGD\Core\Console\Command\{
    DatabaseInitCommand,
    ModuleValidateCommand,
    ModuleRegisterCommand,
    ConsoleCommand
};

/**
 * Main execution class for the daenerys tool.
 */
class Main
{
    private $application;
    private $bootstrap;
    private $game;

    /**
     * Construct a new daenerys tool instance.
     */
    public function __construct()
    {
        $this->application = new Application();

        $this->application->setName("daenerys 🐲 ");
        $this->application->setVersion("0.0.1 (lotgd/core version " . \LotGD\Core\Game::getVersion() . ")");
    }

    /**
     * Add supported commands, including those configured from lotgd.yml files.
     */
    protected function addCommands()
    {
        $this->application->add(new ModuleValidateCommand($this->game));
        $this->application->add(new ModuleRegisterCommand($this->game));
        $this->application->add(new DatabaseInitCommand($this->game));
        $this->application->add(new ConsoleCommand($this->game));

        // Add additional ones
        $this->bootstrap->addDaenerysCommands($this->application);
    }

    /**
     * Run the danerys tool.
     */
    public function run()
    {
        // Bootstrap application
        $this->bootstrap = new Bootstrap();
        $this->game = $this->bootstrap->getGame();

        // Add commands
        $this->addCommands();

        // Run
        $this->application->run();
    }
}
