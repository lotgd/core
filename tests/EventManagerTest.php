<?php
declare(strict_types=1);

namespace LotGD\Core\Tests;

use LotGD\Core\EventManager;
use LotGD\Core\Models\EventSubscription;
use LotGD\Core\EventHandler;
use LotGD\Core\Exceptions\WrongTypeException;
use LotGD\Core\Exceptions\ClassNotFoundException;
use LotGD\Core\Exceptions\SubscriptionNotFoundException;
use LotGD\Core\Tests\ModelTestCase;

class EventManagerTestInvalidSubscriber
{

}

class EventManagerTestSubscriber implements EventHandler
{
    public static function handleEvent(string $event, array $context) {}
}

class EventManagerTestInstalledSubscriber implements EventHandler
{
    public static function handleEvent(string $event, array $context) {
        $context['foo'] = 'baz';
        return $context;
    }
}

class EventManagerTest extends ModelTestCase
{
    /** @var string default data set */
    protected $dataset = "eventManager";

    public function testSubscribeNoClass()
    {
        $em = new EventManager($this->getEntityManager());

        $this->expectException(ClassNotFoundException::class);
        $em->subscribe("/test.event/", 'LotGD\Core\Tests\NoClassHere');
    }

    public function testSubscribeInvalidClass()
    {
        $em = new EventManager($this->getEntityManager());

        $this->expectException(WrongTypeException::class);
        $em->subscribe("/test.event/", 'LotGD\Core\Tests\EventManagerTestInvalidSubscriber');
    }

    public function testSubscribeInvalidRegexp()
    {
        $em = new EventManager($this->getEntityManager());

        $this->expectException(WrongTypeException::class);
        $em->subscribe("/test.event", 'LotGD\Core\Tests\EventManagerTestSubscriber');
    }

    public function testGetSubscriptions()
    {
      $em = new EventManager($this->getEntityManager());

      $pattern = "/test\\.foo.*/";
      $class = 'LotGD\\Core\\Tests\\EventManagerTestInstalledSubscriber';

      $sub = EventSubscription::create([
          'pattern' => $pattern,
          'class' => $class,
      ]);

      $subscriptions = $em->getSubscriptions();
      $this->assertContainsOnlyInstancesOf(EventSubscription::class, $subscriptions);

      // This is a little fragile, but assertContains() doesn't seem to work.
      $this->assertEquals($sub, $subscriptions[0]);
    }

    public function testSubscribeSuccess()
    {
        $em = new EventManager($this->getEntityManager());

        $pattern = "/test.event/";
        $class = 'LotGD\Core\Tests\EventManagerTestSubscriber';

        $em->subscribe($pattern, $class);

        $sub = EventSubscription::create([
            'pattern' => $pattern,
            'class' => $class,
        ]);

        $subscriptions = $em->getSubscriptions();
        $this->assertContainsOnlyInstancesOf(EventSubscription::class, $subscriptions);

        // This is a little fragile, but assertContains() doesn't seem to work.
        $this->assertEquals($sub, $subscriptions[1]);
    }

    public function testUnsubscribeSuccess()
    {
        $em = new EventManager($this->getEntityManager());

        $em->unsubscribe("/test\\.foo.*/", 'LotGD\Core\Tests\EventManagerTestInstalledSubscriber');

        $subscriptions = $em->getSubscriptions();
        $this->assertEmpty($subscriptions);
    }

    public function testUnsubscribeNotFound()
    {
        $em = new EventManager($this->getEntityManager());

        $this->expectException(SubscriptionNotFoundException::class);
        $em->unsubscribe("/notfound/", 'LotGD\Core\Tests\EventManagerTestInstalledSubscriber');
    }

    public function testPublish()
    {
        $em = new EventManager($this->getEntityManager());

        $event = 'test.foo.something_here';
        $context = array('foo' => 'bar');

        $em->publish($event, $context);
        $this->assertEquals($context['foo'], 'baz');
    }
}
