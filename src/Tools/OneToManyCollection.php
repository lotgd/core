<?php

namespace LotGD\Core\Tools;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;

use LotGD\Core\Exceptions\ClassNotFoundException;
use LotGD\Core\Exceptions\KeyNotFoundException;
use LotGD\Core\Exceptions\NotImplementedException;
use LotGD\Core\Exceptions\WrongTypeException;

class OneToManyCollection implements Collection
{
    /** @var string */
    private $typeClass;
    /** @var EntityManagerInterface */
    private $entityManager = null;
    /** @var array */
    private $collection;
    /** @var int */
    private $numberOfRows;
    
    /**
     * Constructor
     * @param EntityManagerInterface $entityManager
     * @param string $typeClass
     * @throws ClassNotFoundException
     */
    public function __construct(EntityManagerInterface $entityManager, string $typeClass)
    {
        if(class_exists($typeClass) === false) {
            throw new ClassNotFoundException(sprintf("The class %s has not been found.", $typeClass));
        }
        
        $this->entityManager = $entityManager;
        $this->typeClass = $typeClass;
        
        // Load eagerly everything.
        $this->collection = $this->entityManager->getRepository($this->typeClass)->findAll();
    }
    
    /**
     * returns the class this collection consists of.
     * @return string
     */
    public function getTypeClass(): ClassMetadata
    {
        return $this->entityManager->getClassMetadata($this->typeClass);
    }
    
    /**
     * Counts the number of settings stored
     * @return int
     */
    public function count(): int
    {
        // If the collection has not been loaded yet, we should query the db directly
        if ($this->collection === null and $this->numberOfRows === null) {
            $this->numberOfRows = (int)$this->entityManager->createQueryBuilder()
                ->from($this->typeClass, "p")
                ->select("COUNT(p.propertyName)")
                ->getQuery()
                ->getSingleScalarResult();
        }
        
        if ($this->collection === null) {
            return $this->numberOfRows;
        }
        else {
            return count($this->collection);
        }
    }

    /**
     * Checks if the element matches the typeClass of this collection
     * @param mixed $element
     * @throws WrongTypeException
     */
    private function checkElementType($element)
    {
        if ($element instanceof $this->typeClass === false) {
            throw new WrongTypeException(sprintf('$element needs to be of type %s', $this->typeClass));
        }
    }
    
    /**
     * Adds an element to the collection
     * @param mixed $element
     */
    public function add($element)
    {
        $this->checkElementType($element);
        
        if ($this->collection === null) {
            $this->collection = [];
        }
        
        $this->collection[] = $element;
        $this->entityManager->persist($element);
    }
    
    /**
     * Clears the collection
     */
    public function clear()
    {
        $this->entityManager->createQueryBuilder()
            ->delete($this->typeClass, "p")
            ->getQuery()
            ->execute();
        $this->collection = [];
    }
    
    /**
     * Returns true if a item is contained in this collection
     * @param type $element
     * @return bool
     */
    public function contains($element): bool
    {
        $this->checkElementType($element);
        return in_array($element, $this->collection);
    }
    
    /**
     * Checks if this the collection is empty
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->collection);
    }
    
    /**
     * Removes an element from this collection by the given key
     * @param int|string $key
     */
    public function remove($key)
    {
        if (isset($this->collection[$key])) {
            $element = $this->collection[$key];
            $this->removeElement($element);
        }
    }
    
    /**
     * Removes an element from this collection
     * @param type $element
     */
    public function removeElement($element)
    {
        if ($this->contains($element)) {
            $key = array_search($element, $this->collection);
            $this->entityManager->remove($element);
            unset($this->collection[$key]);
        }
    }
    
    /**
     * Checks if this collection contains a certain key
     * @param int|string $key
     */
    public function containsKey($key)
    {
        return isset($this->collection[$key]);
    }
    
    /**
     * Returns the element saved at the given position
     * @param int|string $key
     * @return type
     * @throws KeyNotFoundException
     */
    public function get($key)
    {
        if (isset($this->collection[$key])) {
            return $this->collection[$key];
        }
        else {
            throw new KeyNotFoundException(sprintf("The key %s has not been found within the collection", $key));
        }
    }
    
    /**
     * Returns all collection keys
     * @return array
     */
    public function getKeys(): array
    {
        return array_keys($this->collection);
    }
    
    /**
     * Returns all collection values
     * @return array
     */
    public function getValues(): array
    {
        return array_values($this->collection);
    }
    
    /**
     * Sets the element at position $key to $value.
     * @param int|string $key
     * @param mixed $value
     */
    public function set($key, $element)
    {
        $this->checkElementType($element);
        $this->collection[$key] = $element;
    }
    
    /**
     * Returns an array representation of this collection
     * @return array
     */
    public function toArray(): array
    {
        return $this->collection;
    }
    
    public function first()
    {
        return first($this->collection);
    }
    
    public function last()
    {
        return last($this->collection);
    }
    
    public function key()
    {
        return key($this->collection);
    }
    
    public function next()
    {
        return next($this->collection);
    }
    
    public function current()
    {
        return current($this->collection);
    }
    
    public function exists(\Closure $p): bool
    {
        throw new NotImplementedException();
    }
    
    public function filter(\Closure $p)
    {
        throw new NotImplementedException();
    }
    
    public function forAll(\Closure $p)
    {
        throw new NotImplementedException();
    }
    
    public function map(\Closure $p)
    {
        throw new NotImplementedException();
    }
    
    public function partition(\Closure $p)
    {
        throw new NotImplementedException();
    }
    
    /**
     * Returns the index of a specific element
     * @param mixed $element
     * @return int|string
     */
    public function indexOf($element)
    {
        $this->checkElementType($element);
        return array_search($element, $this->collection);
    }
    
    public function slice($offset, $length = null)
    {
        throw new NotImplementedException();
    }
    
    /**
     * Gets a Iterator over this collection
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->collection);
    }
    
    public function offsetGet($key) {
        return $this->get($key);
    }
    
    public function offsetSet($key, $element) {
        $this->set($key, $element);
    }
    
    public function offsetUnset($key) {
        $this->remove($key);
    }
    
    public function offsetExists($key) {
        return isset($this->collection[$key]);
    }
}