<?php
declare(strict_types=1);

namespace LotGD\Core\Tests;

use LotGD\Core\Game;
use LotGD\Core\ComposerManager;
use LotGD\Core\EventHandler;
use LotGD\Core\EventManager;
use LotGD\Core\EventSubscription;
use LotGD\Core\ModuleManager;
use LotGD\Core\Models\Module;
use LotGD\Core\Exceptions\ModuleAlreadyExistsException;
use LotGD\Core\Exceptions\ModuleDoesNotExistException;
use LotGD\Core\Tests\ModelTestCase;
use Composer\Package\PackageInterface;
use Composer\Composer;

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

    protected $game;
    protected $mm;

    public function setUp()
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
        $package = $this->getMockForAbstractClass(PackageInterface::class);

        $this->expectException(ModuleAlreadyExistsException::class);
        $this->mm->register('lotgd/tests', $package);
    }

    public function testGetModules()
    {
        $modules = $this->mm->getModules();
        $this->assertContainsOnlyInstancesOf(Module::class, $modules);

        // This is a little fragile, but assertContains() doesn't seem to work.
        $this->assertEquals(new \DateTime('2016-05-01'), $modules[0]->getCreatedAt());
        $this->assertEquals('lotgd/tests', $modules[0]->getLibrary());
    }

    public function testModuleDoesNotExist()
    {
        $package = $this->getMockForAbstractClass(PackageInterface::class);

        $this->expectException(ModuleDoesNotExistException::class);
        $this->mm->unregister('lotgd/no-module', $package);
    }

    public function testUnregisterWithNoEvents()
    {
        $package = $this->getMockForAbstractClass(PackageInterface::class);
        $package->method('getExtra')->willReturn(array());

        $eventManager = $this->getMockBuilder(EventManager::class)
                             ->disableOriginalConstructor()
                             ->getMock();

        $this->game->method('getEventManager')->willReturn($eventManager);

        $this->mm->unregister('lotgd/tests', $package);

        $modules = $this->mm->getModules();
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

        $library = 'lotgd/tests';

        $package = $this->getMockForAbstractClass(PackageInterface::class);
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
                         array($this->equalTo($subscriptions[0]['pattern']), $this->equalTo($subscriptions[0]['class']), $library),
                         array($this->equalTo($subscriptions[1]['pattern']), $this->equalTo($subscriptions[1]['class']), $library)
                     );

        $this->game->method('getEventManager')->willReturn($eventManager);

        $this->mm->unregister($library, $package);

        $modules = $this->mm->getModules();
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

        $library = 'lotgd/tests';

        $package = $this->getMockForAbstractClass(PackageInterface::class);
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
                         array($this->equalTo($subscriptions[0]['pattern']), $this->equalTo($subscriptions[0]['class']), $library)
                     );

        $this->game->method('getEventManager')->willReturn($eventManager);

        $this->mm->unregister($library, $package);

        $modules = $this->mm->getModules();
        $this->assertEmpty($modules);
    }

    public function testRegisterWithNoEvents()
    {
        $package = $this->getMockForAbstractClass(PackageInterface::class);
        $package->method('getExtra')->willReturn(array());

        $library = 'lotgd/tests2';

        $eventManager = $this->getMockBuilder(EventManager::class)
                             ->disableOriginalConstructor()
                             ->getMock();

        $this->game->method('getEventManager')->willReturn($eventManager);

        $this->mm->register($library, $package);

        $modules = $this->mm->getModules();

        // Timestamps should be within 5 seconds :)
        $timeDiff = (new \DateTime())->getTimestamp() - $modules[1]->getCreatedAt()->getTimestamp();
        $this->assertLessThanOrEqual(5, $timeDiff);
        $this->assertGreaterThanOrEqual(-5, $timeDiff);
        $this->assertEquals($library, $modules[1]->getLibrary());
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

        $library = 'lotgd/tests2';

        $package = $this->getMockForAbstractClass(PackageInterface::class);
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
                         array($this->equalTo($subscriptions[0]['pattern']), $this->equalTo($subscriptions[0]['class']), $library),
                         array($this->equalTo($subscriptions[1]['pattern']), $this->equalTo($subscriptions[1]['class']), $library)
                     );

        $this->game->method('getEventManager')->willReturn($eventManager);

        $this->mm->register($library, $package);

        $modules = $this->mm->getModules();

        // Timestamps should be within 5 seconds :)
        $timeDiff = (new \DateTime())->getTimestamp() - $modules[1]->getCreatedAt()->getTimestamp();
        $this->assertLessThanOrEqual(5, $timeDiff);
        $this->assertGreaterThanOrEqual(-5, $timeDiff);
        $this->assertEquals($library, $modules[1]->getLibrary());
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

        $library = 'lotgd/tests2';

        $package = $this->getMockForAbstractClass(PackageInterface::class);
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
                         array($this->equalTo($subscriptions[0]['pattern']), $this->equalTo($subscriptions[0]['class']), $library)
                     );

        $this->game->method('getEventManager')->willReturn($eventManager);

        $this->mm->register($library, $package);

        $modules = $this->mm->getModules();

        // Timestamps should be within 5 seconds :)
        $timeDiff = (new \DateTime())->getTimestamp() - $modules[1]->getCreatedAt()->getTimestamp();
        $this->assertLessThanOrEqual(5, $timeDiff);
        $this->assertGreaterThanOrEqual(-5, $timeDiff);
        $this->assertEquals($library, $modules[1]->getLibrary());
    }

    public function testValidateSuccess()
    {
        $pattern = '/test\\.foo.*/';
        $class = "LotGD\\Core\\Tests\\EventManagerTestInstalledSubscriber";
        $library = 'lotgd/tests';
        $subscriptions = array(
            array(
                'pattern' => $pattern,
                'class' => $class
            ),
        );

        $p1 = $this->getMockBuilder(PackageInterface::class)
                   ->getMock();
        $p1->method('getName')->willReturn($library);
        $p1->method('getExtra')->willReturn(array(
            'subscriptions' => $subscriptions
        ));

        $packages = array($p1);

        $composerManager = $this->getMockBuilder(ComposerManager::class)
                                ->disableOriginalConstructor()
                                ->setMethods(array('getModulePackages'))
                                ->getMock();
        $composerManager->method('getModulePackages')->willReturn($packages);

        $s1 = $this->getMockBuilder(EventSubscription::class)
                   ->disableOriginalConstructor()
                   ->setMethods(array('getPattern', 'getClass', 'getLibrary'))
                   ->getMock();
        $s1->method('getPattern')->willReturn($pattern);
        $s1->method('getClass')->willReturn($class);
        $s1->method('getLibrary')->willReturn($library);
        $installedSubscriptions = array($s1);

        $eventManager = $this->getMockBuilder(EventManager::class)
                             ->disableOriginalConstructor()
                             ->getMock();
        $eventManager->method('getSubscriptions')->willReturn($installedSubscriptions);

        $this->game->method('getComposerManager')->willReturn($composerManager);
        $this->game->method('getEventManager')->willReturn($eventManager);

        $r = $this->mm->validate();
        $this->assertEmpty($r);
    }

    public function testValidateFailMissingSubscription()
    {
        $pattern = '/test\\.foo.*/';
        $class = "LotGD\\Core\\Tests\\EventManagerTestInstalledSubscriber";
        $library = 'lotgd/tests';
        $subscriptions = array(
            array(
                'pattern' => $pattern,
                'class' => $class
            ),
            array(
              'pattern' => '/another pattern/',
              'class' => $class
            )
        );

        $p1 = $this->getMockBuilder(PackageInterface::class)
                   ->getMock();
        $p1->method('getName')->willReturn($library);
        $p1->method('getExtra')->willReturn(array(
            'subscriptions' => $subscriptions
        ));

        $packages = array($p1);

        $composerManager = $this->getMockBuilder(ComposerManager::class)
                                ->disableOriginalConstructor()
                                ->setMethods(array('getModulePackages'))
                                ->getMock();
        $composerManager->method('getModulePackages')->willReturn($packages);

        $s1 = $this->getMockBuilder(EventSubscription::class)
                   ->disableOriginalConstructor()
                   ->setMethods(array('getPattern', 'getClass', 'getLibrary'))
                   ->getMock();
        $s1->method('getPattern')->willReturn($pattern);
        $s1->method('getClass')->willReturn($class);
        $s1->method('getLibrary')->willReturn($library);
        $installedSubscriptions = array($s1);

        $eventManager = $this->getMockBuilder(EventManager::class)
                             ->disableOriginalConstructor()
                             ->getMock();
        $eventManager->method('getSubscriptions')->willReturn($installedSubscriptions);

        $this->game->method('getComposerManager')->willReturn($composerManager);
        $this->game->method('getEventManager')->willReturn($eventManager);

        $r = $this->mm->validate();
        $this->assertTrue(strpos($r[0], "Couldn't find subscription") !== false);
    }

    public function testValidateFailMoreInstalledModules()
    {
        $pattern = '/test\\.foo.*/';
        $class = "LotGD\\Core\\Tests\\EventManagerTestInstalledSubscriber";
        $library = 'lotgd/tests';

        $packages = array();

        $composerManager = $this->getMockBuilder(ComposerManager::class)
                                ->disableOriginalConstructor()
                                ->setMethods(array('getModulePackages'))
                                ->getMock();
        $composerManager->method('getModulePackages')->willReturn($packages);

        $s1 = $this->getMockBuilder(EventSubscription::class)
                   ->disableOriginalConstructor()
                   ->setMethods(array('getPattern', 'getClass', 'getLibrary'))
                   ->getMock();
        $s1->method('getPattern')->willReturn($pattern);
        $s1->method('getClass')->willReturn($class);
        $s1->method('getLibrary')->willReturn($library);
        $installedSubscriptions = array($s1);

        $eventManager = $this->getMockBuilder(EventManager::class)
                             ->disableOriginalConstructor()
                             ->getMock();
        $eventManager->method('getSubscriptions')->willReturn($installedSubscriptions);

        $this->game->method('getComposerManager')->willReturn($composerManager);
        $this->game->method('getEventManager')->willReturn($eventManager);

        $r = $this->mm->validate();
        $this->assertTrue(strpos($r[0], "more installed modules") !== false);
    }

    public function testValidateFailMoreExpectedModules()
    {
        $pattern = '/test\\.foo.*/';
        $class = "LotGD\\Core\\Tests\\EventManagerTestInstalledSubscriber";
        $library = 'lotgd/tests';

        $subscriptions = array(
            array(
                'pattern' => $pattern,
                'class' => $class
            ),
        );

        $p1 = $this->getMockBuilder(PackageInterface::class)
                   ->getMock();
        $p1->method('getName')->willReturn($library);
        $p1->method('getExtra')->willReturn(array(
            'subscriptions' => $subscriptions
        ));

        $p2 = $this->getMockBuilder(PackageInterface::class)
                   ->getMock();
        $p2->method('getName')->willReturn('lotgd/tests-another');
        $p2->method('getExtra')->willReturn(array(
            'subscriptions' => $subscriptions
        ));

        $packages = array($p1, $p2);

        $composerManager = $this->getMockBuilder(ComposerManager::class)
                                ->disableOriginalConstructor()
                                ->setMethods(array('getModulePackages'))
                                ->getMock();
        $composerManager->method('getModulePackages')->willReturn($packages);

        $s1 = $this->getMockBuilder(EventSubscription::class)
                   ->disableOriginalConstructor()
                   ->setMethods(array('getPattern', 'getClass', 'getLibrary'))
                   ->getMock();
        $s1->method('getPattern')->willReturn($pattern);
        $s1->method('getClass')->willReturn($class);
        $s1->method('getLibrary')->willReturn($library);
        $installedSubscriptions = array($s1);

        $eventManager = $this->getMockBuilder(EventManager::class)
                             ->disableOriginalConstructor()
                             ->getMock();
        $eventManager->method('getSubscriptions')->willReturn($installedSubscriptions);

        $this->game->method('getComposerManager')->willReturn($composerManager);
        $this->game->method('getEventManager')->willReturn($eventManager);

        $r = $this->mm->validate();
        $this->assertTrue(strpos($r[0], "more modules configured") !== false);
    }

    public function testValidateWarningUnconfiguredSubscriptions()
    {
        $pattern = '/test\\.foo.*/';
        $class = "LotGD\\Core\\Tests\\EventManagerTestInstalledSubscriber";
        $library = 'lotgd/tests';

        $subscriptions = array(
            array(
                'pattern' => $pattern,
                'class' => $class
            ),
        );

        $p1 = $this->getMockBuilder(PackageInterface::class)
                   ->getMock();
        $p1->method('getName')->willReturn($library);
        $p1->method('getExtra')->willReturn(array(
            'subscriptions' => $subscriptions
        ));

        $packages = array($p1);

        $composerManager = $this->getMockBuilder(ComposerManager::class)
                                ->disableOriginalConstructor()
                                ->setMethods(array('getModulePackages'))
                                ->getMock();
        $composerManager->method('getModulePackages')->willReturn($packages);

        $s1 = $this->getMockBuilder(EventSubscription::class)
                   ->disableOriginalConstructor()
                   ->setMethods(array('getPattern', 'getClass', 'getLibrary'))
                   ->getMock();
        $s1->method('getPattern')->willReturn($pattern);
        $s1->method('getClass')->willReturn($class);
        $s1->method('getLibrary')->willReturn($library);

        $s2 = $this->getMockBuilder(EventSubscription::class)
                   ->disableOriginalConstructor()
                   ->setMethods(array('getPattern', 'getClass', 'getLibrary'))
                   ->getMock();
        $s2->method('getPattern')->willReturn('/some pattern/');
        $s2->method('getClass')->willReturn('SomeClass');
        $s2->method('getLibrary')->willReturn($library);

        $installedSubscriptions = array($s1, $s2);

        $eventManager = $this->getMockBuilder(EventManager::class)
                             ->disableOriginalConstructor()
                             ->getMock();
        $eventManager->method('getSubscriptions')->willReturn($installedSubscriptions);

        $this->game->method('getComposerManager')->willReturn($composerManager);
        $this->game->method('getEventManager')->willReturn($eventManager);

        $r = $this->mm->validate();
        $this->assertTrue(strpos($r[0], "not present in the configuration") !== false);
    }
}
