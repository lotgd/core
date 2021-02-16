<?php
declare(strict_types=1);

namespace LotGD\Core\Console\Command\Scene;

use LotGD\Core\Console\Command\BaseCommand;
use LotGD\Core\Models\Scene;
use LotGD\Core\SceneTemplates\SceneTemplateInterface;
use Symfony\Component\Console\Input\InputArgument;

class SceneBaseCommand extends BaseCommand
{
    protected ?string $namespace = "scene";

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

    /**
     * @param string $id
     * @return Scene|null
     */
    protected function getScene(string $id): ?Scene
    {
        /** @var Scene|null $scene */
        $scene = $this->game->getEntityManager()->getRepository(Scene::class)->find($id);
        return $scene;
    }

    /**
     * @param Scene $scene
     * @return string
     */
    protected function getSceneTemplatePath(Scene $scene)
    {
        $sceneTemplate = "no-template";
        if ($scene->getTemplate()) {
            /** @var SceneTemplateInterface $templateClass */
            $templateClass = $scene->getTemplate()->getClass();
            $sceneTemplate = $templateClass::getNavigationEvent();
        }

        return $sceneTemplate;
    }
}