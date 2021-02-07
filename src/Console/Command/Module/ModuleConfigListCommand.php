<?php
declare(strict_types=1);

namespace LotGD\Core\Console\Command\Module;

use LotGD\Core\Events\EventContextData;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ModuleConfigListCommand extends ModuleBaseCommand
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName($this->namespaced("config:list"))
            ->setDescription('List available configuration option for a module')
            ->setDefinition([
                $this->getModuleNameArgumentDefinition(),
            ])
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $module = $this->getModuleModel($input);

        if (!$module) {
            $io->error("Module was not found.");
            return Command::FAILURE;
        }

        // Create hook
        $context = EventContextData::create([
            "module" => $module,
            "io" => $io,
            "settings" => [],
        ]);
        $newContext = $this->game->getEventManager()->publish(
            event: "h/lotgd/core/cli/module-config-list/".$module->getLibrary(),
            contextData: $context
        );
        $settings = $newContext->get("settings");

        $io->title("Module ".$module->getLibrary());

        if (count($settings) === 0) {
            $io->note("This module does not provide any settings.");
        } else {
            $io->table(["setting", "value", "description"], $settings);
        }

        return Command::SUCCESS;
    }
}