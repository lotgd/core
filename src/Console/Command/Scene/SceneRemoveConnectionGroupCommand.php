<?php
declare(strict_types=1);

namespace LotGD\Core\Console\Command\Scene;

use LotGD\Core\Models\Scene;
use LotGD\Core\Models\SceneConnection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Resets the viewpoint of a given character.
 */
class SceneRemoveConnectionGroupCommand extends SceneBaseCommand
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName($this->namespaced("removeConnectionGroup"))
            ->setDescription("Removes a connection group from an existing scene.")
            ->setDefinition(
                new InputDefinition([
                    $this->getSceneIdArgumentDefinition(),
                    new InputArgument("groupName", InputArgument::REQUIRED, "Internal id of the group."),
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

        $sceneId = $input->getArgument("id");
        $groupName = $input->getArgument("groupName");

        // Search scene
        /** @var ?Scene $scene */
        $scene = $em->getRepository(Scene::class)->find($sceneId);

        if (!$scene) {
            $io->error("The scene with the ID {$sceneId} was not found.");
            return Command::FAILURE;
        }

        if (!$scene->hasConnectionGroup($groupName)) {
            $io->error("The scene {$sceneId} does not have a connection group with the name {$groupName}");
            return Command::FAILURE;
        }

        $connectionGroup = $scene->getConnectionGroup($groupName);

        # Mark for removal
        $em->remove($connectionGroup);

        # Update outgoing connections if they refer to the deleted connectionGroup
        $connections = $scene->getConnections();
        /** @var SceneConnection $connection */
        foreach ($connections as $connection) {
            if ($connection->getIncomingScene() === $scene and $connection->getIncomingConnectionGroupName() === $groupName) {
                $connection->setIncomingConnectionGroupName(null);
                $io->comment("Updated connection to {$connection->getOutgoingScene()->getTitle()}");
            }

            if ($connection->getOutgoingScene() === $scene and $connection->getOutgoingConnectionGroupName() === $groupName) {
                $connection->setOutgoingConnectionGroupName(null);
                $io->comment("Updated connection to {$connection->getIncomingScene()->getTitle()}");
            }
        }

        try {
            $em->flush();
        } catch (\Exception $e) {
            $io->error("An unknown error occurred: {$e->getMessage()}");
            return Command::FAILURE;
        }

        $io->success("{$connectionGroup} was successfully removed");
        $logger->info("Removed {$connectionGroup} from {$scene}.");

        return Command::SUCCESS;
    }
}
