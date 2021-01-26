<?php
declare(strict_types=1);

namespace LotGD\Core\Console\Command\Scene;

use LotGD\Core\Console\Command\BaseCommand;
use LotGD\Core\Models\Character;
use LotGD\Core\Models\Scene;
use LotGD\Core\Models\SceneConnectable;
use LotGD\Core\Models\SceneConnection;
use LotGD\Core\Models\SceneConnectionGroup;
use LotGD\Core\Models\SceneTemplate;
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
class SceneRemoveCommand extends BaseCommand
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName('scene:remove')
            ->setDescription('Removes a scene.')
            ->setDefinition(
                new InputDefinition([
                    new InputArgument(
                        "id",
                        mode: InputArgument::REQUIRED,
                        description: "Scene ID",
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

        $sceneId = $input->getArgument("id");

        // Get scene
        /** @var Scene $scene */
        $scene = $em->getRepository(Scene::class)->find($sceneId);

        if (!$scene) {
            $io->error("The scene with the ID {$sceneId} was not found.");
            return Command::FAILURE;
        }

        if (!$scene->isRemovable()) {
            $io->error("The scene with the ID {$sceneId} was marked as not removable. Please remove the responsible");
            return Command::FAILURE;
        }

        // Mark for removal and flush
        try {
            $em->remove($scene);
            $em->flush();
        } catch (\Exception $e) {
            $io->error("Removal of {$sceneId} was not possible: {$e->getMessage()}");
            return Command::FAILURE;
        }

        $io->success("Scene was successfully removed.");

        return Command::SUCCESS;
    }
}
