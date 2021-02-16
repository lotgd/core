<?php
declare(strict_types=1);

namespace LotGD\Core\Console\Command\Scene;

use LotGD\Core\Models\Scene;
use LotGD\Core\Models\SceneConnectable;
use LotGD\Core\Models\SceneConnection;
use LotGD\Core\Models\SceneConnectionGroup;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Resets the viewpoint of a given character.
 */
class SceneShowCommand extends SceneBaseCommand
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName($this->namespaced("show"))
            ->setDescription("Show details about a specific scene.")
            ->setDefinition(
                new InputDefinition([
                    $this->getSceneIdArgumentDefinition(),
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
        $id = $input->getArgument("id");

        /* @var $scene Scene */
        $scene = $em->getRepository(Scene::class)->find($id);

        if ($scene === null) {
            $io->error("Scene not found.");
            return Command::FAILURE;
        }

        $io->title("About scene '{$scene->getTitle()}'");
        $io->listing([
            "ID: {$scene->getId()}",
            "Title: {$scene->getTitle()}",
            "Template: {$scene->getTemplate()?->getClass()}",
        ]);

        $io->text($scene->getDescription());

        $io->section("Connection groups");

        /** @var SceneConnectionGroup[] $connectionGroups */
        $connectionGroups = $scene->getConnectionGroups();
        $list = [];
        foreach ($connectionGroups as $connectionGroup) {
            $list[] = "{$connectionGroup->getTitle()} (id={$connectionGroup->getName()})";
        }
        $io->listing($list);

        $io->section("Connected Scenes");

        /** @var SceneConnection[] $connections */
        $connections = $scene->getConnections();
        $list = [];
        foreach ($connections as $connection) {
            # Get formatting for outgoing scene connection group name
            $outgoingSceneConnectionGroup = $connection->getOutgoingConnectionGroupName();
            if ($outgoingSceneConnectionGroup) {
                $outgoingSceneConnectionGroup = " (on $outgoingSceneConnectionGroup)";
            } else {
                $outgoingSceneConnectionGroup = "";
            }

            # Get formatting for incoming scene connection group name
            $incomingSceneConnectionGroup = $connection->getIncomingConnectionGroupName();
            if ($incomingSceneConnectionGroup) {
                $incomingSceneConnectionGroup = " (on $incomingSceneConnectionGroup)";
            } else {
                $incomingSceneConnectionGroup = " ";
            }

            # Treat outgoing and incoming connections slightly differently
            if ($connection->getOutgoingScene() === $scene) {
                $other = $connection->getIncomingScene();

                # Check if the connection is bidirectional (only out (this)->in)
                if ($connection->isDirectionality(SceneConnectable::Bidirectional)) {
                    $list[] = "this$outgoingSceneConnectionGroup <=> {$other->getTitle()}$incomingSceneConnectionGroup(id={$other->getId()})";
                } else {
                    $list[] = "this$outgoingSceneConnectionGroup => {$other->getTitle()}$incomingSceneConnectionGroup(id={$other->getId()})";
                }
            } else {
                $other = $connection->getOutgoingScene();

                # Check if the connection is bidirectional (only out->in (this))
                if ($connection->isDirectionality(SceneConnectable::Bidirectional)) {
                    $list[] = "this$incomingSceneConnectionGroup <=> {$other->getTitle()}$outgoingSceneConnectionGroup (id={$other->getId()})";
                } else {
                    $list[] = "this$incomingSceneConnectionGroup <= {$other->getTitle()}$outgoingSceneConnectionGroup (id={$other->getId()})";
                }
            }
        }

        $io->listing($list);

        return Command::SUCCESS;
    }
}
