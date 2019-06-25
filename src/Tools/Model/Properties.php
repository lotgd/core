<?php
declare(strict_types=1);

namespace LotGD\Core\Tools\Model;

/**
 * Provides method and doctrine annotation for a property submodel.
 */
trait Properties
{
    /** @Id @Column(type="string", length=255) */
    private $propertyName = "";
    /** @Column(type="text") */
    private $propertyValue = null;
    
    /**
     * Returns the name of the property.
     * @return string
     */
    public function getName(): string
    {
        return $this->propertyName;
    }
    
    /**
     * Sets the name of the property.
     * @param string $name
     * @throws ArgumentEmptyException If parameter $name is empty
     */
    public function setName(string $name)
    {
        if ($name === "") {
            throw new ArgumentEmptyException('The argument $name must not be empty.');
        }
        
        $this->propertyName = $name;
    }
    
    /**
     * Returns the stored property.
     * @return mixed
     */
    public function getValue()
    {
        return \unserialize($this->propertyValue);
    }
    
    /**
     * Sets the stored property.
     * @param mixed $value
     */
    public function setValue($value)
    {
        $this->propertyValue = \serialize($value);
    }
}
