<?php
declare(strict_types=1);

namespace LotGD\Core\Console\Command;

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
             ->setDescription('Validate installed modules');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $results = $this->game->getModuleManager()->validate();

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
