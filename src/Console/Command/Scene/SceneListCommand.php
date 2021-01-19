<?php
declare(strict_types=1);

namespace LotGD\Core\Console\Command\Scene;

use LotGD\Core\Console\Command\BaseCommand;
use LotGD\Core\Models\Scene;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class SceneListCommand extends BaseCommand
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName('scene:list')
            ->setDescription('Lists all scenes')
        ;
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $em = $this->game->getEntityManager();
        $io = new SymfonyStyle($input, $output);

        /** @var Scene[] $scenes */
        $scenes = $em->getRepository(Scene::class)->findAll();

        $table = [["id", "title", "connections"], []];
        foreach ($scenes as $scene) {
            $table[1][] = [
                $scene->getId(),
                $scene->getTitle(),
                count($scene->getConnectedScenes()),
            ];
        }

        $io->table(...$table);

        return Command::SUCCESS;
    }
}