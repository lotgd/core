<?php
declare(strict_types=1);

namespace LotGD\Core\Tests;

class CoreModelTestCase extends ModelTestCase
{
    /**
     * Returns a .yml dataset under this name
     * @return \PHPUnit_Extensions_Database_DataSet_YamlDataSet
     */
    protected function getDataSet(): \PHPUnit_Extensions_Database_DataSet_YamlDataSet
    {
        return new \PHPUnit_Extensions_Database_DataSet_YamlDataSet(implode(DIRECTORY_SEPARATOR, [__DIR__, 'datasets', $this->dataset . '.yml']));
    }
}
