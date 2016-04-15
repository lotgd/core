<?php

namespace LotGD\Core\Tests;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\{
    EntityManager,
    Tools\Setup,
    Tools\SchemaTool
};
use Doctrine\ORM\Mapping\{
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
                
        $this->_em = EntityManager::create(["url" => "sqlite:///:memory:"], $configuration);
        
        // Create Schema
        $schemaTool = new SchemaTool($this->_em);
        
        $metaClasses = [];
        foreach($this->entities as $entity) {
            $metaClasses[] = new ClassMetadata($entity);
        }
        
        var_dump($metaClasses[0]);
        
        $schemaTool->createSchema($metaClasses);
    }
    
    protected function getEntityManager() {
        return $this->_em;
    }
}
