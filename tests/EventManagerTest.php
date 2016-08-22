<?php
declare(strict_types=1);

namespace LotGD\Core\Tests;

use LotGD\Core\Game;
use LotGD\Core\EventManager;
use LotGD\Core\EventHandler;
use LotGD\Core\Exceptions\WrongTypeException;
use LotGD\Core\Exceptions\ClassNotFoundException;
use LotGD\Core\Exceptions\SubscriptionNotFoundException;
use LotGD\Core\Models\EventSubscription;
use LotGD\Core\Tests\CoreModelTestCase;
use LotGD\Core\Tests\FakeModule\Module as FakeModule;

class EventManagerTestInvalidSubscriber
{

}

class EventManagerTestSubscriber implements EventHandler
{
    public static function handleEvent(Game $g, string $event, array &$context) {}
}

class EventManagerTest extends CoreModelTestCase
{
    /** @var string default data set */
    protected $dataset = "eventManager";

    public function setUp()
    {
        parent::setUp();

        $this->g = $this->getMockBuilder(Game::class)
                     ->disableOriginalConstructor()
                     ->getMock();
        $this->g->method('getEntityManager')->willReturn($this->getEntityManager());
    }

    public function testSubscribeNoClass()
    {
        $em = new EventManager($this->g);

        $this->expectException(ClassNotFoundException::class);
        $em->subscribe("/test.event/", 'LotGD\Core\Tests\NoClassHere', 'lotgd/tests');
    }

    public function testSubscribeInvalidClass()
    {
        $em = new EventManager($this->g);

        $this->expectException(WrongTypeException::class);
        $em->subscribe("/test.event/", 'LotGD\Core\Tests\EventManagerTestInvalidSubscriber', 'lotgd/tests');
    }

    public function testSubscribeInvalidRegexp()
    {
        $em = new EventManager($this->g);

        $this->expectException(WrongTypeException::class);
        $em->subscribe("/test.event", FakeModule::class, 'lotgd/tests');
    }

    public function testGetSubscriptions()
    {
        $em = new EventManager($this->g);

        $pattern = "/test\\.foo.*/";
        $class = FakeModule::class;
        $library = 'lotgd/tests';

        $sub = EventSubscription::create([
          'pattern' => $pattern,
          'class' => $class,
          'library' => $library
        ]);

        $subscriptions = $em->getSubscriptions();
        $this->assertContainsOnlyInstancesOf(EventSubscription::class, $subscriptions);

        // This is a little fragile, but assertContains() doesn't seem to work.
        $this->assertEquals($sub, $subscriptions[0]);
    }

    public function testSubscribeSuccess()
    {
        $em = new EventManager($this->g);

        $pattern = "/test.event/";
        $class = FakeModule::class;
        $library = 'lotgd/tests';

        $em->subscribe($pattern, $class, $library);

        $sub = EventSubscription::create([
            'pattern' => $pattern,
            'class' => $class,
            'library' => $library
        ]);

        $subscriptions = $em->getSubscriptions();
        $this->assertContainsOnlyInstancesOf(EventSubscription::class, $subscriptions);

        // This is a little fragile, but assertContains() doesn't seem to work.
        $this->assertEquals($sub, $subscriptions[1]);
    }

    public function testUnsubscribeSuccess()
    {
        $em = new EventManager($this->g);

        $em->unsubscribe("/test\\.foo.*/", FakeModule::class, 'lotgd/tests');

        $subscriptions = $em->getSubscriptions();
        $this->assertEmpty($subscriptions);
    }

    public function testUnsubscribeNotFound()
    {
        $em = new EventManager($this->g);

        $this->expectException(SubscriptionNotFoundException::class);
        $em->unsubscribe("/notfound/", FakeModule::class, 'lotgd/tests');
    }

    public function testPublish()
    {
        $em = new EventManager($this->g);

        $event = 'test.foo.something_here';
        $context = array('foo' => 'bar');

        $em->publish($event, $context);
        $this->assertEquals($context['foo'], 'baz');
    }
}
