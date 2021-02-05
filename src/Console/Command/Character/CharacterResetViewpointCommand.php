<?php
declare(strict_types=1);

namespace LotGD\Core\Console\Command\Character;

use Exception;
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
class CharacterResetViewpointCommand extends CharacterBaseCommand
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName($this->namespaced("resetViewpoint"))
            ->setDescription("Resets the viewpoint of a given character.")
            ->setDefinition(
                new InputDefinition([
                    $this->getCharacterIdArgumentDefinition(),
                ]),
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

        /* @var $character Character */
        $character = $em->getRepository(Character::class)->find($id);

        if ($character === null) {
            $io->error("Character not found.");
            return Command::FAILURE;
        }

        if ($character->getViewpoint() === null) {
            $io->info("Character does not have a viewpoint yet.");
        } else {
            try {
                $em->remove($character->getViewpoint());
                $character->setViewpoint(null);

                $io->success("Viewpoint was successfully reset.");

                # Save
                $em->flush();
            } catch (Exception $e) {
                $io->error("Resetting the viewpoint was not possible. Reason: {$e}");
                return Command::FAILURE;
            }
        }

        return Command::SUCCESS;
    }
}
