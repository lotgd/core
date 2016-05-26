<?php
declare(strict_types=1);

namespace LotGD\Core\Console;

use LotGD\Core\Console\Command\ModuleCommand;
use Symfony\Component\Console\Application;

class Main {
    public static function main()
    {
        $application = new Application();
        $application->add(new ModuleInstallCommand());
        $application->run();
    }
}
