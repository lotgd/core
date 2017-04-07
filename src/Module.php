<?php

namespace LotGD\Core;

use LotGD\Core\Models\Module as ModuleModel;

/**
 * Classes which provide module functionality should implement this interface.
 */
interface Module extends EventHandler
{
    /**
     * Lifecycle method called when this module is initially installed. Use
     * this method to perform one-time setup operations like adding tables
     * to the database.
     * @param Game $g The game.
     * @param ModuleModel $module The database model for this module.
     */
    public static function onRegister(Game $g, ModuleModel $module);

    /**
     * Lifecycle method called when this module is uninstalled. Use this method
     * to tear down any module-specific additions, like database tables added
     * during registration.
     * @param Game $g The game.
     * @param ModuleModel $module The database model for this module.
     */
    public static function onUnregister(Game $g, ModuleModel $module);
}
