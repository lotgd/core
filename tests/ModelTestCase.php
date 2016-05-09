<?php
declare(strict_types=1);

namespace LotGD\Core\Tests;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\ORM\{
    EntityManager,
    EntityManagerInterface,
    Mapping\AnsiQuoteStrategy,
    Tools\Setup,
    Tools\SchemaTool
};

/**
 * Description of ModelTestCase
 */
abstract class ModelTestCase extends \PHPUnit_Extensions_Database_TestCase {
    /** @var \PDO */
    static private $pdo = null;
    /** @var EntityManager */
    static private $em = null;
    private $connection = null;
    
    final public function getConnection() {
        if($this->connection === null) {
            if(self::$pdo === null) {
                self::$pdo = new \PDO($GLOBALS['DB_DSN'], $GLOBALS["DB_USER"], $GLOBALS["DB_PASSWORD"]);
                
                // Read db annotations from model files
                $configuration = Setup::createAnnotationMetadataConfiguration(["src/Models"], true);
                $configuration->setQuoteStrategy(new AnsiQuoteStrategy());
                
                $configuration->addFilter("soft-deleteable", 'Gedmo\SoftDeleteable\Filter\SoftDeleteableFilter');

                self::$em = EntityManager::create(["pdo" => self::$pdo], $configuration); 
                self::$em->getFilters()->enable("soft-deleteable");   
                self::$em->getEventManager()->addEventSubscriber(new \Gedmo\SoftDeleteable\SoftDeleteableListener());

                // Create Schema
                $metaData = self::$em->getMetadataFactory()->getAllMetadata();
                $schemaTool = new SchemaTool(self::$em);
                $schemaTool->updateSchema($metaData);
            }
            
            $this->conn = $this->createDefaultDBConnection(self::$pdo, $GLOBALS["DB_NAME"]);
        }
        
        return $this->conn;
    }
    
    protected function getDataSet(): \PHPUnit_Extensions_Database_DataSet_YamlDataSet {
        return new \PHPUnit_Extensions_Database_DataSet_YamlDataSet(
            __DIR__."/datasets/".$this->dataset.".yml"
        );
    }
    
    protected function getEntityManager(): EntityManagerInterface {
        return self::$em;
    }
}
