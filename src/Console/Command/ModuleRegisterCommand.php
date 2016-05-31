<?php
declare(strict_types=1);

namespace LotGD\Core\Console\Command;

use LotGD\Core\Bootstrap;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ModuleRegisterCommand extends Command
{
    protected function configure()
    {
        $this->setName('module:register')
             ->setDescription('Register and initialize any newly installed modules');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // TODO: move these somewhere more generic.
        $style = new OutputFormatterStyle('yellow');
        $output->getFormatter()->setStyle('warning', $style);

        $g = Bootstrap::createGame();

        $modules = $g->getModuleManager()->getModules();

        foreach ($modules as $m) {
            $library = $m->getLibrary();

            try {
                $p = $g->getModuleManager()->getPackageForLibrary($library);

                $g->getModuleManager()->register($library, $p);

                $output->writeln("<info>Registered new module {$library}.</info>");
            } catch (LibraryDoesNotExistException $e) {
                $output->writeln("<warning>Module {$library} registered but no longer installed with Composer.</warning>");
            } catch (ModuleAlreadyExistsException $e) {
                $output->writeln("Skipping already registered module {$library}.");
            }
        }
    }
}
