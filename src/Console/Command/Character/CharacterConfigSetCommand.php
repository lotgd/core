<?php
declare(strict_types=1);

namespace LotGD\Core\Console\Command\Character;

use LotGD\Core\Events\EventContextData;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CharacterConfigSetCommand extends CharacterBaseCommand
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName($this->namespaced("config:set"))
            ->setDescription('Change a character setting')
            ->setDefinition([
                $this->getCharacterIdArgumentDefinition(),
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
        $character = $this->getCharacter($input->getArgument("id"));

        if (!$character) {
            $io->error("Module was not found.");
            return Command::FAILURE;
        }

        $io->title("Character {$character->getDisplayName()}");

        // Create hook
        $context = EventContextData::create([
            "character" => $character,
            "io" => $io,
            "setting" => $input->getArgument("setting"),
            "value" => $input->getArgument("value"),
            "return" => Command::FAILURE,
            "reason" => "Setting does not exist.",
        ]);
        $newContext = $this->game->getEventManager()->publish(
            event: "h/lotgd/core/cli/character-config-set",
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