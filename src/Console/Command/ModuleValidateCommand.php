<?php
declare(strict_types=1);

namespace LotGD\Core\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use LotGD\Core\Console\Main;

class ModuleValidateCommand extends Command
{
    protected function configure()
    {
        $this->setName('module:validate')
             ->setDescription('Validate installed modules');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $g = Main::createGame();

        $results = $g->getModuleManager()->validate();

        if (count($results) > 0) {
            foreach ($results as $r) {
                $output->writeln($r);
            }
            return 1;
        } else {
            $output->writeln("<info>LotGD modules validated</info>");
            return 0;
        }
    }
}
