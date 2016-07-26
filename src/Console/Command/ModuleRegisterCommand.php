<?php
declare(strict_types=1);

namespace LotGD\Core\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use LotGD\Core\Exceptions\ClassNotFoundException;
use LotGD\Core\Exceptions\ModuleAlreadyExistsException;

class ModuleRegisterCommand extends BaseCommand
{    
    protected function configure()
    {
        $this->setName('module:register')
             ->setDescription('Register and initialize any newly installed modules');
    }

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
