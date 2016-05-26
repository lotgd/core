<?php
declare(strict_types=1);

namespace LotGD\Core\Console\Command;

use LotGD\Core\Bootstrap;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ModuleInstallCommand extends Command
{
    protected function configure()
    {
        $this->setName('module:install')
             ->setDescription('Install a new LotGD module')
             ->addArgument(
                 'names',
                 InputArgument::IS_ARRAY | InputArgument::REQUIRED,
                 'List of module names to install, vendor/module-name format'
             );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
    }
}
