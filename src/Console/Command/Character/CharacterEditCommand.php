<?php
declare(strict_types=1);

namespace LotGD\Core\Console\Command\Character;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Resets the viewpoint of a given character.
 */
class CharacterEditCommand extends CharacterBaseCommand
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName($this->namespaced("edit"))
            ->setDescription("Edit a given character.")
            ->setDefinition(
                new InputDefinition([
                    $this->getCharacterIdArgumentDefinition(),
                    new InputOption(
                        "level",
                        mode: InputOption::VALUE_REQUIRED,
                        description: "Changes the level"
                    ),
                    new InputOption(
                        "maxHealth",
                        mode: InputOption::VALUE_REQUIRED,
                        description: "Change maximum amount of health points. Health will be adjusted accordingly."
                    ),
                    new InputOption(
                        "heal",
                        mode: InputOption::VALUE_NONE,
                        description: "Restores full health"
                    ),
                    new InputOption(
                        "revive",
                        mode: InputOption::VALUE_OPTIONAL,
                        description: "Revives at full health. Give a number between 0 and 1 to revive fractionally.",
                        default: false,
                    ),
                    new InputOption(
                        "kill",
                        mode: InputOption::VALUE_NONE,
                        description: "Kills"
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
        $logger = $this->getCliLogger();
        $io = new SymfonyStyle($input, $output);

        $id = $input->getArgument("id");
        $level = $input->getOption("level");
        $heal = $input->getOption("heal");
        $revive = $input->getOption("revive");
        $kill = $input->getOption("kill");
        $maxHealth = $input->getOption("maxHealth");

        $changed = false;

        // Find character
        $character = $this->getCharacter($id);

        if (!$character) {
            $io->error("The character with the id {$id} was not found.");
            return Command::FAILURE;
        }

        // Change level
        if ($level !== null) {
            $level = intval($level);

            if ($level <= 0) {
                $io->error("Cannot set the level below 1.");
                return Command::FAILURE;
            }

            // Only change level if necessary
            if ($character->getLevel() !== $level) {
                // Log
                $logger->info("Character level changed", [
                    "for" => $character,
                    "from" => $character->getLevel(),
                    "to" => $level
                ]);

                // Change
                $character->setLevel($level);
                $changed = true;
            }
        }

        // Heal
        if ($heal) {
            if ($character->getHealth() >= $character->getMaxHealth()) {
                $io->note("Character is already at full health.");
            } elseif ($character->isAlive()) {
                $oldHealth = $character->getHealth();

                // Log
                $logger->info("Character health changed", [
                    "for" => $character,
                    "from" => $oldHealth,
                    "to" => $character->getMaxHealth()
                ]);

                // Change
                $character->setHealth($character->getMaxHealth());
                $io->success("Character was restored to full health ({$oldHealth} to {$character->getMaxHealth()}).");
                $changed = true;
            } else {
                $io->error("Cannot heal a dead character. Use --revive instead.");
                return Command::FAILURE;
            }
        }

        // Revive the character
        if ($revive !== false) {
            // Make sure we revive between 0 and 1
            if ($revive === null) {
                $revive = 1;
            } elseif (str_ends_with($revive, "%")) {
                $revive = min(floatval($revive)/100, 1);
            } else {
                $revive = min(floatval($revive), 1);
            }

            if ($character->isAlive()) {
                $io->error("Character is already alive. Use --heal instead.");
                return Command::FAILURE;
            } else {
                // Make sure we heal at least by 1.
                $reviveAmount = (int)round(max($revive * $character->getMaxHealth(), 1), 0);

                // Log
                $logger->info("Character was revived", [
                    "for" => $character,
                    "to" => $reviveAmount
                ]);

                // Change
                $character->setHealth($reviveAmount);
                $io->success("Character was revived with {$reviveAmount} of health points "
                    ."(max: {$character->getMaxHealth()}).");
                $changed = true;
            }
        }

        if ($kill) {
            if (!$character->isAlive()) {
                $io->error("What is dead may never die.");
                return Command::FAILURE;
            } else {
                // Log
                $logger->info("Character was killed", ["for" => $character]);

                // Change
                $character->setHealth(0);
                $io->success("Character was killed.");
                $changed = true;
            }
        }

        if ($maxHealth) {
            $maxHealth = intval($maxHealth);
            if ($maxHealth < 0) {
                $io->error("Cannot set maximum health below 0.");
                return Command::FAILURE;
            }

            if ($character->getMaxHealth() === 0) {
                $healthProportion = 0;
            } else {
                $healthProportion = $character->getHealth() / $character->getMaxHealth();
            }

            // Log
            $logger->info("Character maxHealth changed", [
                "for" => $character,
                "from" => $character->getMaxHealth(),
                "to" => $maxHealth
            ]);

            // Change
            $character->setMaxHealth($maxHealth);
            $character->setHealth((int)round($healthProportion*$maxHealth, 0));
            $io->success("Character has new maximum health of {$maxHealth} (current health is {$character->getHealth()}).");
            $changed = true;
        }

        // Save changes
        if ($changed) {
            try {
                $em->flush();
                $io->success("The character was successfully changed.");

                // Log
                $logger->info("Changed committed.");
            } catch (Exception $e) {
                $io->error("Character could not be saved. Reason: {$e}");
                $logger->error("Changes rolled back.", ["exception" => $e]);
                return Command::FAILURE;
            }
        } else {
            $io->note("Nothing was changed.");
        }

        return Command::SUCCESS;
    }
}
