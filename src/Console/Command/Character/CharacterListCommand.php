<?php
declare(strict_types=1);

namespace LotGD\Core\Console\Command\Character;

use LotGD\Core\Models\Character;
use LotGD\Core\Models\Repositories\CharacterRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command to list all characters.
 */
class CharacterListCommand extends CharacterBaseCommand
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName($this->namespaced("list"))
            ->setDescription('Lists all characters')
            ->setDefinition(
                new InputDefinition([
                    new InputOption(
                        "includeSoftDeleted",
                        mode: InputOption::VALUE_NONE,
                        description: "Includes soft-deleted characters",
                    ),
                    new InputOption(
                        "onlySoftDeleted",
                        mode: InputOption::VALUE_NONE,
                        description: "Displays only soft-deleted characters",
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
        $io = new SymfonyStyle($input, $output);

        /** @var CharacterRepository $repository */
        $repository = $em->getRepository(Character::class);

        $marker = "";
        if ($input->getOption("includeSoftDeleted")) {
            $io->writeln("* marks soft-deleted characters");

            $marker = "*";
            $characters = $repository->findAll(CharacterRepository::INCLUDE_SOFTDELETED);
        } elseif ($input->getOption("onlySoftDeleted")) {
            $io->writeln("Only soft-deleted characters are shown");
            $characters = $repository->findAll(CharacterRepository::ONLY_SOFTDELETED);
        } else {
            $characters = $repository->findAll();
        }

        $table = [["id", "name", "level"], []];
        foreach ($characters as $character) {
            $table[1][] = [
                $character->getId(),
                $marker.$character->getName(),
                $character->getLevel(),
            ];
        }

        $io->table(...$table);

        return Command::SUCCESS;
    }
}
