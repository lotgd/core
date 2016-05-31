<?php
declare(strict_types=1);

namespace LotGD\Core\Console;

use LotGD\Core\Console\Command\ModuleValidateCommand;
use LotGD\Core\Console\Command\ModuleRegisterCommand;
use Symfony\Component\Console\Application;

class Main {
    public static function main()
    {
        $application = new Application();

        $application->setName("daenerys 🐲 ");
        $application->setVersion("0.0.1 (lotgd/core version " . \LotGD\Core\Game::getVersion() . ")");

        $application->add(new ModuleValidateCommand());
        $application->add(new ModuleRegisterCommand());
        $application->run();
    }
}
