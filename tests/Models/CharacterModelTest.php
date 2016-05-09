<?php
declare(strict_types=1);

namespace LotGD\Core\Tests\Models;

use LotGD\Core\Models\Character;
use LotGD\Core\Models\CharacterProperty;
use LotGD\Core\Tests\ModelTestCase;
use LotGD\Core\Models\Repositories\CharacterRepository;

/**
 * Tests the management of Characters
 */
class CharacterModelTest extends ModelTestCase {
    /** @var string default data set */
    protected $dataset = "character";
    
    public function testSoftDeletion()
    {
        $chars = $this->getEntityManager()->getRepository(Character::class)->find(3);
        $this->assertSame(null, $chars);
        
        $allChars = $this->getEntityManager()->getRepository(Character::class)->findAll();
        $this->assertSame(2, count($allChars));
        
        $char = $this->getEntityManager()->getRepository(Character::class)->find(1);
        $char->delete($this->getEntityManager());
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();
        
        
        $allChars = $this->getEntityManager()->getRepository(Character::class)->findAll();
        $this->assertSame(1, count($allChars));
        
        $allChars = $this->getEntityManager()->getRepository(Character::class)->findAll(CharacterRepository::INCLUDE_SOFTDELETED);
        $this->assertSame(3, count($allChars));
        
        $this->getEntityManager()->getFilters()->enable("soft-deleteable");
    }
    
    /**
     * Returns data to create valid characters
     * @return array $futureId => $characterData
     */
    public function validCharacters(): array
    {
        return [
            [[
                "name" => "Testcharacter",
                "maxHealth" => 250
            ]],
            [[
                "name" => "Spamegg",
                "maxHealth" => 42
            ]],
        ];
    }
    
    /**
     * Returns data to create invalid characters
     * @return array A list of faulty characters
     */
    public function invalidCharacters(): array
    {
        return [
            [[
                "name" => 16,
                "maxHealth" => 16,
            ]],
            [[
                "name" => "Faulter",
                "maxHealth" => 17.8,
            ]],
        ];
    }
    
    /**
     * Tests character creation
     * @param array $characterData
     * @dataProvider validCharacters
     */
    public function testCreation(array $characterData)
    {
        $em = $this->getEntityManager();
        
        $characterEntity = Character::create($characterData);
        $characterEntity->save($em);
        
        $em->flush();
        
        $this->assertInternalType("int", $characterEntity->getId());
        
        $em->flush();
    }
    
    /**
     * Tests character creation with faulty data
     * @param type $characterData
     * @dataProvider invalidCharacters
     * @expectedException TypeError
     */
    public function testFaultyCreation(array $characterData)
    {
        Character::create($characterData);
    }
    
    /**
     * Tests if invalid array key given during Character::create throws an exception
     * @expectedException \LotGD\Core\Exceptions\UnexpectedArrayKeyException
     */
    public function testUnknownArrayKey()
    {
        Character::create([
            "name" => "Walter",
            "maxHealth" => 15,
            "unknownAttribute" => "helloWorld",
        ]);
    }
    
    /**
     * Tests if Deletor does it's work
     */
    public function testDeletion()
    {
        $em = $this->getEntityManager();
        
        // Count rows before
        $rowsBefore = count($em->getRepository(Character::class)->findAll());
        
        // Delete one row
        $character = $em->getRepository(Character::class)->find(1);
        $character->delete($em);
        
        $em->clear();
        
        $rowsAfter = count($em->getRepository(Character::class)->findAll());
        
        $this->assertEquals($rowsBefore - 1, $rowsAfter);
        
        // test flushing
        $em->flush();
    }
    
    /**
     * Tests character properties
     */
    public function testProperties()
    {
        $em = $this->getEntityManager();
        
        // test default values
        $firstCharacter = $em->getRepository(Character::class)->find(1);
        $this->assertSame(5, $firstCharacter->getProperty("dragonkills", 5));
        $this->assertNotSame(5, $firstCharacter->getProperty("dragonkills", "5"));
        $this->assertSame("hanniball", $firstCharacter->getProperty("petname", "hanniball"));
        
        // test setting variables, then getting
        $firstCharacter->setProperty("dragonkills", 5);
        $this->assertSame(5, $firstCharacter->getProperty("dragonkills"));
        $this->assertNotSame("5", $firstCharacter->getProperty("dragonkills"));
        
        $firstCharacter->setProperty("dragonkills", "20");
        $this->assertNotSame(20, $firstCharacter->getProperty("dragonkills"));
        $this->assertSame("20", $firstCharacter->getProperty("dragonkills"));
        
        // save some other variables
        $firstCharacter->setProperty("testvar1", 5);
        $firstCharacter->setProperty("testvar2", [5 => 18]);
        $firstCharacter->setProperty("testvar3 9 8", "spam and eggs");
        $firstCharacter->setProperty("testvar4", true);
        
        // test precreated property
        $this->assertSame("hallo", $firstCharacter->getProperty("test"));
        
        // test flushing
        $em->flush();
        
        // revisit database and retrieve properties, check if the correct number is saved
        $total = intval($em->createQueryBuilder()
            ->from(CharacterProperty::class, "u")
            ->select("COUNT(u.propertyName)")
            ->getQuery()->getSingleScalarResult());
        
        $this->assertSame(6, $total);
    }
}
