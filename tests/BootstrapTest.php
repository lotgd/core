<?php
declare(strict_types=1);

namespace LotGD\Core\Tests;

use LotGD\Core\Bootstrap;

class BootstrapTest extends \PHPUnit_Framework_TestCase
{
    public function testGame()
    {
        $g = Bootstrap::game();
        $this->assertNotNull($g->db());
        $this->assertNotNull($g->events());
    }
}
