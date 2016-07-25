<?php

namespace LotGD\Core\Tests\FakeModule;

use LotGD\Core\Module;

class FakeModule extends Module {
    public static function onRegister(Game $g) {}
    public static function onUnregister(Game $g) {}
}
