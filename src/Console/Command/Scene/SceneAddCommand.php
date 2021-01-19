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
class SceneAddCommand extends BaseCommand
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName('scene:add')
            ->setDescription('Add a scene.')
            ->setDefinition(
                new InputDefinition([
                    new InputArgument(
                        "title",
                        mode: InputArgument::REQUIRED,
                        description: "Scene title",
                    ),
                    new InputArgument(
                        "description",
                        mode: InputArgument::OPTIONAL,
                        description: "Scene description",
                        default: "",
                    ),
                    new InputOption(
                        "template",
                        mode: InputOption::VALUE_OPTIONAL,
                        description: "A valid, user-assignable scene template. Check sceneTemplate:list to get all available scenes.",
                        default: null,
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

        $io = new SymfonyStyle($input, $output);

        $title = $input->getArgument("title");
        $description = $input->getArgument("description");
        $templateClass = $input->getOption("template");

        /* @var $template SceneTemplate */
        if ($templateClass) {
            $template = $em->getRepository(SceneTemplate::class)->find($templateClass);

            if (!$template) {
                $io->warning("Template '$template' has not been found. Set to NULL instead.");
            }
        } else {
            $template = $templateClass;
        }

        $scene = Scene::create([
            "title" => $title,
            "description" => $description,
            "template" => $template,
        ]);

        $em->persist($scene);
        $em->flush();

        return Command::SUCCESS;
    }
}
