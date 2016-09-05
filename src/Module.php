<?php

namespace LotGD\Core;

use LotGD\Core\Models\Module as ModuleModel;

/**
 * Classes which provide module functionality should implement this interface.
 */
interface Module extends EventHandler
{
    /**
     * Called when an event is published that is handled by this class.
     *
     * @param Game $g The game.
     * @param string $event Name of this event.
     * @param array $context Arbitrary dictionary representing context around this event.
     * @return array|null Return an array if you want to make changes to the $context before
     * the next handler is called. Otherwise, return null. Any changes made will be propogated
     * to the event publisher as well.
     */
    public static function handleEvent(Game $g, string $event, array &$context);

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
