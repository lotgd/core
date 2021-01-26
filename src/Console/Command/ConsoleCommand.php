<?php
declare(strict_types=1);

namespace LotGD\Core\Console\Command;

use LotGD\Core\Game;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Daenerys command to start a PHP REPL with a basic game set up.
 */
class ConsoleCommand extends BaseCommand
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName('console')
            ->setDescription('Start a shell to interact with the game')
        ;
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        print "Daenerys console, the dragon prompt. lotgd/core " . Game::getVersion() . ".\n";
        print "Enter some PHP, but be careful, this is live and attached to your currently configured setup:\n\n";
        print $this->game->getConfiguration();

        print "\n";
        print "Try things like `\$g::getVersion()`. To quit, ^D or `exit();`.\n";
        print "\n";

        $boris = new \Boris\Boris('🐲 > ');
        $boris->setLocal([
            'g' => $this->game,
        ]);
        $boris->start();

        return Command::SUCCESS;
    }
}
