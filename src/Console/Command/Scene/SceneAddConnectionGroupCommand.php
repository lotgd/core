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
class SceneAddConnectionGroupCommand extends BaseCommand
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName('scene:addConnectionGroup')
            ->setDescription('Add a connection group to an existing scene.')
            ->setDefinition(
                new InputDefinition([
                    new InputArgument("id", InputArgument::REQUIRED, "ID of the scene"),
                    new InputArgument("groupName", InputArgument::REQUIRED, "Internal id of the group."),
                    new InputArgument("groupTitle", InputArgument::REQUIRED, "Title of the group (what the character can see"),
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
        $groupTitle = $input->getArgument("groupTitle");

        // Search scene
        /** @var ?Scene $scene */
        $scene = $em->getRepository(Scene::class)->find($sceneId);

        if (!$scene) {
            $io->error("The requested scene with the ID {$sceneId} was not found");
            return Command::FAILURE;
        }

        // Make scene connection group
        $connectionGroup = new SceneConnectionGroup($groupName, $groupTitle);

        // Add
        try {
            $scene->addConnectionGroup($connectionGroup);
        } catch(ArgumentException $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }

        $em->flush();

        $io->success("Group successfully added");

        return Command::SUCCESS;
    }
}
