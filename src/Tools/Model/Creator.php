<?php
declare(strict_types=1);

namespace LotGD\Core\Tools\Model;

use Doctrine\ORM\EntityManagerInterface;

use LotGD\Core\Exceptions\AttributeMissingException;
use LotGD\Core\Exceptions\UnexpectedArrayKeyException;
use LotGD\Core\Exceptions\WrongTypeException;
use LotGD\Core\Models\CreateableInterface;

/**
 * Provides methods for creating new entities
 */
trait Creator
{
    /**
     * Creates and returns an entity instance and fills values
     * @param array $arguments The values the instance should get
     * @return \self The created Entity
     * @throws AttributeMissingException
     * @throws WrongTypeException
     */
    public static function create(array $arguments): CreateableInterface
    {
        if (isset(self::$fillable) === false) {
            throw new AttributeMissingException('self::$fillable is not defined.');
        }
        
        if (is_array(self::$fillable) === false) {
            throw new WrongTypeException('self::$fillable needs to be an array.');
        }
        
        $entity = new self();
        
        foreach (self::$fillable as $field) {
            if (array_key_exists($field, $arguments)) {
                $methodname = "set".$field;
                $value = $arguments[$field];
                
                $entity->$methodname($value);
                unset($arguments[$field]);
            }
        }
        
        if (count($arguments) > 0) {
            throw new UnexpectedArrayKeyException('self::$fillable does allow the properties "'.implode(", ", array_keys($arguments)).'" to be set.');
        }
        
        return $entity;
    }
    
    /**
     * Static, protected save function to call from trait-overwriting methods.
     * @param \LotGD\Core\Tools\Model\CreateableInterface $object
     * @param EntityManagerInterface $em
     */
    protected static function _save(CreateableInterface $object, EntityManagerInterface $em)
    {
        $em->persist($object);
        $em->flush();
    }
    
    /**
     * Marks the entity as permanent and saves it into the database.
     * @param EntityManagerInterface $em The Entity Manager
     */
    public function save(EntityManagerInterface $em)
    {
        self::_save($this, $em);
    }
}
