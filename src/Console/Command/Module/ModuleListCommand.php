<?php
declare(strict_types=1);

namespace LotGD\Core\Console\Command\Module;

use LotGD\Core\Models\Module;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ModuleListCommand extends ModuleBaseCommand
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName($this->namespaced("list"))
            ->setDescription('List all installed modules.')
        ;
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        /** @var Module[] $modules */
        $modules = $this->game->getModuleManager()->getModules();

        $io->title("Installed modules");

        if (count($modules) > 0) {
            $listing = [];
            foreach ($modules as $module) {
                $listing[] = [$module->getLibrary() => $module->getCreatedAt()->format("d M Y, H:i")];
            }

            $io->definitionList(...$listing);
        } else {
            $io->note("No modules installed.");
        }

        return Command::SUCCESS;
    }
}