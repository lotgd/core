<?php

namespace LotGD\Core\Tests\FakeModule;

use LotGD\Core\Game;
use LotGD\Core\Module as ModuleInterface;
use LotGD\Core\Models\Module as ModuleModel;

class Module implements ModuleInterface {
    public static function handleEvent(Game $g, string $event, array &$context) {
        $context['foo'] = 'baz';
        return $context;
    }
    public static function onRegister(Game $g, ModuleModel $module) {}
    public static function onUnregister(Game $g, ModuleModel $module) {}
}
