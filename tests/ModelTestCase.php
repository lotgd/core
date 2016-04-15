<?php

namespace LotGD\Core\Tests;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\{
    EntityManager,
    Tools\Setup,
    Tools\SchemaTool
};
use Doctrine\ORM\Mapping\{
    AnsiQuoteStrategy,
    ClassMetadata,
    Driver\DriverChain,
    Driver\AnnotationDriver
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
    
    protected function getEntityManager() {
        return $this->_em;
    }
}
