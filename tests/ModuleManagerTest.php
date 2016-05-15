<?php
declare(strict_types=1);

namespace LotGD\Core\Tests;

use LotGD\Core\Game;
use LotGD\Core\EventHandler;
use LotGD\Core\EventManager;
use LotGD\Core\ModuleManager;
use LotGD\Core\Models\Module;
use LotGD\Core\Exceptions\ModuleAlreadyExistsException;
use LotGD\Core\Exceptions\ModuleDoesNotExistException;
use LotGD\Core\Tests\ModelTestCase;
use Composer\Package\PackageInterface;

class ModuleManagerTestSubscriber implements EventHandler
{
    public static function handleEvent(string $event, array $context) {}
}

class ModuleManagerTestAnotherSubscriber implements EventHandler
{
    public static function handleEvent(string $event, array $context) {}
}

class ModuleManagerTest extends ModelTestCase
{
    /** @var string default data set */
    protected $dataset = "module";

    public function testModuleAlreadyExists()
    {
        $game = $this->getMockBuilder(Game::class)
                     ->disableOriginalConstructor()
                     ->getMock();
        $game->method('db')->willReturn($this->getEntityManager());

        $package = $this->getMockBuilder(PackageInterface::class)->getMock();

        $mm = new ModuleManager($this->getEntityManager());

        $this->expectException(ModuleAlreadyExistsException::class);
        $mm->register($game, 'lotgd/test', $package);
    }

    public function testGetModules()
    {
        $mm = new ModuleManager($this->getEntityManager());

        $modules = $mm->getModules();
        $this->assertContainsOnlyInstancesOf(Module::class, $modules);

        // This is a little fragile, but assertContains() doesn't seem to work.
        $this->assertEquals(new \DateTime('2016-05-01'), $modules[0]->getCreatedAt());
        $this->assertEquals('lotgd/test', $modules[0]->getLibrary());
    }

    public function testModuleDoesNotExist()
    {
        $package = $this->getMockBuilder(PackageInterface::class)->getMock();

        $game = $this->getMockBuilder(Game::class)
                     ->disableOriginalConstructor()
                     ->getMock();
        $game->method('db')->willReturn($this->getEntityManager());

        $mm = new ModuleManager($this->getEntityManager());

        $this->expectException(ModuleDoesNotExistException::class);
        $mm->unregister($game, 'lotgd/no-module', $package);
    }

    public function testUnregisterWithNoEvents()
    {
        $package = $this->getMockBuilder(PackageInterface::class)
                        ->setMethods(['getExtra'])
                        ->getMock();
        $package->method('getExtra')->willReturn(array());

        $eventManager = $this->getMockBuilder(EventManager::class)
                             ->disableOriginalConstructor()
                             ->getMock();

        $game = $this->getMockBuilder(Game::class)
                     ->disableOriginalConstructor()
                     ->getMock();
        $game->method('db')->willReturn($this->getEntityManager());
        $game->method('events')->willReturn($eventManager);

        $mm = new ModuleManager($this->getEntityManager());

        $mm->unregister($game, 'lotgd/test', $package);

        $modules = $mm->getModules();
        $this->assertEmpty($modules);
    }

    public function testUnregisterWithEvents()
    {
        $subscriptions = array(
            array(
                'pattern' => '/pattern1/',
                'class' => 'SomeClass1'
            ),
            array(
                'pattern' => '/pattern2/',
                'class' => 'SomeClass2'
            ),
        );

        $package = $this->getMockBuilder(PackageInterface::class)
                        ->setMethods(['getExtra'])
                        ->getMock();
        $package->method('getExtra')->willReturn(array(
            'subscriptions' => $subscriptions
        ));

        $eventManager = $this->getMockBuilder(EventManager::class)
                             ->disableOriginalConstructor()
                             ->setMethods(array('unsubscribe'))
                             ->getMock();
        $eventManager->expects($this->exactly(2))
                     ->method('unsubscribe')
                     ->withConsecutive(
                         array($this->equalTo($subscriptions[0]['pattern']), $this->equalTo($subscriptions[0]['class'])),
                         array($this->equalTo($subscriptions[1]['pattern']), $this->equalTo($subscriptions[1]['class']))
                     );

        $game = $this->getMockBuilder(Game::class)
                     ->disableOriginalConstructor()
                     ->getMock();
        $game->method('db')->willReturn($this->getEntityManager());
        $game->method('events')->willReturn($eventManager);

        $mm = new ModuleManager($this->getEntityManager());

        $mm->unregister($game, 'lotgd/test', $package);

        $modules = $mm->getModules();
        $this->assertEmpty($modules);
    }

    public function testUnregisterWithInvalidEvents()
    {
        $subscriptions = array(
            array(
                'pattern' => '/pattern1/',
                'class' => 'SomeClass1'
            ),
            array(
                // Invalid subscription.
                'crazy' => 'making'
            ),
        );

        $package = $this->getMockBuilder(PackageInterface::class)
                        ->setMethods(['getExtra'])
                        ->getMock();
        $package->method('getExtra')->willReturn(array(
            'subscriptions' => $subscriptions
        ));

        $eventManager = $this->getMockBuilder(EventManager::class)
                             ->disableOriginalConstructor()
                             ->setMethods(array('unsubscribe'))
                             ->getMock();
        $eventManager->expects($this->exactly(1))
                     ->method('unsubscribe')
                     ->withConsecutive(
                         array($this->equalTo($subscriptions[0]['pattern']), $this->equalTo($subscriptions[0]['class']))
                     );

        $game = $this->getMockBuilder(Game::class)
                     ->disableOriginalConstructor()
                     ->getMock();
        $game->method('db')->willReturn($this->getEntityManager());
        $game->method('events')->willReturn($eventManager);

        $mm = new ModuleManager($this->getEntityManager());

        $mm->unregister($game, 'lotgd/test', $package);

        $modules = $mm->getModules();
        $this->assertEmpty($modules);
    }

    public function testRegisterWithNoEvents()
    {
      $package = $this->getMockBuilder(PackageInterface::class)
                      ->setMethods(['getExtra'])
                      ->getMock();
        $package->method('getExtra')->willReturn(array());

        $eventManager = $this->getMockBuilder(EventManager::class)
                             ->disableOriginalConstructor()
                             ->getMock();

        $game = $this->getMockBuilder(Game::class)
                     ->disableOriginalConstructor()
                     ->getMock();
        $game->method('db')->willReturn($this->getEntityManager());
        $game->method('events')->willReturn($eventManager);

        $mm = new ModuleManager($this->getEntityManager());

        $mm->register($game, 'lotgd/test2', $package);

        $modules = $mm->getModules();

        // Timestamps should be within 5 seconds :)
        $timeDiff = (new \DateTime())->getTimestamp() - $modules[1]->getCreatedAt()->getTimestamp();
        $this->assertLessThanOrEqual(5, $timeDiff);
        $this->assertGreaterThanOrEqual(-5, $timeDiff);
        $this->assertEquals('lotgd/test2', $modules[1]->getLibrary());
    }

    public function testRegisterWithEvents()
    {
        $subscriptions = array(
            array(
                'pattern' => '/pattern1/',
                'class' => ModuleManagerTestSubscriber::class
            ),
            array(
                'pattern' => '/pattern2/',
                'class' => ModuleManagerTestAnotherSubscriber::class
            ),
        );

        $package = $this->getMockBuilder(PackageInterface::class)
                        ->setMethods(['getExtra'])
                        ->getMock();
        $package->method('getExtra')->willReturn(array(
            'subscriptions' => $subscriptions
        ));

        $eventManager = $this->getMockBuilder(EventManager::class)
                             ->disableOriginalConstructor()
                             ->setMethods(array('subscribe'))
                             ->getMock();
        $eventManager->expects($this->exactly(2))
                     ->method('subscribe')
                     ->withConsecutive(
                         array($this->equalTo($subscriptions[0]['pattern']), $this->equalTo($subscriptions[0]['class'])),
                         array($this->equalTo($subscriptions[1]['pattern']), $this->equalTo($subscriptions[1]['class']))
                     );

        $game = $this->getMockBuilder(Game::class)
                     ->disableOriginalConstructor()
                     ->getMock();
        $game->method('db')->willReturn($this->getEntityManager());
        $game->method('events')->willReturn($eventManager);

        $mm = new ModuleManager($this->getEntityManager());

        $mm->register($game, 'lotgd/test2', $package);

        $modules = $mm->getModules();

        // Timestamps should be within 5 seconds :)
        $timeDiff = (new \DateTime())->getTimestamp() - $modules[1]->getCreatedAt()->getTimestamp();
        $this->assertLessThanOrEqual(5, $timeDiff);
        $this->assertGreaterThanOrEqual(-5, $timeDiff);
        $this->assertEquals('lotgd/test2', $modules[1]->getLibrary());
    }

    public function testRegisterWithInvalidEvents()
    {
        $subscriptions = array(
            array(
                'pattern' => '/pattern1/',
                'class' => ModuleManagerTestSubscriber::class
            ),
            array(
                // Invalid subscription.
                'crazy' => 'making'
            ),
        );

        $package = $this->getMockBuilder(PackageInterface::class)
                        ->setMethods(['getExtra'])
                        ->getMock();
        $package->method('getExtra')->willReturn(array(
            'subscriptions' => $subscriptions
        ));

        $eventManager = $this->getMockBuilder(EventManager::class)
                             ->disableOriginalConstructor()
                             ->setMethods(array('subscribe'))
                             ->getMock();
        $eventManager->expects($this->exactly(1))
                     ->method('subscribe')
                     ->withConsecutive(
                         array($this->equalTo($subscriptions[0]['pattern']), $this->equalTo($subscriptions[0]['class']))
                     );

        $game = $this->getMockBuilder(Game::class)
                     ->disableOriginalConstructor()
                     ->getMock();
        $game->method('db')->willReturn($this->getEntityManager());
        $game->method('events')->willReturn($eventManager);

        $mm = new ModuleManager($this->getEntityManager());

        $mm->register($game, 'lotgd/test2', $package);

        $modules = $mm->getModules();

        // Timestamps should be within 5 seconds :)
        $timeDiff = (new \DateTime())->getTimestamp() - $modules[1]->getCreatedAt()->getTimestamp();
        $this->assertLessThanOrEqual(5, $timeDiff);
        $this->assertGreaterThanOrEqual(-5, $timeDiff);
        $this->assertEquals('lotgd/test2', $modules[1]->getLibrary());
    }
}
