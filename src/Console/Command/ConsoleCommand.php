<?php

declare(strict_types = 1);

namespace LotGD\Core\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use LotGD\Core\Console\Main;

class ConsoleCommand extends Command
{
    protected function configure()
    {
        $this->setName('console')
             ->setDescription('Start a shell to interact with the game.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $g = Main::createGame();

        $boris = new \Boris\Boris('🐲> ');
        $boris->setLocal(array(
            'g' => $g
        ));
        $boris->start();
    }
}
