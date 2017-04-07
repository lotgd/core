<?php
declare(strict_types=1);

namespace LotGD\Core\Events;

use LotGD\Core\Exceptions\ArgumentException;

/**
 * Abstract EventContextDataContainer to provide a basic structure for managing contextual data of an event.
 *
 * This class must be immutable and returns always a new instance of itself for any change.
 * @package LotGD\Core\Events
 * @immutable
 */
abstract class EventContextDataContainer
{
    private $data;

    /**
     * Creates a new instance of a data container.
     *
     * Sub types can change this method to force certain parameters.
     * @param array $data
     * @return EventContextDataContainer
     */
    public static function create(array $data): self
    {
        return new static($data);
    }

    /**
     * protected constructor..
     * @see self::create
     * @param array $data
     */
    protected function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Returns true if container has a certain field.
     * @param string $field
     * @return bool
     */
    public function has(string $field): bool
    {
        return array_key_exists($field, $this->data);
    }

    /**
     * Returns the value of a field.
     * @param string $field
     * @return mixed
     */
    public function get(string $field)
    {
        if ($this->has($field)) {
            return $this->data[$field];
        } else {
            $this->throwException($field);
        }
    }

    /**
     * Sets a field to a new value and returns a new data container
     * @param string $field
     * @param $value
     * @return EventContextDataContainer
     */
    public function set(string $field, $value): self
    {
        if ($this->has($field)) {
            $data = $this->data;
            $data[$field] = $value;

            return new static($data);
        } else {
            $this->throwException($field);
        }
    }

    /**
     * Sets multiple fields at once
     * @param array $data array of $field=>$value pairs
     * @return EventContextDataContainer
     */
    public function setFields(array $data): self
    {
        $data = $this->data;

        foreach ($data as $field => $value) {
            if ($this->has($field)) {
                $data[$field] = $value;
            } else {
                $this->throwException($field);
            }
        }

        return new static($data);
    }

    /**
     * Returns a list of fields in this context
     * @return array
     */
    private function getListOfFields(): array
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