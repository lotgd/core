<?php
declare(strict_types=1);

namespace LotGD\Core\Models;

use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;

use LotGD\Core\Tools\Model\Creator;
use LotGD\Core\Tools\Model\Deletor;

/**
 * An event name to class binding that represents that class listening for that
 * event.
 * @Entity
 * @Table(name="event_subscriptions")
 */
class EventSubscription implements CreateableInterface
{
    use Creator;
    use Deletor;

    /** @Id @Column(type="string"); */
    private $pattern;

    /** @Id @Column(type="string"); */
    private $class;

    /** @Id @Column(type="string"); */
    private $library;

    /** @var array */
    private static $fillable = [
        "pattern",
        "class",
        "library",
    ];

    /**
     * Returns the pattern used to match against event names for this subscription.
     * Format is PHP regular expressions.
     * @return string
     */
    public function getPattern(): string
    {
        return $this->pattern;
    }

    /**
     * Set the pattern used to match against event names.
     * Format is PHP regular expressions.
     * @param string $pattern
     */
    public function setPattern(string $pattern)
    {
        $this->pattern = $pattern;
    }

    /**
     * Returns the class name subscribed to this event.
     * @return string
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * Sets the class name subscribed to this event.
     * @param string $class
     */
    public function setClass(string $class)
    {
        $this->class = $class;
    }

    /**
     * Returns the library that this subscription is part of, in vendor/module format.
     * @return string
     */
    public function getLibrary(): string
    {
        return $this->library;
    }

    /**
     * Sets the library that this subscription is part of, in vendor/module format.
     * @param string $library
     */
    public function setLibrary(string $library)
    {
        $this->library = $library;
    }
}
