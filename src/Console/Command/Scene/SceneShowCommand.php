<?php
declare(strict_types=1);

namespace LotGD\Core\Console\Command\Scene;

use LotGD\Core\Console\Command\BaseCommand;
use LotGD\Core\Models\Character;
use LotGD\Core\Models\Scene;
use LotGD\Core\Models\SceneConnectable;
use LotGD\Core\Models\SceneConnection;
use LotGD\Core\Models\SceneConnectionGroup;
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
class SceneShowCommand extends BaseCommand
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName('scene:show')
            ->setDescription('Show details about a specific scene.')
            ->setDefinition(
                new InputDefinition([
                    new InputArgument("id", InputArgument::REQUIRED, "ID of the scene"),
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
            "Template: {$scene->getTemplate()->getClass()}",
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
            if ($connection->getOutgoingScene() === $scene) {
                $other = $connection->getIncomingScene();

                if ($connection->isDirectionality(SceneConnectable::Bidirectional)) {
                    $list[] = "this <=> {$other->getTitle()} (id={$other->getId()})";
                } else {
                    $list[] = "this  => {$other->getTitle()} (id={$other->getId()})";
                }
            } else {
                $other = $connection->getOutgoingScene();

                if ($connection->isDirectionality(SceneConnectable::Bidirectional)) {
                    $list[] = "this <=> {$other->getTitle()} (id={$other->getId()})";
                } else {
                    $list[] = "this <= {$other->getTitle()} (id={$other->getId()})";
                }
            }
        }
        $io->listing($list);

        return Command::SUCCESS;
    }
}
