<?php
declare(strict_types=1);

namespace LotGD\Core\Models;

use Doctrine\ORM\EntityManagerInterface;
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

    /** @var array */
    private static $fillable = [
        "pattern",
        "class",
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

    public function setClass(string $class)
    {
        $this->class = $class;
    }
}
