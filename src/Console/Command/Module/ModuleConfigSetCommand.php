<?php
declare(strict_types=1);

namespace LotGD\Core\Console\Command\Module;

use LotGD\Core\Events\EventContextData;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ModuleConfigSetCommand extends ModuleBaseCommand
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName($this->namespaced("config:set"))
            ->setDescription('Change a module setting')
            ->setDefinition([
                $this->getModuleNameArgumentDefinition(),
                new InputArgument(
                    "setting",
                    mode: InputArgument::REQUIRED,
                    description: "Name of setting, see {$this->namespaced('config:list')}.",
                ),
                new InputArgument(
                    "value",
                    InputArgument::REQUIRED,
                    description: "New value for the given setting.",
                ),
            ])
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logger = $this->getCliLogger();
        $io = new SymfonyStyle($input, $output);
        $module = $this->getModuleModel($input);

        if (!$module) {
            $io->error("Module was not found.");
            return Command::FAILURE;
        }

        $io->title("Module {$module->getLibrary()}");

        // Create hook
        $context = EventContextData::create([
            "module" => $module,
            "io" => $io,
            "setting" => $input->getArgument("setting"),
            "value" => $input->getArgument("value"),
            "return" => Command::FAILURE,
            "reason" => "Setting does not exist.",
        ]);
        $newContext = $this->game->getEventManager()->publish(
            event: "h/lotgd/core/cli/module-config-set/{$module->getLibrary()}",
            contextData: $context
        );
        if ($newContext->get("return") != Command::SUCCESS) {
            $io->error($newContext->get("reason"));
            return Command::FAILURE;
        }

        $this->game->getEntityManager()->flush();

        return Command::SUCCESS;
    }
}