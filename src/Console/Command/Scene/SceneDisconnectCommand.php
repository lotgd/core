<?php
declare(strict_types=1);

namespace LotGD\Core\Console\Command\Scene;

use LotGD\Core\Console\Command\BaseCommand;
use LotGD\Core\Exceptions\ArgumentException;
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
class SceneDisconnectCommand extends BaseCommand
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName('scene:disconnect')
            ->setDescription('Disconnects two scenes.')
            ->setDefinition(
                new InputDefinition([
                    new InputArgument(
                        "scene1",
                        mode: InputArgument::REQUIRED,
                        description: "Outgoing scene ID",
                    ),
                    new InputArgument(
                        "scene2",
                        mode: InputArgument::REQUIRED,
                        description: "Incoming scene ID",
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
        $sceneRepository = $em->getRepository(Scene::class);

        $io = new SymfonyStyle($input, $output);

        /** @var Scene $scene1 */
        $scene1 = $sceneRepository->find($input->getArgument("scene1"));
        /** @var Scene $scene2 */
        $scene2 = $sceneRepository->find($input->getArgument("scene2"));

        if (!$scene1) {
            $io->error("Scene with id {$input->getArgument('scene1')} was not found.");
            return Command::FAILURE;
        }

        if (!$scene2) {
            $io->error("Scene with id {$input->getArgument('scene2')} was not found.");
            return Command::FAILURE;
        }

        $connection = $scene1->getConnectionTo($scene2);

        if (!$connection) {
            $io->error("The to given scenes do not share a connection.");
            return Command::FAILURE;
        }

        // Commit changes
        $em->remove($connection);
        $em->flush();

        $io->success("The connections between the two given scenes was removed.");

        return Command::SUCCESS;
    }
}
