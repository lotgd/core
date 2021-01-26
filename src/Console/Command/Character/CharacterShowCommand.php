<?php
declare(strict_types=1);

namespace LotGD\Core\Console\Command\Character;

use Exception;
use LotGD\Core\Console\Command\BaseCommand;
use LotGD\Core\Models\Character;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Resets the viewpoint of a given character.
 */
class CharacterShowCommand extends BaseCommand
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName('character:show')
            ->setDescription('Shows details about character.')
            ->setDefinition(
                new InputDefinition([
                    new InputArgument(
                        "id",
                        mode: InputArgument::REQUIRED,
                        description: "Character ID",
                    ),
                    new InputOption(
                        "onlyViewpoint",
                        mode: InputOption::VALUE_NONE,
                        description: "Set to true to only display viewpoint",
                    )
                ])
            )
        ;
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $em = $this->game->getEntityManager();
        $logger = $this->game->getLogger();

        $io = new SymfonyStyle($input, $output);

        $id = $input->getArgument("id");
        $onlyViewpoint = $input->getOption("onlyViewpoint");

        // Find character
        /** @var Character $character */
        $character = $em->getRepository(Character::class)->find($id);

        if (!$character) {
            $io->error("The character with the id {$id} was not found.");
            return Command::FAILURE;
        }

        if (!$onlyViewpoint) {
            $io->title("About Character {$character->getName()}");

            $io->listing([
                "ID: {$character->getId()}",
                "Display name: {$character->getDisplayName()}",
                "Level: {$character->getLevel()}",
                "Health: {$character->getHealth()}/{$character->getMaxHealth()}",
                "Alive: ".($character->isAlive()?"yes":"no"),
                "Attack: {$character->getAttack()}",
                "Defense: {$character->getDefense()}",
            ]);

            $io->section("Viewpoint");
        } else {
            $io->title("Viewpoint of {$character->getName()}");
        }

        $viewpoint = $character->getViewpoint();

        if (!$viewpoint) {
            $io->text("No viewpoint yet");
        } else {
            $io->text($viewpoint->getTitle() . "\n");
            $io->text($viewpoint->getDescription());

            $io->section("Viewpoint actions");
            $actionGroups = $viewpoint->getActionGroups();

            $rows = [];

            foreach ($actionGroups as $actionGroup) {
                $rows[] = [$actionGroup->getId(), $actionGroup->getTitle(), "", "", ""];

                foreach ($actionGroup->getActions() as $action) {
                    $rows[] = ["", "", $action->getId(), $action->getTitle(), $action->getDestinationSceneId()];
                }

                if (count($actionGroup->getActions())) {
                    $rows[] = new TableSeparator();
                }
            }

            $io->table(["Group id", "Group name", "Action id", "Action name", "Destination"], $rows);
        }

        return Command::SUCCESS;
    }
}
