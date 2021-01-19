<?php
declare(strict_types=1);

namespace LotGD\Core\Console\Command\Module;

use LotGD\Core\Console\Command\BaseCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Danerys command to validate installed modules.
 */
class ModuleValidateCommand extends BaseCommand
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName('module:validate')
            ->setDescription('Validate installed modules')
        ;
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $results = $this->game->getModuleManager()->validate();

        if (\count($results) > 0) {
            foreach ($results as $r) {
                $output->writeln($r);
            }

            return Command::FAILURE;
        }
        $output->writeln("<info>LotGD modules validated</info>");

        return Command::SUCCESS;
    }
}
