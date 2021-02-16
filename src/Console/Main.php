<?php
declare(strict_types=1);

namespace LotGD\Core\Console;

use LotGD\Core\Bootstrap;

use LotGD\Core\Console\Command\Character\CharacterAddCommand;
use LotGD\Core\Console\Command\Character\CharacterEditCommand;
use LotGD\Core\Console\Command\Character\CharacterListCommand;
use LotGD\Core\Console\Command\Character\CharacterRemoveCommand;
use LotGD\Core\Console\Command\Character\CharacterResetViewpointCommand;
use LotGD\Core\Console\Command\Character\CharacterShowCommand;
use LotGD\Core\Console\Command\ConsoleCommand;
use LotGD\Core\Console\Command\Database\DatabaseInitCommand;
use LotGD\Core\Console\Command\Database\DatabaseSchemaUpdateCommand;
use LotGD\Core\Console\Command\Module\CharacterConfigListCommand;
use LotGD\Core\Console\Command\Module\CharacterConfigResetCommand;
use LotGD\Core\Console\Command\Module\CharacterConfigSetCommand;
use LotGD\Core\Console\Command\Module\ModuleListCommand;
use LotGD\Core\Console\Command\Module\ModuleRegisterCommand;
use LotGD\Core\Console\Command\Module\ModuleValidateCommand;
use LotGD\Core\Console\Command\SceneTemplates\SceneTemplateListCommand;
use LotGD\Core\Console\Command\Scene\SceneAddCommand;
use LotGD\Core\Console\Command\Scene\SceneAddConnectionGroupCommand;
use LotGD\Core\Console\Command\Scene\SceneConnectCommand;
use LotGD\Core\Console\Command\Scene\SceneDisconnectCommand;
use LotGD\Core\Console\Command\Scene\SceneListCommand;
use LotGD\Core\Console\Command\Scene\SceneRemoveCommand;
use LotGD\Core\Console\Command\Scene\SceneRemoveConnectionGroupCommand;
use LotGD\Core\Console\Command\Scene\SceneShowCommand;
use LotGD\Core\Game;
use Symfony\Component\Console\Application;

/**
 * Main execution class for the daenerys tool.
 */
class Main
{
    private Application $application;
    private Bootstrap $bootstrap;
    private Game $game;

    /**
     * Construct a new daenerys tool instance.
     */
    public function __construct()
    {
        $this->application = new Application();

        $this->application->setName("daenerys 🐲 ");
        $this->application->setVersion("lotgd/core version " . Game::getVersion() . "");
    }

    /**
     * Add supported commands, including those configured from lotgd.yml files.
     */
    protected function addCommands()
    {
        $this->application->add(new DatabaseInitCommand($this->game));
        $this->application->add(new DatabaseSchemaUpdateCommand($this->game));

        $this->application->add(new ConsoleCommand($this->game));

        // Module commands
        $this->application->add(new CharacterConfigListCommand($this->game));
        $this->application->add(new CharacterConfigResetCommand($this->game));
        $this->application->add(new CharacterConfigSetCommand($this->game));
        $this->application->add(new ModuleListCommand($this->game));
        $this->application->add(new ModuleRegisterCommand($this->game));
        $this->application->add(new ModuleValidateCommand($this->game));

        // Character commands
        $this->application->add(new CharacterAddCommand($this->game));
        $this->application->add(new CharacterEditCommand($this->game));
        $this->application->add(new CharacterListCommand($this->game));
        $this->application->add(new CharacterRemoveCommand($this->game));
        $this->application->add(new CharacterResetViewpointCommand($this->game));
        $this->application->add(new CharacterShowCommand($this->game));

        // Scene commands
        $this->application->add(new SceneAddCommand($this->game));
        $this->application->add(new SceneListCommand($this->game));
        $this->application->add(new SceneRemoveCommand($this->game));
        $this->application->add(new SceneShowCommand($this->game));

        // Scene connections
        $this->application->add(new SceneConnectCommand($this->game));
        $this->application->add(new SceneDisconnectCommand($this->game));

        // Scene connection group
        $this->application->add(new SceneAddConnectionGroupCommand($this->game));
        $this->application->add(new SceneRemoveConnectionGroupCommand($this->game));

        // Scene templates
        $this->application->add(new SceneTemplateListCommand($this->game));

        // Add additional ones
        $this->bootstrap->addDaenerysCommands($this->application);
    }

    /**
     * Run the Daenerys tool.
     */
    public function run()
    {
        // Bootstrap application
        $this->bootstrap = new Bootstrap();
        $this->game = $this->bootstrap->getGame(\getcwd());

        // Add commands
        $this->addCommands();

        // Run
        $this->application->run();
    }
}
