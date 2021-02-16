<?php
declare(strict_types=1);

namespace LotGD\Core\Console\Command\Scene;

use LotGD\Core\Events\EventContextData;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class SceneConfigSetCommand extends SceneBaseCommand
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName($this->namespaced("config:set"))
            ->setDescription('Change a scene setting')
            ->setDefinition([
                $this->getSceneIdArgumentDefinition(),
                new InputArgument(
                    "setting",
                    mode: InputArgument::REQUIRED,
                    description: "Name of setting, see {$this->namespaced('config:list')}.",
                ),
                new InputArgument(
                    "value",
                    InputArgument::REQUIRED,
                    description: "New value for the given setting.",
                ),
            ])
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logger = $this->getCliLogger();
        $io = new SymfonyStyle($input, $output);
        $scene = $this->getScene($input->getArgument("id"));

        if (!$scene) {
            $io->error("Scene was not found.");
            return Command::FAILURE;
        }

        $sceneTemplate = $this->getSceneTemplatePath($scene);

        $io->title("Scene {$scene->getTitle()}");

        // Create hook
        $context = EventContextData::create([
            "scene" => $scene,
            "io" => $io,
            "setting" => $input->getArgument("setting"),
            "value" => $input->getArgument("value"),
            "return" => Command::FAILURE,
            "reason" => "Setting does not exist.",
        ]);
        $newContext = $this->game->getEventManager()->publish(
            event: "h/lotgd/core/cli/character-config-set/{$sceneTemplate}",
            contextData: $context
        );
        if ($newContext->get("return") != Command::SUCCESS) {
            $io->error($newContext->get("reason"));
            return Command::FAILURE;
        }

        $this->game->getEntityManager()->flush();

        return Command::SUCCESS;
    }
}