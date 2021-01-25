<?php
declare(strict_types=1);

namespace LotGD\Core\Console\Command\Scene;

use LotGD\Core\Console\Command\BaseCommand;
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
class SceneRemoveConnectionGroupCommand extends BaseCommand
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName('scene:removeConnectionGroup')
            ->setDescription('Removes a connection group from an existing scene.')
            ->setDefinition(
                new InputDefinition([
                    new InputArgument("id", InputArgument::REQUIRED, "ID of the scene"),
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

        $io = new SymfonyStyle($input, $output);

        $sceneId = $input->getArgument("id");
        $groupName = $input->getArgument("groupName");

        // Search scene
        /** @var ?Scene $scene */
        $scene = $em->getRepository(Scene::class)->find($sceneId);

        if (!$scene) {
            $io->error("The requested scene with the ID {$sceneId} was not found");
            return Command::FAILURE;
        }

        if (!$scene->hasConnectionGroup($groupName)) {
            $io->error("The scene {$sceneId} oes not have a connection group with the name {$groupName}");
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

        $em->flush();

        $io->success("Group successfully added");

        return Command::SUCCESS;
    }
}
