<?php
declare(strict_types=1);

namespace LotGD\Core\Tools\Model;

use Doctrine\ORM\EntityManagerInterface;

use LotGD\Core\Exceptions\AttributeMissingException;
use LotGD\Core\Exceptions\UnexpectedArrayKeyException;
use LotGD\Core\Exceptions\WrongTypeException;

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
    public static function create(array $arguments)
    {
        if (isset(self::$fillable) === false) {
            throw new AttributeMissingException('self::$fillable is not defined.');
        }
        
        if (is_array(self::$fillable) === false) {
            throw new WrongTypeException('self::$fillable needs to be an array.');
        }
        
        $entity = new self();
        
        foreach (self::$fillable as $field) {
            if (isset($arguments[$field])) {
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
     * Marks the entity as permanent and saves it into the database.
     * @param EntityManagerInterface $em The Entity Manager
     */
    public function save(EntityManagerInterface $em)
    {
        $em->persist($this);
        $em->flush();
    }
}
