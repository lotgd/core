<?php
declare(strict_types=1);

namespace LotGD\Core\Console\Command;

use LotGD\Core\Models\Character;
use Symfony\Component\Console\Command\Command;
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
                    new InputOption('id', null, InputOption::VALUE_REQUIRED),
                ])
            )
        ;
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $id = $input->getOption("id");

        /* @var $character \LotGD\Core\Models\Character */
        $character = $this->game->getEntityManager()->getRepository(Character::class)->find($id);

        if ($character === null) {
            $io->error("Character not found.");
            return;
        }

        $this->game->getEntityManager()->remove($character->getViewpoint());
        $character->setViewpoint(null);

        $this->game->getEntityManager()->flush();

        return Command::SUCCESS;
    }
}
