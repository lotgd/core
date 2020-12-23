<?php
declare(strict_types=1);

namespace LotGD\Core\SceneTemplates;

interface SceneTemplateInterface
{
    /**
     * Returns the event string that's attached to the navigation-to hook.
     * @return string
     */
    public static function getNavigationEvent(): string;
}
