<?php

namespace LotGD\Core\Tools\Model;

use Doctrine\ORM\EntityManagerInterface;

use LotGD\Core\Exceptions\{
    AttributeMissingException,
    WrongTypeException
};

/**
 * Provides methods for creating new instances.
 */
trait Creator {
    /**
     * Creates and returns an entity instance and fills values
     * @param array $arguments The values the instance should get
     * @return \self The created Entity
     * @throws AttributeMissingException
     * @throws WrongTypeException
     */
    public static function create(array $arguments) {
        if(isset(self::$fillable) === false) {
            throw new AttributeMissingException('self::$fillable is not defined.');
        }
        
        if(is_array(self::$fillable) === false) {
            throw new WrongTypeException('self::$fillable needs to be an array.');
        }
        
        $entity = new self();
        
        foreach(self::$fillable as $field) {
            if(isset($arguments[$field])) {
                $methodname = "set".$field;
                $value = $arguments[$field];
                
                $entity->$methodname($value);
            }
        }
        
        return $entity;
    }
    
    /**
     * Marks the entity as permanent and saves it into the database.
     * @param EntityManagerInterface $em The Entity Manager
     */
    public function save(EntityManagerInterface $em) {
        $em->persist($this);
        $em->flush();
    }
}
