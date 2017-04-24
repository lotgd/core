<?php

namespace LotGD\Core\Tests\DefectiveModule;

use LotGD\Core\Exceptions\CoreException;
use LotGD\Core\Game;
use LotGD\Core\Events\EventContext;
use LotGD\Core\Module as ModuleInterface;
use LotGD\Core\Models\Module as ModuleModel;

class DefectiveModuleException extends CoreException {}

class Module implements ModuleInterface {
    public static function handleEvent(Game $g, EventContext $context): EventContext
    {
        return $context;
    }

    public static function onRegister(Game $g, ModuleModel $module)
    {
        throw new DefectiveModuleException("Exception");
    }

    public static function onUnregister(Game $g, ModuleModel $module)
    {

    }
}
