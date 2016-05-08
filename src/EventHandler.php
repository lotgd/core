<?php

namespace LotGD\Core;

interface EventHandler
{
    /**
     * Called when an event is published that is handled by this class.
     *
     * @param string $event Name of this event.
     * @param array $context Arbitrary dictionary representing context around this event.
     * @return array|null Return an array if you want to make changes to the $context before
     * the next handler is called. Otherwise, return null.
     */
    public static function handleEvent(string $event, array $context): mixed;
}
