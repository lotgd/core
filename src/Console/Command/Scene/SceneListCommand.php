<?php
declare(strict_types=1);

namespace LotGD\Core\Console\Command\Scene;

use LotGD\Core\Console\Command\BaseCommand;
use LotGD\Core\Models\Scene;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class SceneListCommand extends SceneBaseCommand
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName($this->namespaced("list"))
            ->setDescription("Lists all scenes")
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

        $table = [["id", "title", "connections", "template"], []];
        foreach ($scenes as $scene) {
            $table[1][] = [
                $scene->getId(),
                $scene->getTitle(),
                count($scene->getConnectedScenes()),
                $scene->getTemplate()?->getClass(),
            ];
        }

        $io->table(...$table);

        return Command::SUCCESS;
    }
}