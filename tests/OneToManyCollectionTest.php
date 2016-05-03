<?php
declare(strict_types=1);

namespace LotGD\Core\Tests\Models;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;

use LotGD\Core\Models\GameConfigurationElement;
use LotGD\Core\Tools\OneToManyCollection;
use LotGD\Core\Tests\ModelTestCase;
use LotGD\Core\Exceptions\WrongTypeException;

/**
 * Tests for creating scenes and moving them around.
 */
class OneToManyCollectionTest extends ModelTestCase
{
    /** @var string default data set */
    protected $dataset = "gameConfiguration";
    
    private function getCollection(): OneToManyCollection
    {
        $em = $this->getEntityManager();
        $em->flush();
        $em->clear();
        return new OneToManyCollection($em, GameConfigurationElement::class);
    }
    
    public function testArrayAccessInterface()
    {
        $em = $this->getEntityManager();
        $collection = $this->getCollection();
        
        $this->assertTrue(isset($collection[2]));
        $this->assertFalse(isset($collection[4]));
        $this->assertNotSame($collection[0], $collection[1]);
        $this->assertInstanceOf(GameConfigurationElement::class, $collection[3]);
        
        unset($collection[2]);
        $this->assertFalse(isset($collection[2]));
        
        $collection = $this->getCollection();
        
        // We deleted one
        $this->assertFalse(isset($collection[3]));
        $newElement = new GameConfigurationElement();
        $newElement->setName("halodry");
        $newElement->setValue("grml");
        $collection[3] = $newElement;
        $em->persist($collection[3]);
        
        $em->flush();
    }
    
    public function testCountableInterfaceAndClear()
    {
        $em = $this->getEntityManager();
        $collection = $this->getCollection();
        
        $this->assertSame(4, count($collection));
        $this->assertFalse($collection->isEmpty());
        
        $collection->clear();
        $collection = $this->getCollection();
        
        $this->assertSame(0, count($collection));
        $this->assertTrue($collection->isEmpty());
    }
    
    public function testArrayIteratorInterface()
    {
        $em = $this->getEntityManager();
        $collection = $this->getCollection();
        
        foreach ($collection as $key => $val) {
            $this->assertInstanceOf(GameConfigurationElement::class, $val);
        }
    }
    
    public function testCollectionInterface()
    {
        $em = $this->getEntityManager();
        
        $newElement = new GameConfigurationElement();
        $newElement->setName("testConfig");
        $newElement->setValue("testValue.5");
        
        $collection = $this->getCollection();
        
        // Test OneToManyCollection::Add
        $collection->add($newElement);
        $this->assertSame(5, count($collection));
        
        $exceptionCount = 0;
        try {
            $collection->add("A String");
        } catch (WrongTypeException $ex) {
            $exceptionCount++;
        }
        
        $this->assertSame(1, $exceptionCount);
        $this->assertSame(5, count($collection));
        
        $collection = $this->getCollection();
        $this->assertSame(5, count($collection));
        
        // Test OneToManyCollection::get, remove and contains
        $testElement1 = $this->getEntityManager()
            ->getRepository(GameConfigurationElement::class)
            ->findByPropertyName("gameVersion")[0];
        $testElement2 = $collection->get(4);
        $this->assertSame("testConfig", $testElement2->getName());
        $collection->remove(4);
        
        $this->assertInstanceOf(GameConfigurationElement::class, $testElement1);
        $this->assertTrue($collection->contains($testElement1));
        $this->assertFalse($collection->contains("adsiofioadsf"));
        $this->assertFalse($collection->contains($testElement2));
        
        // Test ::removeElement
        $collection->removeElement($testElement1);
        $this->assertSame(3, count($collection));
        
        $collection = $this->getCollection();
        $this->assertSame(3, count($collection));
        
        // Test ::containsKey
        $this->assertTrue($collection->containsKey(2));
        $this->assertFalse($collection->containsKey(3));
        
        // Test ::getKeys(), getValues(), toArray()
        $keys = $collection->getKeys();
        $values = $collection->getValues();
        
        $this->assertSame([0, 1, 2], $keys);
        $this->assertSame([$collection[0], $collection[1], $collection[2]], $values);
        $this->assertSame(\array_combine($keys, $values), $collection->toArray());
        
        // Test ::set
        $oldElement = $collection[2];
        $collection->set(2, $newElement);
        
        $this->assertNotSame($oldElement, $newElement);
        $this->assertNotSame($oldElement, $collection[2]);
        $this->assertSame($newElement, $collection[2]);
        
        $collection = $this->getCollection();
        
        $oldElementFound = false;
        $newElementFound = false;
        foreach ($collection as $element) {
            if ($element->getName() === $oldElement->getName()) {
                $oldElementFound = true;
            }
            
            if ($element->getName() === $newElement->getName()) {
                $newElementFound = true;
            }
        }
        
        $this->assertTrue($newElementFound);
        $this->assertFalse($oldElementFound);
    }
    
    public function testCollectionFilterInterface()
    {
        
    }
    
    public function testTypeClass()
    {
        $collection = $this->getCollection();
        $typeClass = $collection->getTypeClass();
        
        $this->assertInstanceOf(ClassMetadata::class, $typeClass);
        $this->assertSame(GameConfigurationElement::class, $typeClass->name);
    }
}
