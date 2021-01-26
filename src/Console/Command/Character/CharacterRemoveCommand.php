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
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Resets the viewpoint of a given character.
 */
class CharacterRemoveCommand extends BaseCommand
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName('character:remove')
            ->setDescription('Definitely removes a character (no soft delete).')
            ->setDefinition(
                new InputDefinition([
                    new InputArgument(
                        "id",
                        mode: InputArgument::REQUIRED,
                        description: "Character ID",
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

        $id = $input->getArgument("id");

        // Find character
        /** @var Character $character */
        $character = $em->getRepository(Character::class)->find($id);

        if (!$character) {
            $io->error("The character with the id {$id} was not found.");
            return Command::FAILURE;
        }

        try {
            $em->remove($character);

            // Commit changes
            $em->flush();
        } catch (Exception $e) {
            $io->error("Removing {$character} was not possible. Reason: {$e->getMessage()}.");
            return Command::FAILURE;
        }

        $io->success("{$character} was successfully removed.");
        $logger->info("{$character} was removed.");

        return Command::SUCCESS;
    }
}
