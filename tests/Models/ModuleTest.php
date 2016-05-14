<?php
declare(strict_types=1);

namespace LotGD\Core\Tests\Models;

use LotGD\Core\Models\Module;
use LotGD\Core\Tests\ModelTestCase;

/**
 * Tests for module management.
 */
class ModuleTest extends ModelTestCase
{
    /** @var string default data set */
    protected $dataset = "module";

    /**
     * Test getter methods
     */
    public function testGetters()
    {
        $em = $this->getEntityManager();
        $scene = $em->getRepository(Module::class)->find('lotgd/test');

        $this->assertEquals("lotgd/test", $scene->getLibrary());
        $this->assertEquals(new \DateTime('2016-05-01'), $scene->getCreatedAt());

        $em->flush();
    }
}
