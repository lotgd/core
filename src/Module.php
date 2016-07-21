<?php

namespace LotGD\Core;

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
     */
    public static function onRegister(Game $g);

    /**
     * Lifecycle method called when this module is uninstalled. Use this method
     * to tear down any module-specific additions, like database tables added
     * during registration.
     * @param Game $g The game.
     */
    public static function onUnregister(Game $g);
}
