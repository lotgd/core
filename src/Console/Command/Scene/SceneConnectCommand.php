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
class SceneConnectCommand extends BaseCommand
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName('scene:connect')
            ->setDescription('Connects two scenes.')
            ->setDefinition(
                new InputDefinition([
                    new InputArgument(
                        "outgoing",
                        mode: InputArgument::REQUIRED,
                        description: "Outgoing scene ID",
                    ),
                    new InputArgument(
                        "incoming",
                        mode: InputArgument::REQUIRED,
                        description: "Incoming scene ID",
                    ),
                    new InputOption(
                        "outgoingGroupName",
                        shortcut: "o",
                        mode: InputOption::VALUE_OPTIONAL,
                        description: "A valid, user-assignable scene template. Check sceneTemplate:list to get all available scenes.",
                        default: null,
                    ),
                    new InputOption(
                        "incomingGroupName",
                        shortcut: "i",
                        mode: InputOption::VALUE_OPTIONAL,
                        description: "A valid, user-assignable scene template. Check sceneTemplate:list to get all available scenes.",
                        default: null,
                    ),
                    new InputOption(
                        "directionality",
                        shortcut: "d",
                        mode: InputOption::VALUE_OPTIONAL,
                        description: "0 for bidirectional, 1 for unidirectional (outgoing->incoming)",
                        default: 0,
                    )
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

        /** @var ?Scene $outgoingScene */
        $outgoingScene = $sceneRepository->find($input->getArgument("outgoing"));
        /** @var ?Scene $incomingScene */
        $incomingScene = $sceneRepository->find($input->getArgument("incoming"));

        // Check of scenes actually exist
        if (!$outgoingScene) {
            $io->error("The outgoing scene was not found.");
            return Command::FAILURE;
        }

        if (!$incomingScene) {
            $io->error("The incoming scene was not found");
            return Command::FAILURE;
        }

        // Get group names
        $outgoingGroupName = $input->getOption("outgoingGroupName");
        $incomingGroupName = $input->getOption("incomingGroupName");

        /** @var SceneConnectable $outgoing */
        $outgoing = null;
        /** @var SceneConnectable $outgoing */
        $incoming = null;

        // Determine the outgoing Connectable
        if ($outgoingGroupName) {
            if (!$outgoingScene->hasConnectionGroup($outgoingGroupName)) {
                $io->error("The outgoing scene does not have a connection group with the id {$outgoingGroupName}");
                return Command::FAILURE;
            } else {
                $outgoing = $outgoingScene->getConnectionGroup($outgoingGroupName);
            }
        } else {
            $outgoing = $outgoingScene;
        }

        // Determine the incoming Connectable
        if ($incomingGroupName) {
            if (!$incomingScene->hasConnectionGroup($incomingGroupName)) {
                $io->error("The incoming scene does not have a connection group with the id {$incomingGroupName}");
                return Command::FAILURE;
            } else {
                $incoming = $incomingScene->getConnectionGroup($incomingGroupName);
            }
        } else {
            $incoming = $incomingScene;
        }

        // Get directionality
        $directionality = intval($input->getOption("directionality"));

        if ($directionality < 0 or $directionality > 1) {
            $io->warning("Directionality was not either 0 or 1. It was forced to 0.");
            $directionality = 0;
        }

        // Connect the connectables
        try {
            $outgoing->connect($incoming, $directionality);
        } catch (ArgumentException $e) {
            $io->error("Scenes were not connected. Reason: {$e}.");
            return Command::FAILURE;
        }

        // Commit changes
        $em->flush();

        $io->success("The two scenes were successfully connected.");

        return Command::SUCCESS;
    }
}
