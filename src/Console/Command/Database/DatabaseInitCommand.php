<?php
declare(strict_types=1);

namespace LotGD\Core\Console\Command\Database;

use LotGD\Core\Console\Command\BaseCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Danerys command to initiate the database with default values.
 */
class DatabaseInitCommand extends BaseCommand
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName('database:init')
            ->setDescription('Initiates database with default values.')
        ;
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->game->getEntityManager()->flush();

        return Command::SUCCESS;
    }
}
