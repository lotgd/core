<?php
declare(strict_types=1);


namespace LotGD\Core\Tests\SceneTemplates;


use LotGD\Core\SceneTemplates\BasicSceneTemplate;

class ParameterTestSceneTemplate extends BasicSceneTemplate
{
    public static function getNavigationEvent(): string
    {
        return "tests/parameter";
    }
}