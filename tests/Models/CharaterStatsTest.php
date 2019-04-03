<?php
declare(strict_types=1);


namespace LotGD\Core\Tests\Models;


use LotGD\Core\EventHandler;
use LotGD\Core\Events\EventContext;
use LotGD\Core\Exceptions\CharacterStatExistsException;
use LotGD\Core\Exceptions\CharacterStatGroupExistsException;
use LotGD\Core\Exceptions\CharacterStatGroupNotFoundException;
use LotGD\Core\Exceptions\CharacterStatNotFoundException;
use LotGD\Core\Game;
use LotGD\Core\Models\Character;
use LotGD\Core\Models\CharacterStatGroup;
use LotGD\Core\Models\CharacterStats;
use LotGD\Core\Tests\CoreModelTestCase;

class TestEventProvider implements EventHandler
{
    static $called = 0;
    static $last_context;

    public static function handleEvent(Game $g, EventContext $context): EventContext
    {
        $stats = $context->getDataField("stats");
        $character = $context->getDataField("character");

        self::$called++;
        self::$last_context = $context;

        return $context;
    }
}

class CharaterStatsTest extends CoreModelTestCase
{
    /** @var string default data set */
    protected $dataset = "character_stats";

    public function setUp()
    {
        parent::setUp();

        $game = $this->g;
        $game->getEventManager()->subscribe("#h/lotgd/core/characterStats/populate#", TestEventProvider::class, "lotgd/test");
        $this->getEntityManager()->flush();
    }

    public function tearDown()
    {
        $game = $this->g;
        $game->getEventManager()->unsubscribe("#h/lotgd/core/characterStats/populate#", TestEventProvider::class, "lotgd/test");
        $this->getEntityManager()->flush();

        parent::tearDown();
    }

    public function testICharacterStatPopulationEventGetsCalled()
    {
        $character = $this->getEntityManager()->getRepository(Character::class)->find("10000000-0000-0000-0000-000000000001");
        $stats = new CharacterStats($this->g, $character);

        $this->assertSame(1, TestEventProvider::$called);
    }

    public function testIfCharacterStatPopulationEventUsesCorrectTypes()
    {
        $character = $this->getEntityManager()->getRepository(Character::class)->find("10000000-0000-0000-0000-000000000001");
        $stats = new CharacterStats($this->g, $character);

        $this->assertInstanceOf(CharacterStats::class, TestEventProvider::$last_context->getDataField("stats"));
        $this->assertInstanceOf(Character::class, TestEventProvider::$last_context->getDataField("character"));
    }

    public function testIfCharacterStatGroupCanGetAddedToCharacterStats()
    {
        $character = $this->getEntityManager()->getRepository(Character::class)->find("10000000-0000-0000-0000-000000000001");
        $stats = new CharacterStats($this->g, $character);

        $group = new CharacterStatGroup("vendor/test", "Test");
        $stats->addCharacterStatGroup($group);

        $this->assertSame($group, $stats->getCharacterStatGroup("vendor/test"));
    }

    public function testIfAddingCharacterStatGroupWithSameIdResultsInException()
    {
        $character = $this->getEntityManager()->getRepository(Character::class)->find("10000000-0000-0000-0000-000000000001");
        $stats = new CharacterStats($this->g, $character);

        $group = new CharacterStatGroup("vendor/test", "Test");
        $stats->addCharacterStatGroup($group);
        $this->assertTrue($stats->hasCharacterStatGroup($group->getId()));

        $this->expectException(CharacterStatGroupExistsException::class);

        $group2 = new CharacterStatGroup("vendor/test", "Test");
        $stats->addCharacterStatGroup($group2);
    }

    public function testIfGettingUnknownCharacterStatGroupResultsInException()
    {
        $character = $this->getEntityManager()->getRepository(Character::class)->find("10000000-0000-0000-0000-000000000001");
        $stats = new CharacterStats($this->g, $character);

        $group = new CharacterStatGroup("vendor/test", "Test");
        $this->assertFalse($stats->hasCharacterStatGroup($group->getId()));

        $this->expectException(CharacterStatGroupNotFoundException::class);
        $stats->getCharacterStatGroup($group->getId());
    }

    public function testIfIteratingCharacterStatsYieldsAllStatGroups()
    {
        $character = $this->getEntityManager()->getRepository(Character::class)->find("10000000-0000-0000-0000-000000000001");
        $stats = new CharacterStats($this->g, $character);

        $groups = [
            new CharacterStatGroup("vendor/test-0", "Test 1"),
            new CharacterStatGroup("vendor/test-1", "Test 2"),
            new CharacterStatGroup("vendor/test-2", "Test 3")
        ];

        foreach ($groups as $group) {
            $stats->addCharacterStatGroup($group);
        }

        $i = 0;
        foreach($stats->iterate() as $statGroup)
        {
            $this->assertInstanceOf(CharacterStatGroup::class, $statGroup);
            $this->assertTrue($stats->hasCharacterStatGroup($statGroup->getId()));
            $this->assertSame($groups[$i], $statGroup);

            $i++;
        }
    }

    public function testIfIteratingCharacterStatsYieldsAllStatGroupsSortedAccordingToTheirWeight()
    {
        $character = $this->getEntityManager()->getRepository(Character::class)->find("10000000-0000-0000-0000-000000000001");
        $stats = new CharacterStats($this->g, $character);

        $groups = [
            new CharacterStatGroup("vendor/test-0", "Test 1", 100),
            new CharacterStatGroup("vendor/test-1", "Test 2", 0),
            new CharacterStatGroup("vendor/test-2", "Test 3", -100)
        ];

        foreach ($groups as $group) {
            $stats->addCharacterStatGroup($group);
        }

        $i = 0;
        foreach($stats->iterate() as $statGroup)
        {
            $this->assertInstanceOf(CharacterStatGroup::class, $statGroup);
            $this->assertTrue($stats->hasCharacterStatGroup($statGroup->getId()));
            $this->assertSame($groups[2-$i], $statGroup);

            $i++;
        }
    }

    public function testIfCharacterStatCanGetAddedToCharacterStatGroup()
    {
        $character = $this->getEntityManager()->getRepository(Character::class)->find("10000000-0000-0000-0000-000000000001");
        $stats = new CharacterStats($this->g, $character);

        $group = new CharacterStatGroup("vendor/test", "Test");
        $stats->addCharacterStatGroup($group);

        $stat = new CharacterStats\BaseCharacterStat("vendor/test/item", "Item", 17);
        $group->addCharacterStat($stat);

        $this->assertSame($stat, $group->getCharacterStat("vendor/test/item"));
    }

    public function testIfAddingCharacterStatWithSameIdResultsInException()
    {
        $character = $this->getEntityManager()->getRepository(Character::class)->find("10000000-0000-0000-0000-000000000001");
        $stats = new CharacterStats($this->g, $character);

        $group = new CharacterStatGroup("vendor/test", "Test");
        $stats->addCharacterStatGroup($group);

        $stat = new CharacterStats\BaseCharacterStat("vendor/test/item", "Item", 17);
        $group->addCharacterStat($stat);

        $this->assertTrue($group->hasCharacterStat($stat->getId()));

        $this->expectException(CharacterStatExistsException::class);

        $stat2 = new CharacterStats\BaseCharacterStat("vendor/test/item", "Item", 17);
        $group->addCharacterStat($stat2);
    }

    public function testIfGettingUnknownCharacterStatResultsInException()
    {
        $character = $this->getEntityManager()->getRepository(Character::class)->find("10000000-0000-0000-0000-000000000001");
        $stats = new CharacterStats($this->g, $character);

        $group = new CharacterStatGroup("vendor/test", "Test");
        $stats->addCharacterStatGroup($group);

        $stat = new CharacterStats\BaseCharacterStat("vendor/test/item", "Item", 17);

        $this->assertFalse($group->hasCharacterStat($stat->getId()));

        $this->expectException(CharacterStatNotFoundException::class);
        $group->getCharacterStat($group->getId());
    }

    public function testIfIteratingCharacterGroupYieldsAllStatGroups()
    {
        $character = $this->getEntityManager()->getRepository(Character::class)->find("10000000-0000-0000-0000-000000000001");
        $stats = new CharacterStats($this->g, $character);

        $stats = [
            new CharacterStats\BaseCharacterStat("vendor/test/item-0", "Item 1", 17),
            new CharacterStats\BaseCharacterStat("vendor/test/item-1", "Item 2", 18),
            new CharacterStats\BaseCharacterStat("vendor/test/item-2", "Item 3", 19),
        ];

        $group = new CharacterStatGroup("vendor/test", "Test-Group");

        foreach ($stats as $stat) {
            $group->addCharacterStat($stat);
        }

        $i = 0;
        foreach($group->iterate() as $stat) {
            $this->assertInstanceOf(CharacterStats\BaseCharacterStat::class, $stat);
            $this->assertTrue($group->hasCharacterStat($stat->getId()));
            $this->assertSame($stats[$i], $stat);

            $i++;
        }
    }

    public function testIfIteratingCharacterGroupYieldsAllStatGroupsIfWeightsAreGiven()
    {
        $character = $this->getEntityManager()->getRepository(Character::class)->find("10000000-0000-0000-0000-000000000001");
        $stats = new CharacterStats($this->g, $character);

        $stats = [
            new CharacterStats\BaseCharacterStat("vendor/test/item-0", "Item 1", 17, 100),
            new CharacterStats\BaseCharacterStat("vendor/test/item-1", "Item 2", 18, 0),
            new CharacterStats\BaseCharacterStat("vendor/test/item-2", "Item 3", 19, -1),
        ];

        $group = new CharacterStatGroup("vendor/test", "Test-Group");

        foreach ($stats as $stat) {
            $group->addCharacterStat($stat);
        }

        $i = 0;
        foreach($group->iterate() as $stat) {
            $this->assertInstanceOf(CharacterStats\BaseCharacterStat::class, $stat);
            $this->assertTrue($group->hasCharacterStat($stat->getId()));
            $this->assertSame($stats[2-$i], $stat);

            $i++;
        }
    }
}