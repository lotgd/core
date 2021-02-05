<?php
declare(strict_types=1);

namespace LotGD\Core\Console\Command\Character;

use Exception;
use LotGD\Core\Models\Character;
use LotGD\Core\Models\Repositories\CharacterRepository;
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
class CharacterRemoveCommand extends CharacterBaseCommand
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName($this->namespaced("remove"))
            ->setDescription("Definitely removes a character (no soft delete).")
            ->setDefinition(
                new InputDefinition([
                    $this->getCharacterIdArgumentDefinition(),
                    new InputOption(
                        name: "soft",
                        mode: InputOption::VALUE_NONE,
                        description: "Only removes the character softly (soft delete)."
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
        /** @var CharacterRepository $characterRepository */
        $characterRepository = $em->getRepository(Character::class);
        $logger = $this->getCliLogger();

        $io = new SymfonyStyle($input, $output);

        $id = $input->getArgument("id");

        // Find character
        /** @var Character $character */
        $character = $characterRepository->findWithSoftDeleted($id);

        if (!$character) {
            $io->error("The character with the id {$id} was not found.");
            return Command::FAILURE;
        }

        if ($character->isDeleted()) {
            $io->info("Character was marked as soft-deleted.");
        }

        try {
            if ($input->getOption("soft")) {
                // Only soft-delete if requested
                $character->delete($em);

                // Commit changes
                $em->flush();

                $io->success("{$character} was successfully soft-deleted.");
                $logger->info("Character was soft-deleted.", ["character" => $character]);
            } else {
                $em->remove($character);

                // Commit changes
                $em->flush();

                $io->success("{$character} was successfully removed.");
                $logger->info("Character was removed.", ["character" => $character]);
            }
        } catch (Exception $e) {
            $io->error("Removing {$character} was not possible. Reason: {$e}.");
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
