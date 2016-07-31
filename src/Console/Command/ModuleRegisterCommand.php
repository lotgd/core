<?php
declare(strict_types=1);

namespace LotGD\Core\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use LotGD\Core\Exceptions\ClassNotFoundException;
use LotGD\Core\Exceptions\ModuleAlreadyExistsException;

/**
 * Danerys command to register and initiate any newly installed modules.
 */
class ModuleRegisterCommand extends BaseCommand
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName('module:register')
             ->setDescription('Register and initialize any newly installed modules');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $modules = $this->game->getComposerManager()->getModulePackages();

        foreach ($modules as $p) {
            $library = $p->getName();

            try {
                $this->game->getModuleManager()->register($library, $p);

                $output->writeln("<info>Registered new module {$library}</info>");
            } catch (ModuleAlreadyExistsException $e) {
                $output->writeln("Skipping already registered module {$library}");
            } catch (ClassNotFoundException $e) {
                $output->writeln("<error>Error installing module {$library}: " . $e->getMessage() . "</error>");
            }
        }
    }
}
