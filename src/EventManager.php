<?php
declare(strict_types=1);

namespace LotGD\Core;

use LotGD\Core\Events\EventContext;
use LotGD\Core\Events\EventContextData;
use LotGD\Core\Exceptions\ClassNotFoundException;
use LotGD\Core\Exceptions\SubscriptionNotFoundException;
use LotGD\Core\Exceptions\WrongTypeException;
use LotGD\Core\Models\EventSubscription;

/**
 * Manages a simple publish/subscribe system based on regular expressions
 * matching event names and running a fixed.
 */
class EventManager
{
    /**
     * @param Game $g The game.
     */
    public function __construct(private Game $g) {}

    /**
     * Publish an event. Will immediately cause handleEvent() to be called on all
     * subscribed classes. This does not ensure any order in which the handlers
     * are run.
     *
     * @param string $event The name of the event to publish.
     * @param EventContextData $contextData The Data context
     * @return EventContextData The changed data.
     */
    public function publish(string $event, EventContextData $contextData): EventContextData
    {
        // For right now, implement the naive approach of iterating every entry
        // in the subscription database, checking the regular expression. We
        // will need a cache :)
        // TODO: Add an in-memory cache here. Will likely only be in the 1000s of
        // patterns, so no need to go to the remote key-value store.

        $this->g->getLogger()->debug("Publishing event {$event}.");

        $subscriptions = $this->getSubscriptions();
        foreach ($subscriptions as $s) {
            if (\preg_match($s->getPattern(), $event)) {
                $class = $s->getClass();
                $this->g->getLogger()->debug("  Handling with {$class}.");

                $eventContext = new EventContext($event, $s->getPattern(), $contextData);

                $returnedEventContext = $class::handleEvent($this->g, $eventContext);
                // Overwrite contextData - contextData might be the same if nothing has changed,
                // or might reference a completely new object the event handler changed a value.
                $contextData = $returnedEventContext->getData();
            }
        }

        return $contextData;
    }

    /**
     * Create a new event subscription, registering $class to receive the handleEvent()
     * method every time an event matching $pattern is published.
     *
     * @param string $pattern Regular expression, in PHP format, to match against
     * published event names.
     * @param string $class Fully qualified class name, which implements the
     * EventHandler interface, that will receive the handleEvent() method call when
     * events matching $pattern are published.
     * @param string $library Library this subscription belongs to.
     * @throws ClassNotFoundException if class cannot be resolved into a class.
     * @throws WrongTypeException if class does not implement the EventHandler
     * interface or the pattern is not a valid regular expression.
     */
    public function subscribe(string $pattern, string $class, string $library)
    {
        $this->g->getLogger()->debug("Subscribing event pattern={$pattern} class={$class} library={$library}");

        try {
            // Can we resolve this class?
            $klass = new \ReflectionClass($class);
        } catch (\LogicException $e) {
            // Currently we do the same thing on not found as on some other
            // exception. Maybe we should do something different.
            throw new ClassNotFoundException("Loading class ${class} failed");
        } catch (\ReflectionException $e) {
            throw new ClassNotFoundException("Could not find class ${class}");
        }

        // Check if the class implements EventHandler.
        $interface = EventHandler::class;
        if (!$klass->implementsInterface($interface)) {
            throw new WrongTypeException("Class does not implement {$interface}");
        }

        // Validate the regular expression.
        if (@\preg_match($pattern, '') === false) {
            throw new WrongTypeException("Invalid regular expression: {$pattern}");
        }

        $e = EventSubscription::create([
            'pattern' => $pattern,
            'class' => $class,
            'library' => $library,
        ]);

        $this->g->getEntityManager()->persist($e);
    }

    /**
     * Remove an event subscription, unregistering $class to receive the handleEvent()
     * method when $pattern is published.
     *
     * @param string $pattern Regular expression, in PHP format, to match against
     * published event names.
     * @param string $class Fully qualified class name.
     * @param string $library
     * @throws SubscriptionNotFoundException if the specified subscription does not exist.
     */
    public function unsubscribe(string $pattern, string $class, string $library)
    {
        $e = $this->g->getEntityManager()->getRepository(EventSubscription::class)->find([
            'pattern' => $pattern,
            'class' => $class,
            'library' => $library,
        ]);
        if (!$e) {
            throw new SubscriptionNotFoundException("Subscription not found with pattern={$pattern} class={$class} library={$library}.");
        }
        $e->delete($this->g->getEntityManager());
    }

    /**
     * Return a list of existing subscriptions.
     */
    public function getSubscriptions(): array
    {
        return $this->g->getEntityManager()->getRepository(EventSubscription::class)->findAll();
    }
}
