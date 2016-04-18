<?php
declare(strict_types=1);

namespace LotGD\Core\Tests\Models;

use LotGD\Core\Models\Character;
use LotGD\Core\Tests\ModelTestCase;

use Doctrine\ORM\Mapping as ORM;

/**
 * Description of CharacterModelTest
 *
 * @author Basilius Sauter
 */
class CharacterModelTest extends ModelTestCase {
    /** @var string default data set */
    protected $dataset = "character";
    
    /**
     * Returns data to create valid characters
     * @return array $futureId => $characterData
     */
    public function validCharacters() {
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
    public function invalidCharacters() {
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
    public function testCreation(array $characterData) {
        $em = $this->getEntityManager();
        
        $characterEntity = Character::createAtFullHealth($characterData);
        $characterEntity->save($em);
        
        $this->assertInternalType("int", $characterEntity->getId());
    }
    
    /**
     * Tests character creation with faulty data
     * @param type $characterData
     * @dataProvider invalidCharacters
     * @expectedException TypeError
     */
    public function testFaultyCreation(array $characterData) {
        Character::create($characterData);
    }
    
    /**
     * Tests if invalid array key given during Character::create throws an exception
     * @expectedException \LotGD\Core\Exceptions\UnexpectedArrayKeyException
     */
    public function testUnknownArrayKey() {
        Character::create([
            "name" => "Walter",
            "maxHealth" => 15,
            "unknownAttribute" => "helloWorld",
        ]);
    }
    
    /**
     * Tests if Deletor does it's work
     */
    public function testDeletion() {
        $em = $this->getEntityManager();
        
        // Count rows before
        $rowsBefore = count($em->getRepository(Character::class)->findAll());
        
        // Delete one row
        $character = $em->getRepository(Character::class)->find(1);
        $character->delete($em);
        
        $rowsAfter = count($em->getRepository(Character::class)->findAll());
        
        $this->assertEquals($rowsBefore - 1, $rowsAfter);
    }
}
