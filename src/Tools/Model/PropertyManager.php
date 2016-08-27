<?php
declare(strict_types=1);

namespace LotGD\Core\Tools\Model;

/**
 * Provides method and doctrine annotation for a property submodel
 */
trait PropertyManager
{
    private $propertyStorage = null;

    public function loadProperties()
    {
        if ($this->propertyStorage !== null) {
            return;
        }

        foreach ($this->properties as $property) {
            $this->propertyStorage[$property->getName()] = $property;
        }
    }

    public function getProperty(string $name, $default = null)
    {
        $this->loadProperties();

        if (isset($this->propertyStorage[$name])) {
            return $this->propertyStorage[$name]->getValue();
        } else {
            return $default;
        }
    }

    public function setProperty(string $name, $value)
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
