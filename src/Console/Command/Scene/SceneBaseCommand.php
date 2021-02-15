<?php
declare(strict_types=1);

namespace LotGD\Core\Console\Command\Scene;

use LotGD\Core\Console\Command\BaseCommand;
use Symfony\Component\Console\Input\InputArgument;

class SceneBaseCommand extends BaseCommand
{
    protected ?string $namespace = "character";

    /**
     * @return InputArgument
     */
    protected function getSceneIdArgumentDefinition(): InputArgument
    {
        return new InputArgument(
            name: "id",
            mode: InputArgument::REQUIRED,
            description: "Scene ID",
        );
    }
}