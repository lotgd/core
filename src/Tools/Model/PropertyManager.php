<?php
declare(strict_types=1);

namespace LotGD\Core\Tools\Model;

/**
 * Provides method and doctrine annotation for a property submodel
 */
trait PropertyManager
{
    private $propertyStorage = null;

    /**
     * Loads properties
     */
    public function loadProperties(): void
    {
        if ($this->propertyStorage !== null) {
            return;
        }

        foreach ($this->properties as $property) {
            $this->propertyStorage[$property->getName()] = $property;
        }
    }

    /**
     * Returns a property with its stored type.
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function getProperty(string $name, $default = null)
    {
        $this->loadProperties();

        if (isset($this->propertyStorage[$name])) {
            return $this->propertyStorage[$name]->getValue();
        } else {
            return $default;
        }
    }

    /**
     * Deletes a property.
     * @param string $name
     */
    public function unsetProperty(string $name): void
    {
        $this->loadProperties();

        if (isset($this->propertyStorage[$name])) {
            $property = $this->propertyStorage[$name];
            $this->properties->removeElement($property);
            unset($this->propertyStorage[$name]);
        }
    }

    /**
     * Sets a property to a given value
     * @param string $name
     * @param mixed $value
     */
    public function setProperty(string $name, $value): void
    {
        $this->loadProperties();

        if (isset($this->propertyStorage[$name])) {
            $this->propertyStorage[$name]->setValue($value);
        } else {
            $className = $this->properties->getTypeClass()->name;
            $property = new $className();
            if (method_exists($property, "setOwner")) {
                $property->setOwner($this);
            }
            $property->setName($name);
            $property->setValue($value);

            $this->propertyStorage[$name] = $property;
            $this->properties->add($property);
        }
    }
}
