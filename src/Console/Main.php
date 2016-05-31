<?php
declare(strict_types=1);

namespace LotGD\Core\Console;

use LotGD\Core\Console\Command\ModuleValidateCommand;
use Symfony\Component\Console\Application;

class Main {
    public static function main()
    {
        $application = new Application();

        $application->setName("daenerys 🐲 ");
        $application->setVersion("0.0.1");

        $application->add(new ModuleValidateCommand());
        $application->run();
    }
}
