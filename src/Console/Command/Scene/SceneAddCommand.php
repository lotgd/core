<?php
declare(strict_types=1);

namespace LotGD\Core\Console\Command\Scene;

use Exception;
use LotGD\Core\Models\Scene;
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
class SceneAddCommand extends SceneBaseCommand
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName($this->namespaced("add"))
            ->setDescription("Add a scene.")
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
        $logger = $this->getCliLogger();

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

        $scene = new Scene(
            title: $title,
            description: $description,
            template: $template
        );

        try {
            $em->persist($scene);

            // Commit changes
            $em->flush();
        } catch (Exception $e) {
            $io->error("Persisting of the scene was not possible. Reason: {$e->getMessage()}.");
            return Command::FAILURE;
        }

        $io->success("Scene was successfully created. ID: {$scene->getId()}.");
        $logger->info("{$scene} was created.");

        return Command::SUCCESS;
    }
}
