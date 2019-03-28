<?php
declare(strict_types=1);


namespace LotGD\Core\Console\Command;


use LotGD\Core\Models\Character;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command to list all characters.
 * @package LotGD\Core\Console\Command
 */
class CharacterListCommand extends BaseCommand
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName('character:list')
            ->setDescription('Lists all characters');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $characters = $this->game->getEntityManager()->getRepository(Character::class)->findAll();

        $table = [["id", "name", "level"], []];
        foreach ($characters as $character) {
            $table[1][] = [
                $character->getId(),
                $character->getDisplayName(),
                $character->getLevel()
            ];
        }

        $io->table(...$table);
    }
}