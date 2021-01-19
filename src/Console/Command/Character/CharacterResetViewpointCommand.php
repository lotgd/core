<?php
declare(strict_types=1);

namespace LotGD\Core\Console\Command\Character;

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
class CharacterResetViewpointCommand extends BaseCommand
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName('character:resetViewpoint')
            ->setDescription('Resets the viewpoint of a given character.')
            ->setDefinition(
                new InputDefinition([
                    new InputArgument("id", InputArgument::REQUIRED, "ID of the character"),
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

        $io = new SymfonyStyle($input, $output);
        $id = $input->getArgument("id");

        /* @var $character Character */
        $character = $em->getRepository(Character::class)->find($id);

        if ($character === null) {
            $io->error("Character not found.");
            return Command::FAILURE;
        }

        $em->remove($character->getViewpoint());
        $character->setViewpoint(null);

        # Save
        $em->flush();

        return Command::SUCCESS;
    }
}
