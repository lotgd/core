<?php
declare(strict_types=1);

namespace LotGD\Core\Console\Command\Character;

use Exception;
use LotGD\Core\Console\Command\BaseCommand;
use LotGD\Core\Models\Character;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Resets the viewpoint of a given character.
 */
class CharacterAddCommand extends BaseCommand
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName('character:add')
            ->setDescription('Add a character.')
            ->setDefinition(
                new InputDefinition([
                    new InputArgument(
                        "name",
                        mode: InputArgument::REQUIRED,
                        description: "Character name",
                    ),
                    new InputOption(
                        "level",
                        mode: InputOption::VALUE_OPTIONAL,
                        description: "Character level",
                        default: 1,
                    ),
                    new InputOption(
                        "maxHealth",
                        mode: InputOption::VALUE_OPTIONAL,
                        description: "Maximum health of the character. 10*level if not given.",
                        default: null,
                    ),
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

        $name = $input->getArgument("name");
        $level = intval($input->getOption("level"));
        $maxHealth = $input->getOption("maxHealth");

        if ($level <= 0) {
            $io->error("Level must at least be 1.");
            return Command::FAILURE;
        }

        // Set maxHealth in dependence of the level if not given.
        if ($maxHealth === null) {
            $maxHealth = $level*10;
        } else {
            $maxHealth = intval($maxHealth);
        }

        $character = Character::createAtFullHealth([
            "name" => $name,
            "level" => $level,
            "maxHealth" => $maxHealth,
        ]);

        try {
            $em->persist($character);

            // Commit changes
            $em->flush();
        } catch (Exception $e) {
            $io->error("Creating the character was not possible. Reason: {$e->getMessage()}.");
            return Command::FAILURE;
        }

        $io->success("{$character} was successfully created.");
        $logger->info("{$character} was created.");

        return Command::SUCCESS;
    }
}
