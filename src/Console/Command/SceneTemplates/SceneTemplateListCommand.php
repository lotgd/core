<?php
declare(strict_types=1);

namespace LotGD\Core\Console\Command\SceneTemplates;

use LotGD\Core\Console\Command\BaseCommand;
use LotGD\Core\Models\Scene;
use LotGD\Core\Models\SceneTemplate;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class SceneTemplateListCommand extends BaseCommand
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName('sceneTemplate:list')
            ->setDescription('Lists all registered scene templates')
        ;
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $em = $this->game->getEntityManager();
        $io = new SymfonyStyle($input, $output);

        /** @var SceneTemplate[] $templates */
        $templates = $em->getRepository(SceneTemplate::class)->findAll();

        $table = [["class"], []];
        foreach ($templates as $template) {
            $table[1][] = [
                $template->getClass(),
            ];
        }

        $io->table(...$table);

        return Command::SUCCESS;
    }
}