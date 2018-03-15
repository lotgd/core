<?php
declare(strict_types=1);

namespace LotGD\Core\Tests\Models;

use LotGD\Core\Models\Module;
use LotGD\Core\Models\ModuleProperty;
use LotGD\Core\ModuleManager;
use LotGD\Core\Tests\CoreModelTestCase;

/**
 * Tests for module management.
 */
class ModuleTest extends CoreModelTestCase
{
    /** @var string default data set */
    protected $dataset = "module";

    /**
     * Test getter methods
     */
    public function testGetters()
    {
        $em = $this->getEntityManager();
        $scene = $em->getRepository(Module::class)->find('lotgd/tests');

        $this->assertEquals("lotgd/tests", $scene->getLibrary());
        $this->assertEquals(new \DateTime('2016-05-01'), $scene->getCreatedAt());

        $em->flush();
    }

    public function testSetter()
    {
        $em = $this->getEntityManager();
        $module = new Module("lotgd/test/blah");
        $module->setProperty("test", 15);

        $this->assertSame(15, $module->getProperty("test"));
    }

    public function testProperties()
    {
        $em = $this->getEntityManager();

        // test default values
        $module = $em->getRepository(Module::class)->find('lotgd/tests');
        $this->assertSame(5, $module->getProperty("dragonkills", 5));
        $this->assertNotSame(5, $module->getProperty("dragonkills", "5"));
        $this->assertSame("hanniball", $module->getProperty("petname", "hanniball"));

        // test setting variables, then getting
        $module->setProperty("dragonkills", 5);
        $this->assertSame(5, $module->getProperty("dragonkills"));
        $this->assertNotSame("5", $module->getProperty("dragonkills"));

        $module->setProperty("dragonkills", "20");
        $this->assertNotSame(20, $module->getProperty("dragonkills"));
        $this->assertSame("20", $module->getProperty("dragonkills"));

        // save some other variables
        $module->setProperty("testvar1", 5);
        $module->setProperty("testvar2", [5 => 18]);
        $module->setProperty("testvar3 9 8", "spam and eggs");
        $module->setProperty("testvar4", true);

        // test precreated property
        $this->assertSame("hallo", $module->getProperty("test"));

        // test flushing
        $em->flush();

        // revisit database and retrieve properties, check if the correct number is saved
        $total = intval($em->createQueryBuilder()
            ->from(ModuleProperty::class, "u")
            ->select("COUNT(u.propertyName)")
            ->getQuery()->getSingleScalarResult());

        $this->assertSame(6, $total);

        // test cascading removes
        $module->delete($em);

        $total = intval($em->createQueryBuilder()
            ->from(ModuleProperty::class, "u")
            ->select("COUNT(u.propertyName)")
            ->getQuery()->getSingleScalarResult());

        $this->assertSame(0, $total);
    }
}
