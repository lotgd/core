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

        print("Daenerys console, the dragon prompt. lotgd/core " . \LotGD\Core\Game::getVersion() . ".\n");
        print("Enter some PHP, but be careful, this is live and attached to your currently configured setup:\n\n");
        print($g->getConfiguration());

        print("\n");
        print("Try things like `\$g::getVersion()`. To quit, ^D or `exit();`.\n");
        print("\n");

        $boris = new \Boris\Boris('🐲  > '); // For some reason we need the extra spaces.
        $boris->setLocal(array(
            'g' => $g
        ));
        $boris->start();
    }
}
