<?php

namespace LotGD\Core\Tools;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;

use LotGD\Core\Exceptions\ClassNotFoundException;
use LotGD\Core\Exceptions\KeyNotFoundException;
use LotGD\Core\Exceptions\NotImplementedException;
use LotGD\Core\Exceptions\WrongTypeException;

/**
 * A one-to-many relation between two entities.
 */
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
     * Constructor for a one to many colelction of type $typeClass.
     * @param EntityManagerInterface $entityManager
     * @param string $typeClass
     * @throws ClassNotFoundException
     */
    public function __construct(EntityManagerInterface $entityManager, string $typeClass)
    {
        if (class_exists($typeClass) === false) {
            throw new ClassNotFoundException(sprintf("The class %s has not been found.", $typeClass));
        }

        $this->entityManager = $entityManager;
        $this->typeClass = $typeClass;

        // Load eagerly everything.
        $this->collection = $this->entityManager->getRepository($this->typeClass)->findAll();
    }

    /**
     * Returns the class this collection consists of.
     * @return ClassMetadata
     */
    public function getTypeClass(): ClassMetadata
    {
        return $this->entityManager->getClassMetadata($this->typeClass);
    }

    /**
     * @return int
     * @throws \Doctrine\ORM\NonUniqueResultException
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
        } else {
            return count($this->collection);
        }
    }

    /**
     * Checks if the element matches the type of this collection.
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
     * @param mixed $element
     * @return true|void
     * @throws WrongTypeException
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
     * Clears the collection.
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
     * @param mixed $element
     * @return bool
     */
    public function contains($element): bool
    {
        try {
            $this->checkElementType($element);
        } catch (WrongTypeException $e) {
            return false;
        }

        return in_array($element, $this->collection);
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->collection);
    }

    /**
     * @param int|string $key
     * @return mixed|void
     */
    public function remove($key)
    {
        if (isset($this->collection[$key])) {
            $element = $this->collection[$key];
            $this->removeElement($element);
        }
    }

    /**
     * @param mixed $element
     * @return bool|void
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
     * @param int|string $key
     * @return bool
     */
    public function containsKey($key)
    {
        return isset($this->collection[$key]);
    }

    /**
     * Returns the element saved with the given key.
     * @param int|string $key
     * @return type
     * @throws KeyNotFoundException
     */
    public function get($key)
    {
        if (isset($this->collection[$key])) {
            return $this->collection[$key];
        } else {
            throw new KeyNotFoundException(sprintf("The key %s has not been found within the collection", $key));
        }
    }

    /**
     * @return array
     */
    public function getKeys(): array
    {
        return array_keys($this->collection);
    }

    /**
     * @return array
     */
    public function getValues(): array
    {
        return array_values($this->collection);
    }

    /**
     * @param int|string $key
     * @param mixed $value
     * @throws WrongTypeException
     */
    public function set($key, $value)
    {
        $this->checkElementType($value);

        $this->remove($key);
        $this->collection[$key] = $value;
        $this->entityManager->persist($value);
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return $this->collection;
    }

    /**
     * @return mixed
     */
    public function first()
    {
        return array_values($this->collection)[0];
    }

    /**
     * @return mixed
     */
    public function last()
    {
        return array_values($this->collection)[count($this->collection)];
    }

    /**
     * @return int|string|null
     */
    public function key()
    {
        return key($this->collection);
    }

    /**
     * @return mixed|object
     */
    public function next()
    {
        return next($this->collection);
    }

    /**
     * @return mixed|object
     */
    public function current()
    {
        return current($this->collection);
    }

    /**
     * @param \Closure $p
     * @return bool
     * @throws NotImplementedException
     */
    public function exists(\Closure $p): bool
    {
        throw new NotImplementedException();
    }

    /**
     * @param \Closure $p
     * @return Collection|void
     * @throws NotImplementedException
     */
    public function filter(\Closure $p)
    {
        throw new NotImplementedException();
    }

    /**
     * @param \Closure $p
     * @return bool|void
     * @throws NotImplementedException
     */
    public function forAll(\Closure $p)
    {
        throw new NotImplementedException();
    }

    /**
     * @param \Closure $p
     * @return Collection|void
     * @throws NotImplementedException
     */
    public function map(\Closure $p)
    {
        throw new NotImplementedException();
    }

    /**
     * @param \Closure $p
     * @return Collection[]|void
     * @throws NotImplementedException
     */
    public function partition(\Closure $p)
    {
        throw new NotImplementedException();
    }

    /**
     * @param mixed $element
     * @return bool|false|int|string
     * @throws WrongTypeException
     */
    public function indexOf($element)
    {
        $this->checkElementType($element);
        return array_search($element, $this->collection);
    }

    /**
     * @param int $offset
     * @param null $length
     * @return array|void
     * @throws NotImplementedException
     */
    public function slice($offset, $length = null)
    {
        throw new NotImplementedException();
    }

    /**
     * Gets a Iterator over this collection.
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->collection);
    }

    /**
     * @param mixed $key
     * @return mixed
     * @throws KeyNotFoundException
     */
    public function offsetGet($key)
    {
        return $this->get($key);
    }

    /**
     * @param mixed $key
     * @param mixed $element
     */
    public function offsetSet($key, $element)
    {
         $this->set($key, $element);
    }

    /**
     * @param mixed $key
     */
    public function offsetUnset($key)
    {
        $this->remove($key);
    }

    /**
     * @param mixed $key
     * @return bool
     */
    public function offsetExists($key)
    {
        return isset($this->collection[$key]);
    }
}
