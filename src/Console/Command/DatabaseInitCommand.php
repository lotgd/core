<?php
declare(strict_types=1);

namespace LotGD\Core\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use LotGD\Core\Console\Main;
use LotGD\Core\Game;

class DatabaseInitCommand extends BaseCommand
{
    protected function configure()
    {
        $this->setName('database:init')
             ->setDescription('Initiates database with default values.');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->game->getEntityManager()->flush();
    }
}
