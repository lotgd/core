<?php
declare(strict_types=1);

namespace LotGD\Core\Events;
use LotGD\Core\Exceptions\ArgumentException;


/**
 * Class EventContext
 * @package LotGD\Core
 * @immutable
 */
class EventContext
{
    private $matchingPattern;
    private $event;
    private $data;

    /**
     * EventContext constructor.
     * @param string $event The published event
     * @param string $matchingPattern The matching pattern
     * @param EventContextDataContainer $data
     */
    public function __construct(
        string $event,
        string $matchingPattern,
        EventContextDataContainer $data
    ) {
        $this->event = $event;
        $this->matchingPattern = $matchingPattern;
        $this->data = $data;
    }

    /**
     * Returns the event of this context.
     * @return string
     */
    public function getEvent(): string
    {
        return $this->event;
    }

    /**
     * Returns the matching pattern of this context.
     * @return string
     */
    public function getMatchingPattern(): string
    {
        return $this->matchingPattern;
    }

    public function hasDataType($type): bool
    {
        return $this->data instanceof $type ? true : false;
    }

    /**
     * Returns the immutable data container.
     * @return EventContextDataContainer
     */
    public function getData(): EventContextDataContainer
    {
        return $this->data;
    }

    /**
     * Returns a data field
     * @param $field
     * @return mixed
     */
    public function getDataField($field)
    {
        return $this->data->get($field);
    }

    /**
     * Sets a data field
     * @param $field
     * @param $value
     */
    public function setDataField($field, $value)
    {
        $this->data = $this->data->set($field, $value);
    }

    /**
     * Sets multiple data fields at once.
     * @param $data
     */
    public function setDataFields($data)
    {
        $this->data = $this->data->setFields($data);
    }

    /**
     * Checks if given original data is the same as currently held within this context.
     * @param EventContextDataContainer $originalData
     * @return bool
     */
    public function hasDataChanged(EventContextDataContainer $originalData): bool
    {
        return $this->data === $originalData ? false : true;
    }
}