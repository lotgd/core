<?php
declare(strict_types=1);

namespace LotGD\Core\Console\Command\Character;

use LotGD\Core\Events\EventContextData;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CharacterConfigListCommand extends CharacterBaseCommand
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName($this->namespaced("config:list"))
            ->setDescription('List available settings for a character')
            ->setDefinition([
                $this->getCharacterIdArgumentDefinition(),
            ])
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $character = $this->getCharacter($input->getArgument("id"));

        if (!$character) {
            $io->error("Character was not found.");
            return Command::FAILURE;
        }

        // Create hook
        $context = EventContextData::create([
            "character" => $character,
            "io" => $io,
            "settings" => [],
        ]);
        $newContext = $this->game->getEventManager()->publish(
            event: "h/lotgd/core/cli/character-config-list",
            contextData: $context
        );
        $settings = $newContext->get("settings");

        $io->title("Character ".$character->getDisplayName());

        if (count($settings) === 0) {
            $io->note("There are no character settings available.");
        } else {
            $io->table(["setting", "value", "description"], $settings);
        }

        return Command::SUCCESS;
    }
}