<?php
declare(strict_types=1);

namespace LotGD\Core;
use LotGD\Core\Events\EventContext;

interface EventHandler
{
    /**
     * Called when an event is published that is handled by this class.
     *
     * @param Game $g The game.
     * @param EventContext $context Arbitrary dictionary representing context around this event.
     * @return EventContext Return an array if you want to make changes to the $context before
     * the next handler is called. Otherwise, return null. Any changes made will be propogated
     * to the event publisher as well.
     */
    public static function handleEvent(Game $g, EventContext $context): EventContext;
}
