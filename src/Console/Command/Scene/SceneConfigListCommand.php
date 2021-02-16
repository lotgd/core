<?php
declare(strict_types=1);

namespace LotGD\Core\Console\Command\Scene;

use LotGD\Core\Events\EventContextData;
use LotGD\Core\SceneTemplates\SceneTemplateInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class SceneConfigListCommand extends SceneBaseCommand
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName($this->namespaced("config:list"))
            ->setDescription('List available settings for a scene')
            ->setDefinition([
                $this->getSceneIdArgumentDefinition(),
            ])
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $scene = $this->getScene($input->getArgument("id"));

        if (!$scene) {
            $io->error("Scene was not found.");
            return Command::FAILURE;
        }

        $sceneTemplate = $this->getSceneTemplatePath($scene);

        // Create hook
        $context = EventContextData::create([
            "scene" => $scene,
            "io" => $io,
            "settings" => [],
        ]);
        $newContext = $this->game->getEventManager()->publish(
            event: "h/lotgd/core/cli/scene-config-list/$sceneTemplate",
            contextData: $context
        );
        $settings = $newContext->get("settings");

        $io->title("Scene ".$scene->getTitle());

        if (count($settings) === 0) {
            $io->note("There are no scene settings available.");
        } else {
            $io->table(["setting", "value", "description"], $settings);
        }

        return Command::SUCCESS;
    }
}