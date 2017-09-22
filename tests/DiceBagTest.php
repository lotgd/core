<?php
declare(strict_types=1);

namespace LotGD\Core\Tests;

use LotGD\Core\DiceBag;

/**
* @backupGlobals disabled
* @backupStaticAttributes disabled
*/
class DiceBagTests extends \PHPUnit_Framework_TestCase
{
    public function testUniform()
    {
        $db = new DiceBag();
        $value = $db->uniform(0., 1.);
        $this->assertGreaterThanOrEqual(0, $value);
        $this->assertLessThanOrEqual(1, $value);
    }

    public function testNormal()
    {
        $db = new DiceBag();
        $value = $db->normal(0., 1.);
        $this->assertGreaterThanOrEqual(0, $value);
        $this->assertLessThanOrEqual(1, $value);

        $value = $db->normal(1., 0.);
        $this->assertGreaterThanOrEqual(0, $value);
        $this->assertLessThanOrEqual(1, $value);

        $this->assertEquals(0, $db->normal(0., 0.));
    }
    
    public function testPseudoBell()
    {
        $db = new DiceBag();
        $value = $db->pseudoBell();
        $this->assertGreaterThanOrEqual(0, $value);
        $this->assertLessThanOrEqual(mt_getrandmax(), $value);
        
        $value = $db->pseudoBell(5, 5);
        $this->assertSame(5, $value);
        
        $value = $db->pseudoBell(1, 3);
        $this->assertGreaterThanOrEqual(1, $value);
        $this->assertLessThanOrEqual(3, $value);
        
        $value = $db->pseudoBell(3, 1);
        $this->assertGreaterThanOrEqual(1, $value);
        $this->assertLessThanOrEqual(3, $value);
    }

    public function testDice()
    {
        $db = new DiceBag();
        $value = $db->dice(1, 6);
        $this->assertGreaterThanOrEqual(1, $value);
        $this->assertLessThanOrEqual(6, $value);

        $value = $db->dice(5, 5);
        $this->assertSame(5, $value);

        $value = $db->dice(1, 3);
        $this->assertGreaterThanOrEqual(1, $value);
        $this->assertLessThanOrEqual(3, $value);

        $value = $db->dice(3, 1);
        $this->assertGreaterThanOrEqual(1, $value);
        $this->assertLessThanOrEqual(3, $value);
    }
}
