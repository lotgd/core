<?php
declare(strict_types=1);

namespace LotGD\Core\Tests;

use Composer\Package\PackageInterface;
use Composer\Composer;

use Doctrine\Common\Util\Debug;
use LotGD\Core\Game;
use LotGD\Core\ComposerManager;
use LotGD\Core\EventHandler;
use LotGD\Core\EventManager;
use LotGD\Core\EventSubscription;
use LotGD\Core\LibraryConfiguration;
use LotGD\Core\Models\Character;
use LotGD\Core\ModuleManager;
use LotGD\Core\Module;
use LotGD\Core\Exceptions\ModuleAlreadyExistsException;
use LotGD\Core\Exceptions\ModuleDoesNotExistException;
use LotGD\Core\Tests\CoreModelTestCase;
use LotGD\Core\Tests\FakeModule\Module as FakeModule;
use LotGD\Core\Tests\DefectiveModule\Module as DefectiveModule;

class ModuleManagerTest extends CoreModelTestCase
{
    /** @var string default data set */
    protected $dataset = "module";

    protected $game;
    protected $mm;

    public function setUp(): void
    {
        parent::setUp();

        $this->game = $this->getMockBuilder(Game::class)
                     ->disableOriginalConstructor()
                     ->getMock();
        $this->game->method('getEntityManager')->willReturn($this->getEntityManager());

        $this->mm = new ModuleManager($this->game);
    }

    public function testModuleAlreadyExists()
    {
        $library = $this->getMockBuilder(LibraryConfiguration::class)
                        ->disableOriginalConstructor()
                        ->getMock();
        $library->method('getName')->willReturn('lotgd/tests');

        $this->expectException(ModuleAlreadyExistsException::class);
        $this->mm->register($library);
    }

    public function testGetModule()
    {
        $m = $this->mm->getModule('lotgd/tests');
        $this->assertEquals('lotgd/tests', $m->getLibrary());
    }

    public function testGetModules()
    {
        $modules = $this->mm->getModules();
        $this->assertContainsOnlyInstancesOf(\LotGD\Core\Models\Module::class, $modules);

        // This is a little fragile, but assertContains() doesn't seem to work.
        $this->assertEquals(new \DateTime('2016-05-01'), $modules[0]->getCreatedAt());
        $this->assertEquals('lotgd/tests', $modules[0]->getLibrary());
    }

    public function testModuleDoesNotExist()
    {
        $library = $this->getMockBuilder(LibraryConfiguration::class)
                        ->disableOriginalConstructor()
                        ->getMock();
        $library->method('getName')->willReturn('lotgd/no-module');

        $this->expectException(ModuleDoesNotExistException::class);
        $this->mm->unregister($library);
    }

    public function testUnregisterWithNoEvents()
    {
        $library = $this->getMockBuilder(LibraryConfiguration::class)
                        ->disableOriginalConstructor()
                        ->getMock();
        $library->method('getName')->willReturn('lotgd/tests');
        $library->method('getRootNamespace')->willReturn('LotGD\\Core\\Tests\\FakeModule\\');
        $library->method('getSubscriptionPatterns')->willReturn([]);

        $eventManager = $this->getMockBuilder(EventManager::class)
                             ->disableOriginalConstructor()
                             ->getMock();
        $this->game->method('getEventManager')->willReturn($eventManager);

        $this->mm->unregister($library);

        $modules = $this->mm->getModules();
        $this->assertEmpty($modules);
    }

    public function testUnregisterWithEvents()
    {
        $class = FakeModule::class;
        $name = 'lotgd/tests';
        $subscriptions = array(
            '/pattern1/',
            '/pattern2/'
        );
        $library = $this->getMockBuilder(LibraryConfiguration::class)
                        ->disableOriginalConstructor()
                        ->getMock();
        $library->method('getName')->willReturn($name);
        $library->method('getRootNamespace')->willReturn('LotGD\\Core\\Tests\\FakeModule\\');
        $library->method('getSubscriptionPatterns')->willReturn($subscriptions);

        $eventManager = $this->getMockBuilder(EventManager::class)
                             ->disableOriginalConstructor()
                             ->setMethods(array('unsubscribe'))
                             ->getMock();
        $eventManager->expects($this->exactly(2))
                     ->method('unsubscribe')
                     ->withConsecutive(
                         array($this->equalTo($subscriptions[0]), $this->equalTo($class), $name),
                         array($this->equalTo($subscriptions[1]), $this->equalTo($class), $name)
                     );

        $this->game->method('getEventManager')->willReturn($eventManager);

        $this->mm->unregister($library);

        $modules = $this->mm->getModules();
        $this->assertEmpty($modules);
    }

    public function testRegisterWithNoEvents()
    {
        $class = FakeModule::class;
        $name = 'lotgd/tests2';
        $subscriptions = [];
        $library = $this->getMockBuilder(LibraryConfiguration::class)
                        ->disableOriginalConstructor()
                        ->getMock();
        $library->method('getName')->willReturn($name);
        $library->method('getRootNamespace')->willReturn('LotGD\\Core\\Tests\\FakeModule\\');
        $library->method('getSubscriptionPatterns')->willReturn($subscriptions);

        $eventManager = $this->getMockBuilder(EventManager::class)
                             ->disableOriginalConstructor()
                             ->getMock();

        $this->game->method('getEventManager')->willReturn($eventManager);

        $this->mm->register($library);

        $modules = $this->mm->getModules();

        // Timestamps should be within 5 seconds :)
        $timeDiff = (new \DateTime())->getTimestamp() - $modules[1]->getCreatedAt()->getTimestamp();
        $this->assertLessThanOrEqual(5, $timeDiff);
        $this->assertGreaterThanOrEqual(-5, $timeDiff);
        $this->assertEquals($name, $modules[1]->getLibrary());
    }

    public function testRegisterWithEvents()
    {
        $class = FakeModule::class;
        $name = 'lotgd/tests2';
        $subscriptions = array(
            '/pattern1/',
            '/pattern2/'
        );
        $library = $this->getMockBuilder(LibraryConfiguration::class)
                        ->disableOriginalConstructor()
                        ->getMock();
        $library->method('getName')->willReturn($name);
        $library->method('getRootNamespace')->willReturn('LotGD\\Core\\Tests\\FakeModule\\');
        $library->method('getSubscriptionPatterns')->willReturn($subscriptions);

        $eventManager = $this->getMockBuilder(EventManager::class)
                             ->disableOriginalConstructor()
                             ->setMethods(array('subscribe'))
                             ->getMock();
        $eventManager->expects($this->exactly(2))
                     ->method('subscribe')
                     ->withConsecutive(
                         array($this->equalTo($subscriptions[0]), $this->equalTo($class), $name),
                         array($this->equalTo($subscriptions[1]), $this->equalTo($class), $name)
                     );

        $this->game->method('getEventManager')->willReturn($eventManager);

        $this->mm->register($library);

        $modules = $this->mm->getModules();

        // Timestamps should be within 5 seconds :)
        $timeDiff = (new \DateTime())->getTimestamp() - $modules[1]->getCreatedAt()->getTimestamp();
        $this->assertLessThanOrEqual(5, $timeDiff);
        $this->assertGreaterThanOrEqual(-5, $timeDiff);
        $this->assertEquals($name, $modules[1]->getLibrary());
    }

    public function testRegisterWithFailedEvents()
    {
        $class = FakeModule::class;
        $name = 'lotgd/tests2';
        $subscriptions = array(
            '/pattern1/',
            '#asasd/',
        );
        $library = $this->getMockBuilder(LibraryConfiguration::class)
            ->disableOriginalConstructor()
            ->getMock();
        $library->method('getName')->willReturn($name);
        $library->method('getRootNamespace')->willReturn('LotGD\\Core\\Tests\\FakeModule\\');
        $library->method('getSubscriptionPatterns')->willReturn($subscriptions);

        $eventManager = new EventManager($this->g);
        $this->game->method('getEventManager')->willReturn($eventManager);

        $eventsBefore = count($eventManager->getSubscriptions());

        $subscriptionThrownException = false;
        try {
            $this->mm->register($library);
        } catch(\Throwable $e) {
            $subscriptionThrownException = true;
        }

        $this->assertTrue($subscriptionThrownException);

        // Assert module has not been installed.
        $modules = $this->mm->getModules();
        $this->assertArrayNotHasKey(1, $modules);

        // Assert events are not registered
        $eventsAfter = count($eventManager->getSubscriptions());

        // Randomly flush
        $this->getEntityManager()->flush();

        $this->assertSame($eventsBefore, $eventsAfter, "Events after failed subscription are actually more.");
    }

    public function testRegisteringDefectiveModule()
    {
        $class = DefectiveModule::class;
        $name = "lotgd/tests3";
        $subscriptions = ["#e/lotgd/core/tests/dat-event#"];
        $library = $this->getMockBuilder(LibraryConfiguration::class)
            ->disableOriginalConstructor()
            ->getMock();
        $library->method('getName')->willReturn($name);
        $library->method('getRootNamespace')->willReturn('LotGD\\Core\\Tests\\DefectiveModule\\');
        $library->method('getSubscriptionPatterns')->willReturn($subscriptions);

        // Defective Module adds a character. Count the characters before.
        $charactersBefore = count($this->getEntityManager()->getRepository(Character::class)->findAll());

        $eventManager = new EventManager($this->g);
        $this->game->method('getEventManager')->willReturn($eventManager);

        $modulesBefore = $this->mm->getModules();
        try {
            // onRegister throws an exception. This exception needs to be captured and handled by mm->register without actually
            // registering a real module...
            $this->mm->register($library);
            $exceptionCaptured = false;
        } catch(\Throwable $e) {
            $exceptionCaptured = true;
        }
        $modulesAfter = $this->mm->getModules();

        $this->assertTrue($exceptionCaptured);
        $this->assertCount(count($modulesBefore), $modulesAfter);

        // Make sure there are no event leftovers.
        $subscriptions_db = $eventManager->getSubscriptions();
        $found = 0;
        foreach($subscriptions_db as $subscription) {
            if (in_array($subscription->getPattern(), $subscriptions)) {
                $found++;
            }
        }
        $this->assertSame(0, $found);

        // Count characters. Must stay the same!
        $charactersAfter = count($this->getEntityManager()->getRepository(Character::class)->findAll());
        $this->assertSame($charactersBefore, $charactersAfter, "Modules flushed did not get not added to the database.");
    }
}
