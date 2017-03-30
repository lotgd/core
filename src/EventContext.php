<?php
declare(strict_types=1);

namespace LotGD\Core;
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
     * @param array $data
     */
    public function __construct(
        string $event,
        string $matchingPattern,
        array $data
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

    /**
     * Returns a list of fields in this context
     * @return array
     */
    public function getListOfFields(): array
    {
        return array_keys($this->data);
    }

    /**
     * Returns a comma separated string with all allowed fields, for debugging reasons.
     * @return string
     */
    private function getFormattedListOfFields(): string
    {
        return substr(
            implode(", ", $this->getListOfFields()),
            0,
            -2
        );
    }

    /**
     * Sets a field and returns the changed object.
     * @param $field
     * @param $value
     * @return EventContext
     */
    public function setField($field, $value): self
    {
        if ($this->hasField($field)) {
            $data = $this->data;
            $data[$field] = $value;
        } else {
            $this->throwException($field);
        }

        return new static($this->event, $this->matchingPattern, $data);
    }

    /**
     * Sets multiple fields at once and returns the changed object.
     * @param $data array of field => value
     * @return EventContext
     */
    public function setFields($data): self
    {
        $oldData = $this->data;

        foreach ($data as $key => $value) {
            if ($this->hasField($field)) {
                $oldData[$key] = $value;
            } else {
                $this->throwException($field);
            }
        }

        return new static($this->event, $this->matchingPattern, $oldData);
    }

    /**
     * Returns the data to a field.
     * @param $field
     * @return mixed
     */
    public function getField($field)
    {
        if ($this->hasField($field)) {
            return $this->data[$field];
        } else {
            $this->throwException($field);
        }
    }

    /**
     * Returns true if the context has a specific field.
     * @param $field
     * @return bool
     */
    public function hasField($field)
    {
        if (isset($this->data[$field])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * internal use only - throws an ArgumentException a field is given that's not valid.
     * @param $field
     * @throws ArgumentException
     */
    private function throwException($field)
    {
        throw new ArgumentException(
            "{$field} is not valid in this context, only {$this->getFormattedListOfFields()} are allowed."
        );
    }
}