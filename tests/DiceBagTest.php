<?php
declare(strict_types=1);

namespace LotGD\Core\Tests;

use LotGD\Core\DiceBag;

/**
* @backupGlobals disabled
* @backupStaticAttributes disabled
*/
class DiceBagTests extends \PHPUnit_Framework_TestCase {
  public function testUniform() {
    $db = new DiceBag();
    $value = $db->uniform(0., 1.);
    $this->assertGreaterThan(0, $value);
    $this->assertLessThan(1, $value);
  }

  public function testNormal() {
    $db = new DiceBag();
    $value = $db->normal(0., 1.);
    $this->assertGreaterThan(0, $value);
    $this->assertLessThan(1, $value);

    $value = $db->normal(1., 0.);
    $this->assertGreaterThan(0, $value);
    $this->assertLessThan(1, $value);

    $this->assertEquals(0, $db->normal(0., 0.));
  }
}
