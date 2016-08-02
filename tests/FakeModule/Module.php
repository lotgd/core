<?php

namespace LotGD\Core\Tests\FakeModule;

use LotGD\Core\Game;
use LotGD\Core\Module as ModuleBase;

class Module implements ModuleBase {
    public static function handleEvent(Game $g, string $event, array &$context) {
        $context['foo'] = 'baz';
        return $context;
    }
    public static function onRegister(Game $g) {}
    public static function onUnregister(Game $g) {}
}
