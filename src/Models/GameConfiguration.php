<?php
declare(strict_types=1);

namespace LotGD\Core\Models;

use Doctrine\ORM\EntityManagerInterface;

use LotGD\Core\Models\GameConfigurationElement;
use LotGD\Core\Tools\OneToManyCollection;
use LotGD\Core\Tools\Model\PropertyManager;

/**
 * Provides an interface to access properties
 */
class GameConfiguration
{
    use PropertyManager;
    
    /** @var ArrayCollection */
    private $properties;
    
    /**
     * Constructor.
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->properties = new OneToManyCollection($entityManager, GameConfigurationElement::class);
    }
    
    /**
     * Returns a configuration value or the default one if the configuration name has not been set yet.
     * @param string $configurationName
     * @param mixed $configurationDefault
     * @return mixed
     */
    public function get(string $configurationName, $configurationDefault)
    {
        return $this->getProperty($configurationName, $configurationDefault);
    }
    
    /**
     * Sets and overwrites a configuration value saved by the name
     * @param string $configurationName
     * @param type $configurationValue
     */
    public function set(string $configurationName, $configurationValue)
    {
        $this->setProperty($configurationName, $configurationValue);
    }
}
