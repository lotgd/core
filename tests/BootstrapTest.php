<?php
declare(strict_types=1);

namespace LotGD\Core\Tests;

use LotGD\Core\Bootstrap;

class BootstrapTest extends \PHPUnit_Framework_TestCase
{
    public function testGame()
    {
        $g = Bootstrap::createGame();
        $this->assertNotNull($g->getEntityManager());
        $this->assertNotNull($g->getEventManager());
    }
}
