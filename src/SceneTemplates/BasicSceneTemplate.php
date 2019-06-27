<?php
declare(strict_types=1);

namespace LotGD\Core\SceneTemplates;

/**
 * Class BasicSceneTemplate.
 *
 * Offers a basic scene template. All scenes with no template use this class internally.
 */
class BasicSceneTemplate implements SceneTemplateInterface
{
    /**
     * {@inheritDoc}
     * @return string
     */
    public static function getNavigationEvent(): string
    {
        return "no-template";
    }
}
