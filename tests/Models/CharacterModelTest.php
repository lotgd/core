<?php
declare(strict_types=1);

namespace LotGD\Core\Tests\Models;

use LotGD\Core\EventHandler;
use LotGD\Core\EventManager;
use LotGD\Core\Events\EventContext;
use LotGD\Core\Game;
use LotGD\Core\GameBuilder;
use LotGD\Core\Models\Character;
use LotGD\Core\Models\CharacterProperty;
use LotGD\Core\Tests\CoreModelTestCase;
use LotGD\Core\Models\Repositories\CharacterRepository;

/**
 * Tests the management of Characters
 */
class CharacterModelTest extends CoreModelTestCase
{
    /** @var string default data set */
    protected $dataset = "character";

    /**
     * Tests for soft deletion
     */
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


        $allChars = $this->getEntityManager()
            ->getRepository(Character::class)
            ->findAll();
        $this->assertSame(1, count($allChars));

        $allChars = $this->getEntityManager()
            ->getRepository(Character::class)
            ->findAll(CharacterRepository::INCLUDE_SOFTDELETED);
        $this->assertSame(3, count($allChars));
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
        $characterEntity->setProperty("a property", 16);
        $this->assertSame(16, $characterEntity->getProperty("a property"));
        $characterEntity->save($em);

        $em->flush();

        $this->assertInternalType("int", $characterEntity->getId());
        $this->assertSame(16, $characterEntity->getProperty("a property"));

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

    public function testIfAttackPublishesEvent()
    {
        $level = mt_rand(0, 100);
        $character1 = Character::create(["name" => "Test", "maxHealth" => 10, "level" => $level]);
        $character1->setGame($this->g);
        $character2 = Character::create(["name" => "Test", "maxHealth" => 10, "level" => $level*2]);
        $character2->setGame($this->g);

        $detectionClass = new class implements EventHandler {
            static $events_called = [];

            public static function handleEvent(Game $g, EventContext $context): EventContext
            {
                $event = $context->getEvent();
                $value = $context->getDataField("value");

                self::$events_called[$event] = $value;

                $context->setDataField("value", $value*2);
                return $context;
            }
        };

        /** @var EventManager $eventManager */
        $eventManager = $this->g->getEventManager();

        $eventManager->subscribe("#h/lotgd/core/getCharacterAttack#", get_class($detectionClass), "test");
        $eventManager->subscribe("#h/lotgd/core/getCharacterDefense#", get_class($detectionClass), "test");

        $this->assertSame($level*2, $character1->getAttack());
        $this->assertSame($level*4, $character2->getDefense());

        $this->assertSame($level, $detectionClass::$events_called["h/lotgd/core/getCharacterAttack"]);
        $this->assertSame($level*2, $detectionClass::$events_called["h/lotgd/core/getCharacterDefense"]);
    }
}
