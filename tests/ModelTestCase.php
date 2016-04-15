<?php
declare(strict_types=1);

namespace LotGD\Core\Tests;

use Doctrine\ORM\{
    EntityManager,
    Mapping\AnsiQuoteStrategy,
    Tools\Setup,
    Tools\SchemaTool
};

/**
 * Description of ModelTestCase
 */
abstract class ModelTestCase extends \PHPUnit_Framework_TestCase {
    /** @var array */
    protected $entities = [];
    /** @var Doctrine\ORM\EntityManager */
    protected $_em;
    
    /**
     * Sets up the database and database structure
     */
    protected function setUp() {        
        $configuration = Setup::createAnnotationMetadataConfiguration(["src/Models"], true);
        $configuration->setQuoteStrategy(new AnsiQuoteStrategy());
                
        $this->_em = EntityManager::create(["url" => "sqlite:///:memory:"], $configuration);
        
        $metaData = $this->_em->getMetadataFactory()->getAllMetadata();
        
        // Create Schema
        $schemaTool = new SchemaTool($this->_em);
        $schemaTool->createSchema($metaData);
    }
    
    /**
     * Returns the entity manager to run tests.
     * @return \LotGD\Core\Tests\EntityManager
     */
    protected function getEntityManager(): EntityManager {
        return $this->_em;
    }
}
