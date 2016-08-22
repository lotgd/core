<?php
declare(strict_types=1);

namespace LotGD\Core\Tests\Models;

use LotGD\Core\Models\GameConfiguration;
use LotGD\Core\Tests\CoreModelTestCase;

/**
 * Tests the management of CharacterScenes
 */
class GameConfigurationTest extends CoreModelTestCase
{
    /** @var string default data set */
    protected $dataset = "gameConfiguration";
    
    public function testGetConfiguration()
    {
        $configuration = new GameConfiguration($this->getEntityManager());
        
        $this->assertSame("hallo", $configuration->get("default_test", "hallo"));
        $this->assertSame(87897, $configuration->get("default_test_int", 87897));
        
        $this->assertSame("Legend of the Green Dragon", $configuration->get("gameName", "Daenerys"));
        $this->assertSame("1.0.5.6", $configuration->get("gameVersion", "1.0"));
        $this->assertSame(30, $configuration->get("maxPlayerOnline", 100));
        $this->assertSame(30.4, $configuration->get("testFloat", 100.123512));
        
        $this->getEntityManager()->flush();
    }
    
    public function datasetSetAndGet() {
        return [
            ["testOne", 15],
            ["testTwo", "256"]
        ];
    }
    
    /**
     * Tests setting settings and fetching them back from the database
     * @dataProvider datasetSetAndGet
     * @param string $key
     * @param mixed $value
     */
    public function testSetAndGet(string $key, $value)
    {
        $configuration = new GameConfiguration($this->getEntityManager());
        
        $configuration->set($key, $value);
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();
        $this->assertSame($value, $configuration->get($key, null));
    }
}
